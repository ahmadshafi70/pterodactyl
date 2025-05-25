<?php

namespace Pterodactyl\Http\Controllers\Admin\NodeBackup;

use Carbon\Carbon;
use Cron\CronExpression;
use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\NodeBackup;
use Prologue\Alerts\AlertsMessageBag;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Pterodactyl\Models\NodeBackupGroup;
use Spatie\QueryBuilder\Filters\Filter;
use Pterodactyl\Models\NodeBackupServer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Models\NodeBackupS3Server;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\NodeBackup\NodeBackupService;
use Pterodactyl\Http\Requests\Admin\NodeBackup\StoreNodeBackupRequest;
use Pterodactyl\Http\Requests\Admin\NodeBackup\StoreNodeBackupGroupRequest;
use Pterodactyl\Http\Requests\Admin\NodeBackup\UpdateNodeBackupGroupRequest;

class NodeBackupController extends Controller
{
    protected AlertsMessageBag $alert;

    private NodeBackupService $nodeBackupService;

    public function __construct(
        AlertsMessageBag $alert,
        NodeBackupService $nodeBackupService
    ) {
        $this->alert = $alert;
        $this->nodeBackupService = $nodeBackupService;
    }

    public function index(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        return view('admin.node_backup.index', [
            'backup_groups' => QueryBuilder::for(NodeBackupGroup::query())->allowedFilters(['name'])->paginate(10),
        ]);
    }

    public function statistics(Request $request): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        return view('admin.node_backup.statistics', [
            'backup_groups' => NodeBackupGroup::all(),
            'node_backups' => NodeBackup::all(),
            'node_backup_servers' => NodeBackupServer::all(),
            'cumulativeBytes' => collect(DB::select('SELECT (COALESCE(LAG(SUM(bytes)) OVER (ORDER BY completed_at), 0) + SUM(bytes)) / 1024 / 1024 AS total_mebibytes,
    DATE(completed_at) AS date 
FROM node_backup_servers
GROUP BY DATE(completed_at)'))
                ->map(fn ($dailyBytes) => ['total_mebibytes' => (float) $dailyBytes->total_mebibytes, 'date' => new Carbon($dailyBytes->date)]),
            'dailyBackupCounts' => collect(DB::select('SELECT (COALESCE(LAG(COUNT(*)) OVER (ORDER BY completed_at), 0) + COUNT(*)) AS count, DATE(completed_at) AS date FROM node_backup_servers GROUP BY DATE(completed_at)'))
                ->map(fn ($backupCount) => ['count' => (int) $backupCount->count, 'date' => new Carbon($backupCount->date)]),
        ]);
    }

    public function createNodeBackupGroup(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        if (Node::query()->count() === 0) {
            $this->alert->danger('You must have at least one node before you can create a backup group.')->flash();

            return redirect()->route('admin.node-backup');
        }

        return view('admin.node_backup.group.new', [
            'locations' => Location::all(),
            's3_servers' => NodeBackupS3Server::all(),
        ]);
    }

    public function storeNodeBackupGroup(StoreNodeBackupGroupRequest $request): \Illuminate\Http\RedirectResponse
    {
        try {
            $data = $this->getNormalizedBackupGroup($request);

            $backup_group = NodeBackupGroup::query()
                ->create($data);

            $this->alert->success('Node Backup Group has been created successfully.')->flash();

            return redirect()->route('admin.node-backup.group.view', $backup_group->id);
        } catch (\Exception $e) {
            $this->alert->danger($e->getMessage())->flash();

            return redirect()->route('admin.node-backup');
        }
    }

    public function viewNodeBackupGroup(int $nodeBackupGroupId): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        return view('admin.node_backup.group.view', [
            'locations' => Location::all(),
            'backup_group' => NodeBackupGroup::query()->findOrFail($nodeBackupGroupId),
            'node_backups' => QueryBuilder::for(NodeBackup::query()->where('node_backup_group_id', $nodeBackupGroupId))->allowedFilters(['name'])->paginate(10),
        ]);
    }

    public function editNodeBackupGroup(int $nodeBackupGroupId): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        return view('admin.node_backup.group.edit', [
            'backup_group' => NodeBackupGroup::query()->findOrFail($nodeBackupGroupId),
            'locations' => Location::all(),
            's3_servers' => NodeBackupS3Server::all(),
        ]);
    }

    public function updateNodeBackupGroup(UpdateNodeBackupGroupRequest $request, int $nodeBackupGroupId): \Illuminate\Http\RedirectResponse
    {
        try {
            $nodeBackupGroup = NodeBackupGroup::query()
                ->findOrFail($nodeBackupGroupId);

            if ($nodeBackupGroup->to_be_deleted) {
                $this->alert->danger('Cannot update Node Backup Group because it is marked to be deleted.')->flash();

                return redirect()->route('admin.node-backup.group.edit', $nodeBackupGroupId);
            }

            $nodeBackupServers = NodeBackupServer::query()
                ->whereIn('node_backup_id', NodeBackup::query()->where('node_backup_group_id', $nodeBackupGroupId)->pluck('id')->toArray())
                ->get();

            if (
                $nodeBackupServers
                ->whereNull('completed_at')
                ->count() > 0
            ) {
                $this->alert->danger('Cannot update Node Backup Group because it has a Node Backup in progress.')->flash();

                return redirect()->route('admin.node-backup.group.edit', $nodeBackupGroupId);
            }

            if (
                $nodeBackupServers
                ->whereNotNull('restoration_started_at')
                ->whereNull('restoration_completed_at')
                ->count() > 0
            ) {
                $this->alert->danger('Cannot update Node Backup Group because it has a Node Backup that is being restored.')->flash();

                return redirect()->route('admin.node-backup.group.edit', $nodeBackupGroupId);
            }

            $data = $this->getNormalizedBackupGroup($request);

            if ($nodeBackupGroup->s3_server_id !== $data['s3_server_id']) {
                if ($data['s3_server_id'] === '0' && $nodeBackupServers->where('disk', Backup::ADAPTER_AWS_S3)->count() > 0) {
                    $this->alert->danger('Cannot update Node Backup Group because it has a Node Backup that uses S3.')->flash();

                    return redirect()->route('admin.node-backup.group.edit', $nodeBackupGroupId);
                } elseif ($data['s3_server_id'] !== '0' && $nodeBackupServers->where('disk', Backup::ADAPTER_WINGS)->count() > 0) {
                    $this->alert->danger('Cannot update Node Backup Group because it has a Node Backup that does not use S3.')->flash();

                    return redirect()->route('admin.node-backup.group.edit', $nodeBackupGroupId);
                }
            }

            $nodeBackupGroup->update($data);

            $this->alert->success('Node Backup Group has been updated successfully.')->flash();

            return redirect()->route('admin.node-backup.group.edit', $nodeBackupGroupId);
        } catch (\Exception $e) {
            $this->alert->danger($e->getMessage())->flash();

            return redirect()->route('admin.node-backup.group.edit', $nodeBackupGroupId);
        }
    }

    public function destroyNodeBackupGroup(int $nodeBackupGroupId): \Illuminate\Http\RedirectResponse
    {
        try {
            $nodeBackupGroup = NodeBackupGroup::query()
                ->findOrFail($nodeBackupGroupId);

            if ($nodeBackupGroup->to_be_deleted) {
                $this->alert->danger('Cannot delete Node Backup Group because it is already marked to be deleted.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            $nodeBackupServers = NodeBackupServer::query()
                ->whereIn('node_backup_id', NodeBackup::query()->where('node_backup_group_id', $nodeBackupGroupId)->pluck('id')->toArray())
                ->get();

            if (
                $nodeBackupServers
                ->whereNull('completed_at')
                ->count() > 0
            ) {
                $this->alert->danger('Cannot delete Node Backup Group because it has a Node Backup Server in progress.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            if (
                $nodeBackupServers
                ->whereNotNull('restoration_started_at')
                ->whereNull('restoration_completed_at')
                ->count() > 0
            ) {
                $this->alert->danger('Cannot delete Node Backup Group because it has a Node Backup Server that is being restored.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            $nodeBackupGroup->update([
                'to_be_deleted' => true,
            ]);

            $this->alert->success('Node Backup Group will be deleted shortly.')->flash();

            return redirect()->route('admin.node-backup');
        } catch (\Exception $e) {
            $this->alert->danger($e->getMessage())->flash();

            return redirect()->route('admin.node-backup');
        }
    }

    public function createNodeBackup(int $nodeBackupGroupId): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        return view('admin.node_backup.group.backup.new', [
            'backup_group' => NodeBackupGroup::query()->findOrFail($nodeBackupGroupId),
        ]);
    }

    public function storeNodeBackup(StoreNodeBackupRequest $request, int $nodeBackupGroupId): \Illuminate\Http\RedirectResponse
    {
        try {
            $nodeBackupGroup = NodeBackupGroup::query()
                ->findOrFail($nodeBackupGroupId);

            if ($nodeBackupGroup->to_be_deleted) {
                $this->alert->danger('Cannot create a Node Backup because the group is marked to be deleted.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            if ($nodeBackupGroup->isProcessing()) {
                $this->alert->danger('Cannot create a Node Backup because one is already being made.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            if ($nodeBackupGroup->servers()->count() === 0) {
                $this->alert->danger('Cannot create a Node Backup because there are no servers to be backed up in the group.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            $data = $request->normalize();

            $data['node_backup_group_id'] = $nodeBackupGroupId;
            $data['uuid'] = NodeBackup::generateUniqueUuid();

            $nodeBackup = NodeBackup::query()
                ->create($data);

            $this->nodeBackupService->handleCreation($nodeBackup);

            $this->alert->success('Node Backup has been created successfully.')->flash();

            return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackup->id]);
        } catch (\Exception $e) {
            $this->alert->danger($e->getMessage())->flash();

            return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
        }
    }

    public function viewNodeBackup(int $nodeBackupGroupId, int $nodeBackupId): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        return view('admin.node_backup.group.backup.view', [
            'locations' => Location::all(),
            'backup_group' => NodeBackupGroup::query()->findOrFail($nodeBackupGroupId),
            'node_backup' => NodeBackup::query()->where('node_backup_group_id', $nodeBackupGroupId)->findOrFail($nodeBackupId),
            'node_backup_servers' => QueryBuilder::for(NodeBackupServer::query()->where('node_backup_id', $nodeBackupId))->allowedFilters([
                AllowedFilter::custom('server', new class () implements Filter {
                    public function __invoke(Builder $query, $value, string $property)
                    {
                        $servers = Server::query()
                            ->where('name', 'LIKE', "%{$value}%")
                            ->orWhere('uuid', 'LIKE', "%{$value}%")
                            ->get();

                        if ($servers->count() > 0) {
                            $query->whereIn('server_id', $servers->pluck('id')->toArray());

                            return;
                        }

                        $query->where('server_id', $value);
                    }
                }),
            ])->paginate(30),
        ]);
    }

    public function restoreNodeBackup(Request $request, int $nodeBackupGroupId, int $nodeBackupId): \Illuminate\Http\RedirectResponse
    {
        try {
            $nodeBackupGroup = NodeBackupGroup::query()
                ->findOrFail($nodeBackupGroupId);

            if ($nodeBackupGroup->to_be_deleted) {
                $this->alert->danger('Cannot restore the Node Backup because the group is marked to be deleted.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            $nodeBackup = NodeBackup::query()
                ->findOrFail($nodeBackupId);

            if ($nodeBackup->to_be_deleted) {
                $this->alert->danger('Cannot restore the Node Backup because it is marked to be deleted.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            }

            $nodeBackupServers = NodeBackupServer::query()
                ->where('node_backup_id', $nodeBackupId)
                ->get();

            if (
                $nodeBackupServers
                ->whereNull('completed_at')
                ->count() > 0
            ) {
                $this->alert->danger('Cannot restore Node Backup because it has a Node-Server-Backup in progress.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            if (
                $nodeBackupServers
                ->whereNotNull('restoration_started_at')
                ->whereNull('restoration_completed_at')
                ->count() > 0
            ) {
                $this->alert->danger('Cannot restore Node Backup because it has a Node-Server-Backup that is being restored.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            NodeBackupServer::query()
                ->whereIn('id', $nodeBackupServers->pluck('id'))
                ->update([
                    'restoration_type' => NodeBackupServer::RESTORATION_TYPE_CLASSIC,
                    'restoration_node_id' => null,
                    'restoration_started_at' => null,
                    'restoration_completed_at' => null,
                ]);

            $this->nodeBackupService->handleNodeBackupServersToRestore(
                $nodeBackupGroup,
                NodeBackupServer::query()
                    ->where('node_backup_id', $nodeBackupId)
                    ->get()
            );

            $this->alert->success('Node Backup has been restored successfully to the nodes and servers of the group.')->flash();

            return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
        } catch (\Exception $e) {
            $this->alert->danger($e->getMessage())->flash();

            return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
        }
    }

    public function restoreNodeBackupOnAnotherNode(Request $request, int $nodeBackupGroupId, int $nodeBackupId, int $nodeId): \Illuminate\Http\RedirectResponse
    {
        try {
            $nodeBackupGroup = NodeBackupGroup::query()
                ->findOrFail($nodeBackupGroupId);

            if ($nodeBackupGroup->to_be_deleted) {
                $this->alert->danger('Cannot restore the Node Backup because the group is marked to be deleted.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            $nodeBackup = NodeBackup::query()
                ->findOrFail($nodeBackupId);

            if ($nodeBackup->to_be_deleted) {
                $this->alert->danger('Cannot restore the Node Backup because it is marked to be deleted.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            }

            $nodeBackupServers = NodeBackupServer::query()
                ->where('node_backup_id', $nodeBackupId)
                ->get();

            if ($nodeBackupServers->count() === 0) {
                $this->alert->danger('Cannot restore Node Backup because there are no Node-Server-Backups.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            if (
                $nodeBackupServers
                ->whereNull('completed_at')
                ->count() > 0
            ) {
                $this->alert->danger('Cannot restore Node Backup because it has a Node-Server-Backup in progress.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            if (
                $nodeBackupServers
                ->whereNotNull('restoration_started_at')
                ->whereNull('restoration_completed_at')
                ->count() > 0
            ) {
                $this->alert->danger('Cannot restore Node Backup because it has a Node-Server-Backup that is being restored.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            $node = Node::query()
                ->findOrFail($nodeId);

            foreach ($nodeBackupServers as $nodeBackupServer) {
                if ($nodeBackupServer->server()->node->id === $nodeId) {
                    $this->alert->danger('Cannot restore Node Backup because one backup is already on the node <code>' . $node->name . '</code>.')->flash();

                    return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
                }
            }

            if ($nodeBackupServers->where('disk', Backup::ADAPTER_WINGS)->count() > 0) {
                $this->alert->danger('Cannot restore Node Backup because it has a Node-Server-Backup that does not use S3.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            NodeBackupServer::query()
                ->whereIn('id', $nodeBackupServers->pluck('id'))
                ->update([
                    'restoration_type' => NodeBackupServer::RESTORATION_TYPE_RECREATE,
                    'restoration_node_id' => $node->id,
                    'restoration_started_at' => null,
                    'restoration_completed_at' => null,
                ]);

            $this->nodeBackupService->handleNodeBackupServersToRestore($nodeBackupGroup, NodeBackupServer::query()
                ->where('node_backup_id', $nodeBackupId)
                ->get());

            $this->alert->success('Node Backup has been restored successfully to the nodes and servers of the group.')->flash();

            return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
        } catch (\Exception $e) {
            $this->alert->danger($e->getMessage())->flash();

            return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
        }
    }

    public function stopNodeBackup(int $nodeBackupGroupId, int $nodeBackupId): \Illuminate\Http\RedirectResponse
    {
        try {
            $nodeBackup = NodeBackup::query()
                ->findOrFail($nodeBackupId);

            if ($nodeBackup->getCompletedAt() != null) {
                $this->alert->danger('Cannot stop Node Backup because it is already done.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            NodeBackupServer::query()
                ->where('node_backup_id', $nodeBackupId)
                ->whereNull('started_at')
                ->orWhereNull('completed_at')
                ->where('is_successful', false)
                ->update([
                    'completed_at' => Carbon::now(),
                ]);

            $this->alert->success('Node Backup has been stopped successfully.')->flash();

            return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
        } catch (\Exception $e) {
            $this->alert->danger($e->getMessage())->flash();

            return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
        }
    }

    public function tryAgainNodeBackup(int $nodeBackupGroupId, int $nodeBackupId): \Illuminate\Http\RedirectResponse
    {
        try {
            $nodeBackupGroup = NodeBackupGroup::query()
                ->findOrFail($nodeBackupGroupId);

            $nodeBackupServersQuery = NodeBackupServer::query()
                ->where('node_backup_id', $nodeBackupId)
                ->where('is_successful', false)
                ->whereNotNull('started_at')
                ->whereNotNull('completed_at');

            $nodeBackupServers = $nodeBackupServersQuery->get();

            if ($nodeBackupServers->count() === 0) {
                $this->alert->danger('Cannot try again Node Backup because there is no failed Node-Server-Backups.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            $nodeBackupServersQuery->update([
                'upload_id' => null,
                'checksum' => null,
                'bytes' => 0,
                'is_successful' => false,
                'completed_at' => null,
                'started_at' => null,
            ]);

            $this->nodeBackupService->processNodeBackupGroupsToSave(
                $nodeBackupGroup,
                NodeBackupServer::query()
                    ->where('node_backup_id', $nodeBackupId)
                    ->get()
            );

            $this->alert->success('Node Backup will be tried again shortly.')->flash();

            return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
        } catch (\Exception $e) {
            $this->alert->danger($e->getMessage())->flash();

            return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
        }
    }

    public function destroyNodeBackup(int $nodeBackupGroupId, int $nodeBackupId): \Illuminate\Http\RedirectResponse
    {
        try {
            $nodeBackupGroup = NodeBackupGroup::query()
                ->findOrFail($nodeBackupGroupId);

            if ($nodeBackupGroup->to_be_deleted) {
                $this->alert->danger('Cannot delete Node Backup because the group is marked to be deleted.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            $nodeBackup = NodeBackup::query()
                ->findOrFail($nodeBackupId);

            if ($nodeBackup->to_be_deleted) {
                $this->alert->danger('Cannot delete Node Backup because it is already marked to be deleted.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            $nodeBackupServers = NodeBackupServer::query()
                ->where('node_backup_id', $nodeBackupId)
                ->get();

            if (
                $nodeBackupServers
                ->whereNull('completed_at')
                ->count() > 0
            ) {
                $this->alert->danger('Cannot delete Node Backup because it has a Node-Server-Backup in progress.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            if (
                $nodeBackupServers
                ->whereNotNull('restoration_started_at')
                ->whereNull('restoration_completed_at')
                ->count() > 0
            ) {
                $this->alert->danger('Cannot delete Node Backup because it has a Node-Server-Backup that is being restored.')->flash();

                return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
            }

            $nodeBackup->update([
                'to_be_deleted' => true,
            ]);

            $this->alert->success('Node Backup will be deleted shortly.')->flash();

            return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
        } catch (\Exception $e) {
            $this->alert->danger($e->getMessage())->flash();

            return redirect()->route('admin.node-backup.group.view', $nodeBackupGroupId);
        }
    }

    public function tryAgainNodeBackupServer(int $nodeBackupGroupId, int $nodeBackupId, int $nodeBackupServerId): \Illuminate\Http\RedirectResponse
    {
        try {
            $nodeBackupGroup = NodeBackupGroup::query()
                ->findOrFail($nodeBackupGroupId);

            if ($nodeBackupGroup->to_be_deleted) {
                $this->alert->danger('Cannot try again this Node-Server-Backup because the group is marked to be deleted.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            }

            $nodeBackup = NodeBackup::query()
                ->findOrFail($nodeBackupId);

            if ($nodeBackup->to_be_deleted) {
                $this->alert->danger('Cannot try again this Node-Server-Backup because the Node Backup is marked to be deleted.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            }

            $nodeBackupServer = NodeBackupServer::query()
                ->findOrFail($nodeBackupServerId);

            if ($nodeBackupServer->isSuccessful()) {
                $this->alert->danger('Cannot try again this Node-Server-Backup because it is already successful.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            } elseif ($nodeBackupServer->inProgress()) {
                $this->alert->danger('Cannot try again this Node-Server-Backup because it is already in progress.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            } elseif ($nodeBackupServer->isRestoring()) {
                $this->alert->danger('Cannot try again this Node-Server-Backup because it is already being restored.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            }

            $nodeBackupServer->update([
                'is_successful' => false,
                'completed_at' => null,
                'started_at' => null,
            ]);

            $this->nodeBackupService->handleServerBackupCreation($nodeBackupGroup, $nodeBackupServer);

            $this->alert->success('Node-Server-Backup has been started again successfully.')->flash();

            return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
        } catch (\Exception $e) {
            $this->alert->danger($e->getMessage())->flash();

            return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
        }
    }

    public function downloadNodeBackupServer(Request $request, int $nodeBackupGroupId, int $nodeBackupId, int $nodeBackupServerId): \Illuminate\Http\RedirectResponse
    {
        try {
            $nodeBackupServer = NodeBackupServer::query()
                ->findOrFail($nodeBackupServerId);

            if (!$nodeBackupServer->isSuccessful()) {
                $this->alert->danger('Cannot download this Node-Server-Backup because it is not successful.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            } elseif ($nodeBackupServer->inProgress()) {
                $this->alert->danger('Cannot download this Node-Server-Backup because it is in progress.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            }

            $downloadUrl = $this->nodeBackupService->handleDownload($nodeBackupServer, $request->user());

            return redirect()->to($downloadUrl);
        } catch (\Exception $e) {
            $this->alert->danger($e->getMessage())->flash();

            return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
        }
    }

    public function restoreNodeBackupServer(Request $request, int $nodeBackupGroupId, int $nodeBackupId, int $nodeBackupServerId)
    {
        try {
            $nodeBackupGroup = NodeBackupGroup::query()
                ->findOrFail($nodeBackupGroupId);

            if ($nodeBackupGroup->to_be_deleted) {
                $this->alert->danger('Cannot restore this Node-Server-Backup because the group is marked to be deleted.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            }

            $nodeBackup = NodeBackup::query()
                ->findOrFail($nodeBackupId);

            if ($nodeBackup->to_be_deleted) {
                $this->alert->danger('Cannot restore this Node-Server-Backup because the Node Backup is marked to be deleted.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            }

            $nodeBackupServer = NodeBackupServer::query()
                ->findOrFail($nodeBackupServerId);

            if (!$nodeBackupServer->isSuccessful()) {
                $this->alert->danger('Cannot restore this Node-Server-Backup because it is not successful.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            } elseif ($nodeBackupServer->inProgress()) {
                $this->alert->danger('Cannot restore this Node-Server-Backup because it is in progress.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            }

            $server = $nodeBackupServer->server();
            if ($server->status !== null) {
                $this->alert->danger('Cannot restore this Node-Server-Backup because the server is in status <code>' . $server->status . '</code>.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            }

            $this->nodeBackupService->handleRestore($nodeBackupServer, $request->user());

            $this->alert->success('Node Backup Server has been restored successfully.')->flash();
        } catch (\Exception $e) {
            $this->alert->danger($e->getMessage())->flash();
        }

        return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
    }

    public function restoreNodeBackupServerOnAnotherNode(int $nodeBackupGroupId, int $nodeBackupId, int $nodeBackupServerId, int $nodeId): \Illuminate\Http\RedirectResponse
    {
        try {
            $nodeBackupGroup = NodeBackupGroup::query()
                ->findOrFail($nodeBackupGroupId);

            if ($nodeBackupGroup->to_be_deleted) {
                $this->alert->danger('Cannot restore this Node-Server-Backup because the group is marked to be deleted.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            }

            $nodeBackup = NodeBackup::query()
                ->findOrFail($nodeBackupId);

            if ($nodeBackup->to_be_deleted) {
                $this->alert->danger('Cannot restore this Node-Server-Backup because the Node Backup is marked to be deleted.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            }

            $nodeBackupServer = NodeBackupServer::query()
                ->findOrFail($nodeBackupServerId);

            if (!$nodeBackupServer->isSuccessful()) {
                $this->alert->danger('Cannot restore this Node-Server-Backup because it is not successful.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            } elseif ($nodeBackupServer->inProgress()) {
                $this->alert->danger('Cannot restore this Node-Server-Backup because it is in progress.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            }

            if ($nodeBackupServer->disk !== Backup::ADAPTER_AWS_S3) {
                $this->alert->danger('Cannot restore this Node-Server-Backup because it is not on AWS S3.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            }

            $server = $nodeBackupServer->server();

            if ($server->status !== null) {
                $this->alert->danger('Cannot restore this Node-Server-Backup because the server is in status <code>' . $server->status . '</code>.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            }

            $oldNode = $server->node;

            if ($oldNode->id === $nodeId) {
                $this->alert->danger('Cannot restore this Node-Server-Backup because the server is already on the node <code>' . $oldNode->name . '</code>.')->flash();

                return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
            }

            $newNode = Node::query()
                ->findOrFail($nodeId);

            $this->nodeBackupService->handleRestoreOnNewNode($nodeBackupServer, $newNode);

            $this->alert->success('Node Backup Server restoration has been started.')->flash();
        } catch (\Exception $e) {
            $this->alert->danger($e->getMessage())->flash();
        }

        return redirect()->route('admin.node-backup.group.backup.view', [$nodeBackupGroupId, $nodeBackupId]);
    }

    private function getNormalizedBackupGroup(Request $request): array
    {
        $data = $request->normalize();

        $data['nodes_id'] = array_map('intval', $data['nodes_id']);

        $data['next_run_at'] = Carbon::instance((new CronExpression(
            sprintf('%s %s %s %s %s', $data['cron_minute'], $data['cron_hour'], $data['cron_day_of_month'], $data['cron_month'], $data['cron_day_of_week'])
        ))->getNextRunDate());

        foreach ($data['nodes_id'] as $nodeId) {
            Node::query()->findOrFail($nodeId);
        }

        if (array_key_exists('ignored_files', $data)) {
            $ignoredFiles = array_filter(explode(PHP_EOL, $data['ignored_files']), function ($ignoredFile) {
                return strlen($ignoredFile) > 0;
            });

            foreach ($ignoredFiles as $ignoredFile) {
                Assert::string($ignoredFile);
            }

            $data['ignored_files'] = implode(PHP_EOL, $ignoredFiles);
        }

        return $data;
    }
}
