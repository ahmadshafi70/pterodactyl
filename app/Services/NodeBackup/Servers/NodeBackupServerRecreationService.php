<?php

namespace Pterodactyl\Services\NodeBackup\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\Allocation;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;

class NodeBackupServerRecreationService
{
    public function __construct(
        private DaemonServerRepository $daemonServerRepository,
        private NodeBackupServerDeletionService $nodeBackupServerDeletionService,
    ) {
    }

    public function handle(Server $server): void
    {
        $this->createAllocations($server);

        try {
            $this->daemonServerRepository->setServer($server)->create(false);
        } catch (\Exception $exception) {
            $this->nodeBackupServerDeletionService->handle($server);
            Log::warning($exception);
        }
    }

    private function createAllocations(Server $server): void
    {
        $allocationsToCreate = Allocation::query()
            ->where('server_id', $server->id)->count();

        Allocation::query()
            ->where('server_id', $server->id)->update([
                'server_id' => null,
            ]);

        Allocation::query()
            ->where('node_id', $server->node_id)
            ->whereNull('server_id')
            ->limit($allocationsToCreate)
            ->update([
                'server_id' => $server->id,
            ]);

        $server->update([
            'allocation_id' => Allocation::query()
                ->where('server_id', $server->id)
                ->where('node_id', $server->node_id)
                ->firstOrFail()
                ->id,
        ]);
    }
}
