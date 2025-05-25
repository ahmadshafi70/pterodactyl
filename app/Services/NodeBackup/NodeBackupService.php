<?php

namespace Pterodactyl\Services\NodeBackup;

use Carbon\CarbonImmutable;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use Illuminate\Http\Response;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\NodeBackup;
use Pterodactyl\Models\NodeBackupGroup;
use Pterodactyl\Models\NodeBackupServer;
use GuzzleHttp\Exception\ClientException;
use Pterodactyl\Services\Nodes\NodeJWTService;
use Pterodactyl\Repositories\Wings\DaemonNodeBackupRepository;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Extensions\NodeBackup\NodeBackupManager;
use Pterodactyl\Services\NodeBackup\Servers\NodeBackupServerDeletionService;
use Pterodactyl\Services\NodeBackup\Servers\NodeBackupServerRecreationService;

class NodeBackupService
{
    public function __construct(
        private NodeBackupManager $nodeBackupManager,
        private DaemonNodeBackupRepository $daemonNodeBackupRepository,
        private NodeJWTService $jwtService,
        private NodeBackupServerRecreationService $nodeBackupServerRecreationService,
        private NodeBackupServerDeletionService $nodeBackupServerDeletionService,
    ) {
    }

    public function handleCreation(NodeBackup $nodeBackup): void
    {
        $nodeBackupGroup = $nodeBackup->nodeBackupGroup();
        $nodeBackupServers = [];

        foreach ($nodeBackupGroup->nodes() as $node) {
            foreach ($node->servers as $server) {
                if ($server->status === null) {
                    $nodeBackupServers[] = NodeBackupServer::create([
                        'node_backup_id' => $nodeBackup->id,
                        'server_id' => $server->id,
                        'server_uuid' => $server->uuid,
                        'server_name' => $server->name,
                        'node_id' => $server->node->id,
                        'uuid' => NodeBackupServer::generateUniqueUuid(),
                        'disk' => $nodeBackupGroup->getAdapter(),
                    ]);
                }
            }
        }

        for ($i = 0; $i < $nodeBackupGroup->max_being_made_backups; ++$i) {
            $nodeBackupServer = $nodeBackupServers[$i] ?? null;

            if ($nodeBackupServer === null) {
                continue;
            }

            $this->handleServerBackupCreation($nodeBackupGroup, $nodeBackupServer);
        }
    }

    public function handleServerBackupCreation(NodeBackupGroup $nodeBackupGroup, NodeBackupServer $nodeBackupServer): void
    {
        if ($nodeBackupGroup->max_server_size !== -1) {
            if ($this->getServerCurrentDiskUsage($nodeBackupServer->server()) >= $nodeBackupGroup->max_server_size) {
                $nodeBackupServer->delete();

                return;
            }
        }

        $nodeBackupServer->update([
            'started_at' => CarbonImmutable::now(),
        ]);

        $this->daemonNodeBackupRepository->setServer($nodeBackupServer->server())
            ->setBackupAdapter($nodeBackupGroup->getAdapter())
            ->backup($nodeBackupServer, $nodeBackupGroup->ignored_files);
    }

    public function processNodeBackupGroupsToSave(NodeBackupGroup $nodeBackupGroup, \Illuminate\Database\Eloquent\Collection|array $nodeBackupServers): void
    {
        $ongoingNodeBackupServers = $nodeBackupServers
            ->whereNotNull('started_at')
            ->whereNull('completed_at');

        $toDoNodeBackupServers = $nodeBackupServers
            ->whereNull('started_at');

        $threshold = $nodeBackupGroup->max_being_made_backups - $ongoingNodeBackupServers->count();

        for ($i = 0; $i < $threshold; ++$i) {
            $key = $i + array_key_first($toDoNodeBackupServers->toArray());

            if (!isset($toDoNodeBackupServers[$key])) {
                continue;
            }

            $this->handleServerBackupCreation($nodeBackupGroup, $toDoNodeBackupServers->offsetGet($key));
        }
    }

    public function handleRetention(NodeBackupGroup $nodeBackupGroup): void
    {
        if ($nodeBackupGroup->retention_days < 0) {
            return;
        }

        $nodeBackups = NodeBackup::query()
            ->where('node_backup_group_id', $nodeBackupGroup->id)
            ->where('created_at', '<=', CarbonImmutable::now()->subDays($nodeBackupGroup->retention_days))
            ->get();

        foreach ($nodeBackups as $nodeBackup) {
            $this->handleDeletion($nodeBackup);
        }
    }

    public function handleDeletion(NodeBackup $nodeBackup, bool $force = false): void
    {
        $nodeBackupServers = NodeBackupServer::query()->where('node_backup_id', $nodeBackup->id)->get();

        foreach ($nodeBackupServers as $nodeBackupServer) {
            if ($nodeBackupServer->isSuccessful()) {
                $this->handleDeletionBackup($nodeBackupServer, $force);
            } else {
                $nodeBackupServer->delete();
            }
        }

        $nodeBackup->delete();
    }

    public function handleDeletionBackup(NodeBackupServer $nodeBackupServer, bool $force = false): void
    {
        try {
            if ($nodeBackupServer->disk === Backup::ADAPTER_AWS_S3) {
                $this->deleteFromS3($nodeBackupServer);
            } elseif ($server = $nodeBackupServer->server()) {
                try {
                    $this->daemonNodeBackupRepository->setServer($server)->delete($nodeBackupServer);
                } catch (DaemonConnectionException $exception) {
                    $previous = $exception->getPrevious();

                    if (!$previous instanceof ClientException || $previous->getResponse()->getStatusCode() !== Response::HTTP_NOT_FOUND) {
                        throw $exception;
                    }
                }
            }
        } catch (\Exception $e) {
            if (!$force) {
                throw $e;
            }
        }

        $nodeBackupServer->delete();

        $nodeBackup = $nodeBackupServer->nodeBackup();
        if ($nodeBackup->numberOfBackups() === 0) {
            $nodeBackup->delete();
        }
    }

    public function handleDownload(NodeBackupServer $nodeBackupServer, User $user = null): string
    {
        if ($nodeBackupServer->disk === Backup::ADAPTER_AWS_S3) {
            return $this->getS3BackupUrl($nodeBackupServer);
        }

        $serverUuid = $nodeBackupServer->server()?->uuid ?? $nodeBackupServer->server_uuid;
        $node = $nodeBackupServer->node();
        $token = $this->jwtService
            ->setExpiresAt(CarbonImmutable::now()->addMinutes(15))
            ->setUser($user)
            ->setClaims([
                'backup_uuid' => $nodeBackupServer->uuid,
                'server_uuid' => $serverUuid,
            ])
            ->handle($node, $user->id . $serverUuid);

        return sprintf('%s/download/backup?token=%s', $node->getConnectionAddress(), $token->toString());
    }

    public function handleNodeBackupServersToRestore(NodeBackupGroup $nodeBackupGroup, \Illuminate\Database\Eloquent\Collection|array $nodeBackupServers): void
    {
        $nodeBackupServers = $nodeBackupServers
            ->whereNotNull('restoration_type');

        for ($i = 0; $i < $nodeBackupGroup->max_being_made_backups; ++$i) {
            $key = $i + array_key_first($nodeBackupServers->toArray());

            if (!isset($nodeBackupServers[$key])) {
                continue;
            }

            $nodeBackupServer = $nodeBackupServers->offsetGet($key);

            switch ($nodeBackupServer->restoration_type) {
                case NodeBackupServer::RESTORATION_TYPE_CLASSIC:
                    $this->handleRestore($nodeBackupServer, null, true);
                    break;
                case NodeBackupServer::RESTORATION_TYPE_RECREATE:
                    $this->handleRestoreOnNewNode($nodeBackupServer, $nodeBackupServer->restorationNode(), true);
                    break;
                default:
                    throw new \Exception('Unknown restoration type.');
            }
        }
    }

    public function processNodeBackupServersToRestore(NodeBackupGroup $nodeBackupGroup, \Illuminate\Database\Eloquent\Collection|array $nodeBackupServers): void
    {
        $doneRestorationNodeBackupServers = $nodeBackupServers
            ->whereNotNull('restoration_started_at')
            ->whereNotNull('restoration_completed_at');

        $toDoRestorationNodeBackupServers = $nodeBackupServers
            ->whereNull('restoration_started_at');

        $threshold = $nodeBackupGroup->max_being_made_backups - ($doneRestorationNodeBackupServers->count() % $nodeBackupGroup->max_being_made_backups);

        for ($i = 0; $i < $threshold; ++$i) {
            $key = $i + array_key_first($toDoRestorationNodeBackupServers->toArray());

            if (!isset($toDoRestorationNodeBackupServers[$key])) {
                continue;
            }

            $nodeBackupServer = $toDoRestorationNodeBackupServers->offsetGet($key);

            switch ($nodeBackupServer->restoration_type) {
                case NodeBackupServer::RESTORATION_TYPE_CLASSIC:
                    $this->handleRestore($nodeBackupServer, null, true);
                    break;
                case NodeBackupServer::RESTORATION_TYPE_RECREATE:
                    $this->handleRestoreOnNewNode($nodeBackupServer, $nodeBackupServer->restorationNode(), true);
                    break;
                default:
                    throw new \Exception('Unknown restoration type.');
            }
        }
    }

    public function handleRestore(NodeBackupServer $nodeBackupServer, User $user = null, bool $isNodeBackupRestoration = false, bool $truncate = true): void
    {
        $server = $nodeBackupServer->server();

        if (!is_null($server->status)) {
            throw new \Exception('This server is not currently in a state that allows for a backup to be restored.');
        }

        if (!$nodeBackupServer->isSuccessful()) {
            throw new \Exception('This backup cannot be restored at this time: not completed or failed.');
        }

        if ($nodeBackupServer->disk === Backup::ADAPTER_AWS_S3) {
            $url = $this->handleDownload($nodeBackupServer, $user);
        }

        if ($isNodeBackupRestoration) {
            $nodeBackupServer->update([
                'restoration_started_at' => CarbonImmutable::now(),
            ]);
        }

        $server->update([
            'status' => Server::STATUS_RESTORING_BACKUP,
        ]);

        $this->daemonNodeBackupRepository->setServer($server)->restore($nodeBackupServer, $url ?? null, $truncate);
    }

    public function handleRestoreOnNewNode(NodeBackupServer $nodeBackupServer, Node $newNode, bool $isNodeBackupRestoration = false): void
    {
        $server = $nodeBackupServer->server();

        if (!is_null($server->status)) {
            throw new \Exception('This server is not currently in a state that allows for a backup to be restored.');
        }

        if (!$nodeBackupServer->isSuccessful()) {
            throw new \Exception('This backup cannot be restored at this time: not completed or failed.');
        }

        if ($newNode->allocations->count() < $server->allocations->count()) {
            throw new \Exception('This node does not have enough allocations to restore this server.');
        }

        if ($isNodeBackupRestoration) {
            $nodeBackupServer->update([
                'restoration_started_at' => CarbonImmutable::now(),
            ]);
        }

        $this->nodeBackupServerDeletionService->handle($server);

        $server->update([
            'node_id' => $newNode->id,
            'status' => Server::STATUS_INSTALLING,
            'restored_from' => $nodeBackupServer->id,
            'installed_at' => null,
            'skip_scripts' => true,
        ]);

        $server->refresh();

        $this->nodeBackupServerRecreationService->handle($server);
    }

    private function deleteFromS3(NodeBackupServer $backup): void
    {
        /** @var \Pterodactyl\Extensions\Filesystem\S3Filesystem $adapter */
        $adapter = $this->nodeBackupManager
            ->setNodeBackupGroup($backup->nodeBackupGroup())
            ->adapter();
        $serverUuid = $backup->server()?->uuid ?? $backup->server_uuid;

        $adapter->getClient()->deleteObject([
            'Bucket' => $adapter->getBucket(),
            'Key' => sprintf('%s/%s.tar.gz', $serverUuid, $backup->uuid),
        ]);
    }

    private function getS3BackupUrl(NodeBackupServer $nodeBackupServer): string
    {
        $adapter = $this->nodeBackupManager
            ->setNodeBackupGroup($nodeBackupServer->nodeBackupGroup())
            ->adapter();

        $serverUuid = $nodeBackupServer->server()?->uuid ?? $nodeBackupServer->server_uuid;
        $request = $adapter->getClient()->createPresignedRequest(
            $adapter->getClient()->getCommand('GetObject', [
                'Bucket' => $adapter->getBucket(),
                'Key' => sprintf('%s/%s.tar.gz', $serverUuid, $nodeBackupServer->uuid),
                'ContentType' => 'application/x-gzip',
            ]),
            CarbonImmutable::now()->addMinutes(5)
        );

        return $request->getUri()->__toString();
    }

    private function getServerCurrentDiskUsage(Server $server): int
    {
        return round($this->daemonNodeBackupRepository
            ->setNode($server->node)
            ->setServer($server)
            ->getDetails()['utilization']['disk_bytes'] / 1024 / 1024, 2) ?? -1;
    }
}
