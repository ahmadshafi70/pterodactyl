<?php

namespace Pterodactyl\Services\NodeBackup\Servers;

use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class NodeBackupServerDeletionService
{
    public function __construct(
        private DaemonServerRepository $daemonServerRepository,
    ) {
    }

    public function handle(Server $server): void
    {
        try {
            $this->daemonServerRepository->setServer($server)->delete();
        } catch (DaemonConnectionException $exception) {
            Log::warning($exception);
        }
    }
}
