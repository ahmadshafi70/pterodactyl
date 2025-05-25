<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Servers\StartupModificationService;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Startup\GetStartupRequest;

class StartupChangeController extends ClientApiController
{
    /**
     * @param StartupModificationService $startupModificationService
     */
    public function __construct(private StartupModificationService $startupModificationService)
    {
        parent::__construct();
    }

    /**
     * @param GetStartupRequest $request
     * @param Server $server
     * @return array
     */
    public function index(GetStartupRequest $request, Server $server)
    {
        return [
            'success' => true,
            'data' => [
                'startup' => $server->startup,
            ],
        ];
    }

    /**
     * @param GetStartupRequest $request
     * @param Server $server
     * @return true[]
     * @throws DisplayException
     */
    public function change(GetStartupRequest $request, Server $server)
    {
        $this->validate($request, [
            'startup' => ['required', 'string'],
        ]);

        foreach ($server->egg->variables as $variable) {
            if (str_contains($variable->name, 'required')) {
                if (!str_contains($request->input('startup', $server->egg->startup), sprintf('{{%s}}', $variable->env_variable))) {
                    throw new DisplayException(sprintf('{{%s}} variable missing from the startup command.', $variable->env_variable));
                }
            }
        }

        $this->startupModificationService->setUserLevel(User::USER_LEVEL_ADMIN);

        try {
            $this->startupModificationService->handle($server, [
                'startup' => trim($request->input('startup', $server->egg->startup)),
            ]);
        } catch (\Throwable $e) {
            throw new DisplayException('Failed to run the startup command change.');
        }

        return ['success' => true];
    }

    /**
     * @param GetStartupRequest $request
     * @param Server $server
     * @return true[]
     * @throws DisplayException
     */
    public function default(GetStartupRequest $request, Server $server)
    {
        $this->startupModificationService->setUserLevel(User::USER_LEVEL_ADMIN);

        try {
            $this->startupModificationService->handle($server, [
                'startup' => $server->egg->startup,
            ]);
        } catch (\Throwable $e) {
            throw new DisplayException('Failed to restore the startup command to the original.');
        }

        return ['success' => true];
    }
}
