<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Database;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Services\Databases\DatabasePasswordService;
use Pterodactyl\Transformers\Api\Client\DatabaseTransformer;
use Pterodactyl\Services\Databases\DatabaseManagementService;
use Pterodactyl\Services\Databases\DeployServerDatabaseService;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Databases\GetDatabasesRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Databases\StoreDatabaseRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Databases\DeleteDatabaseRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Databases\RotatePasswordRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\AutomaticPhpMyAdmin;
use Pterodactyl\Http\Requests\Api\Client\Servers\Databases\GetTokenDatabaseRequest;

class DatabaseController extends ClientApiController
{
    /**
     * DatabaseController constructor.
     */
    public function __construct(
        private DeployServerDatabaseService $deployDatabaseService,
        private DatabaseManagementService $managementService,
        private DatabasePasswordService $passwordService
    ) {
        parent::__construct();
    }

    /**
     * Return all the databases that belong to the given server.
     */
    public function index(GetDatabasesRequest $request, Server $server): array
    {
        return $this->fractal->collection($server->databases)
            ->transformWith($this->getTransformer(DatabaseTransformer::class))
            ->toArray();
    }

    /**
     * Create a new database for the given server and return it.
     *
     * @throws \Throwable
     * @throws \Pterodactyl\Exceptions\Service\Database\TooManyDatabasesException
     * @throws \Pterodactyl\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException
     */
    public function store(StoreDatabaseRequest $request, Server $server): array
    {
        $database = $this->deployDatabaseService->handle($server, $request->validated());

        Activity::event('server:database.create')
            ->subject($database)
            ->property('name', $database->database)
            ->log();

        return $this->fractal->item($database)
            ->parseIncludes(['password'])
            ->transformWith($this->getTransformer(DatabaseTransformer::class))
            ->toArray();
    }

    /**
     * Rotates the password for the given server model and returns a fresh instance to
     * the caller.
     *
     * @throws \Throwable
     */
    public function rotatePassword(RotatePasswordRequest $request, Server $server, Database $database): array
    {
        $this->passwordService->handle($database);
        $database->refresh();

        Activity::event('server:database.rotate-password')
            ->subject($database)
            ->property('name', $database->database)
            ->log();

        return $this->fractal->item($database)
            ->parseIncludes(['password'])
            ->transformWith($this->getTransformer(DatabaseTransformer::class))
            ->toArray();
    }

    /**
     * Removes a database from the server.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function delete(DeleteDatabaseRequest $request, Server $server, Database $database): Response
    {
        $this->managementService->delete($database);

        Activity::event('server:database.delete')
            ->subject($database)
            ->property('name', $database->database)
            ->log();

        return new Response('', Response::HTTP_NO_CONTENT);
    }
    /**
     * Create a token to use for connection with PhpMyAdmin.
     */
    public function getToken(GetTokenDatabaseRequest $request, Server $server, Database $database): JsonResource
    {
        try {
            $automatic_pma = AutomaticPhpMyAdmin::query()->where('linked_database_host', $database->database_host_id)->firstOrFail();
        } catch (\Exception $e) {
            $automatic_pma = AutomaticPhpMyAdmin::query()->where('linked_database_host', null)->firstOrFail();
        }

        if ($automatic_pma->linked_database_host == null) {
            $database_hosts_ids = DatabaseHost::all()->pluck('id')->toArray();
            $phpmyadmin_server_id = $automatic_pma->phpmyadmin_server_id + array_search($database->database_host_id, $database_hosts_ids);
        } else {
            $phpmyadmin_server_id = $automatic_pma->phpmyadmin_server_id;
        }

        $data = array(
            'phpmyadmin_server_id' => $phpmyadmin_server_id,
            'database_username' => $database->username,
            'database_password' => $this->managementService->getPasswordFromDatabase($database),
        );
        $encryption = openssl_encrypt(json_encode($data), "AES-128-CTR", $automatic_pma->encryption_key, 0, $automatic_pma->encryption_iv);

        return new JsonResource(['encryption' => $encryption, 'url' => $automatic_pma->url, 'cookie_domain' => $automatic_pma->cookie_domain, 'cookie_name' => $automatic_pma->cookie_name]);
    }
}
