<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Backups;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Pterodactyl\Extensions\NodeBackup\NodeBackupManager;
use Pterodactyl\Models\Backup;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Extensions\Filesystem\S3Filesystem;
use Pterodactyl\Models\NodeBackup;
use Pterodactyl\Models\NodeBackupGroup;
use Pterodactyl\Models\NodeBackupServer;
use Pterodactyl\Services\NodeBackup\NodeBackupService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Pterodactyl\Http\Requests\Api\Remote\ReportBackupCompleteRequest;

class BackupStatusController extends Controller
{
    public function __construct(
        private BackupManager $backupManager,
        private NodeBackupService $nodeBackupService,
        private NodeBackupManager $nodeBackupManager
    )
    {
    }

    public function index(ReportBackupCompleteRequest $request, string $backup): JsonResponse
    {
        // Get the node associated with the request.
        /** @var \Pterodactyl\Models\Node $node */
        $node = $request->attributes->get('node');

        /** @var \Pterodactyl\Models\Backup $model */
        $model = Backup::query()
            ->where('uuid', $backup)
            ->first();

        if (is_null($model)) {
            /** @var \Pterodactyl\Models\NodeBackupServer $nodeBackupServer */
            $nodeBackupServer = NodeBackupServer::query()
                ->where('uuid', $backup)
                ->firstOrFail();

            // Check that the backup is "owned" by the node making the request. This avoids other nodes
            // from messing with backups that they don't own.
            /** @var \Pterodactyl\Models\Server $server */
            $server = $nodeBackupServer->server();
            if ($server->node_id !== $node->id) {
                throw new HttpForbiddenException('You do not have permission to access that backup.');
            }

            $successful = $request->boolean('successful');

            $nodeBackupServer->update([
                'is_successful' => $successful,
                'bytes' => $successful ? $request->input('size') : 0,
                'checksum' => $successful ? ($request->input('checksum_type') . ':' . $request->input('checksum')) : null,
                'completed_at' => CarbonImmutable::now(),
            ]);

            $nodeBackupGroup = $nodeBackupServer->nodeBackupGroup();
            $nodeBackupServers = NodeBackupServer::query()->where('node_backup_id', $nodeBackupServer->node_backup_id)->get();

            if ($nodeBackupServers->whereNull('completed_at')->count() === 0) {
                $nodeBackupServer->nodeBackup()->update([
                    'completed_at' => CarbonImmutable::now(),
                ]);
                $nodeBackupGroup->update([
                    'last_run_at' => CarbonImmutable::now(),
                ]);
            } else {
                $this->nodeBackupService->processNodeBackupGroupsToSave($nodeBackupGroup, $nodeBackupServers);
            }

            $adapter = $this->nodeBackupManager
                ->setNodeBackupGroup($nodeBackupGroup)
                ->adapter();

            if ($adapter instanceof S3Filesystem) {
                $this->completeMultipartUploadNodeServerBackup($nodeBackupServer, $adapter, $successful, $request->input('parts'));
            }
        } else {
            // Check that the backup is "owned" by the node making the request. This avoids other nodes
            // from messing with backups that they don't own.
            /** @var \Pterodactyl\Models\Server $server */
            $server = $model->server;
            if ($server->node_id !== $node->id) {
                throw new HttpForbiddenException('You do not have permission to access that backup.');
            }

            if ($model->is_successful) {
                throw new BadRequestHttpException('Cannot update the status of a backup that is already marked as completed.');
            }

            $action = $request->boolean('successful') ? 'server:backup.complete' : 'server:backup.fail';
            $log = Activity::event($action)->subject($model, $model->server)->property('name', $model->name);

            $log->transaction(function () use ($model, $request) {
                $successful = $request->boolean('successful');

                $model->fill([
                    'is_successful' => $successful,
                    'is_locked' => $successful ? $model->is_locked : false,
                    'checksum' => $successful ? ($request->input('checksum_type') . ':' . $request->input('checksum')) : null,
                    'bytes' => $successful ? $request->input('size') : 0,
                    'completed_at' => CarbonImmutable::now(),
                ])->save();

                $adapter = $this->backupManager->adapter();
                if ($adapter instanceof S3Filesystem) {
                    $this->completeMultipartUploadBackup($model, $adapter, $successful, $request->input('parts'));
                }
            });
        }

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    public function restore(Request $request, string $backup): JsonResponse
    {
        /** @var \Pterodactyl\Models\Backup $model */
        $model = Backup::query()->where('uuid', $backup)->first();

        if (is_null($model)) {
            $nodeBackupServer = NodeBackupServer::query()->where('uuid', $backup)->firstOrFail();

            $nodeBackupServer->server()->update([
                'status' => null,
            ]);

            if ($nodeBackupServer->nodeBackup()->isRestoring()) {
                $nodeBackupServer->update([
                    'restoration_completed_at' => CarbonImmutable::now(),
                ]);

                $this->nodeBackupService->processNodeBackupServersToRestore($nodeBackupServer->nodeBackupGroup(), NodeBackupServer::query()
                    ->where('node_backup_id', $nodeBackupServer->node_backup_id)
                    ->get()
                );
            }
        } else {
            $model->server->update(['status' => null]);

            Activity::event($request->boolean('successful') ? 'server:backup.restore-complete' : 'server.backup.restore-failed')
                ->subject($model, $model->server)
                ->property('name', $model->name)
                ->log();
        }

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    protected function completeMultipartUploadBackup(Backup $backup, S3Filesystem $adapter, bool $successful, ?array $parts): void
    {
        if (empty($backup->upload_id)) {
            if (!$successful) {
                return;
            }

            throw new DisplayException('Cannot complete backup request: no upload_id present on model.');
        }

        $params = [
            'Bucket' => $adapter->getBucket(),
            'Key' => sprintf('%s/%s.tar.gz', $backup->server->uuid, $backup->uuid),
            'UploadId' => $backup->upload_id,
        ];

        $client = $adapter->getClient();
        if (!$successful) {
            $client->execute($client->getCommand('AbortMultipartUpload', $params));

            return;
        }

        // Otherwise send a CompleteMultipartUpload request.
        $params['MultipartUpload'] = [
            'Parts' => [],
        ];

        if (is_null($parts)) {
            $params['MultipartUpload']['Parts'] = $client->execute($client->getCommand('ListParts', $params))['Parts'];
        } else {
            foreach ($parts as $part) {
                $params['MultipartUpload']['Parts'][] = [
                    'ETag' => $part['etag'],
                    'PartNumber' => $part['part_number'],
                ];
            }
        }

        $client->execute($client->getCommand('CompleteMultipartUpload', $params));
    }

    protected function completeMultipartUploadNodeServerBackup(NodeBackupServer $backup, S3Filesystem $adapter, bool $successful, ?array $parts): void
    {
        if (empty($backup->upload_id)) {
            if (!$successful) {
                return;
            }

            throw new DisplayException('Cannot complete backup request: no upload_id present on model.');
        }

        $params = [
            'Bucket' => $adapter->getBucket(),
            'Key' => sprintf('%s/%s.tar.gz', $backup->server()->uuid, $backup->uuid),
            'UploadId' => $backup->upload_id,
        ];

        $client = $adapter->getClient();
        if (!$successful) {
            $client->execute($client->getCommand('AbortMultipartUpload', $params));

            return;
        }

        $params['MultipartUpload'] = [
            'Parts' => [],
        ];

        if (is_null($parts)) {
            $params['MultipartUpload']['Parts'] = $client->execute($client->getCommand('ListParts', $params))['Parts'];
        } else {
            foreach ($parts as $part) {
                $params['MultipartUpload']['Parts'][] = [
                    'ETag' => $part['etag'],
                    'PartNumber' => $part['part_number'],
                ];
            }
        }

        $client->execute($client->getCommand('CompleteMultipartUpload', $params));
    }
}
