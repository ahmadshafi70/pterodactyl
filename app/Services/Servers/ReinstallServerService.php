<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Models\User;

class ReinstallServerService
{
    /**
     * ReinstallService constructor.
     */
    public function __construct(
        private ConnectionInterface $connection,
        private StartupModificationService $startupModificationService,
        private DaemonServerRepository $daemonServerRepository
    ) {
    }

    /**
     * Reinstall a server on the remote daemon.
     *
     * @throws \Throwable
     */
    public function handle(Server $server): Server
    {
        // Restore the original startup command
        $this->startupModificationService->setUserLevel(User::USER_LEVEL_ADMIN);
        $this->startupModificationService->handle($server, [
            'startup' => $server->egg->startup,
        ]);

        // Process the reinstall
        return $this->connection->transaction(function () use ($server) {
            $server->fill(['status' => Server::STATUS_INSTALLING])->save();

            $this->daemonServerRepository->setServer($server)->reinstall();

            return $server->refresh();
        });
    }
}
