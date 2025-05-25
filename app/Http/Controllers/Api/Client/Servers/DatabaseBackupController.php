<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Database;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Services\Backups\Databases\CreateDatabaseBackupService;
use Pterodactyl\Services\Backups\Databases\DeleteDatabaseBackupService;
use Pterodactyl\Services\Backups\Databases\DeployDatabaseBackupService;
use Pterodactyl\Http\Requests\Api\Client\Servers\Databases\ManageDatabaseBackupRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends ClientApiController
{
    /**
     * @param CreateDatabaseBackupService $createDatabaseBackupService
     * @param DeployDatabaseBackupService $deployDatabaseBackupService
     */
    public function __construct(private CreateDatabaseBackupService $createDatabaseBackupService, private DeployDatabaseBackupService $deployDatabaseBackupService)
    {
        parent::__construct();
    }

    /**
     * @param ManageDatabaseBackupRequest $request
     * @param Server $server
     * @return array
     */
    public function index(ManageDatabaseBackupRequest $request, Server $server)
    {
        return [
            'success' => true,
            'data' => [
                'limit' => $server->database_backup_limit,
                'backups' => DB::table('database_backups')
                    ->select(['database_backups.*', 'databases.database as database'])
                    ->leftJoin('databases', 'databases.id', '=', 'database_backups.database_id')
                    ->where('database_backups.server_id', '=', $server->id)
                    ->get(),
                'databases' => $server->databases()->select(['id', 'database'])->get(),
            ],
        ];
    }

    /**
     * @param ManageDatabaseBackupRequest $request
     * @param Server $server
     * @return array
     * @throws DisplayException
     */
    public function create(ManageDatabaseBackupRequest $request, Server $server)
    {
        $backups = DB::table('database_backups')->where('server_id', '=', $server->id)->count();
        if ($backups >= $server->database_backup_limit) {
            throw new DisplayException('You can\'t create more database backups.');
        }

        $database = Database::where('id', '=', (int) $request->input('database', 0))->where('server_id', '=', $server->id)->first();
        if (!$database) {
            throw new DisplayException('Database not found.');
        }

        if (!$this->createDatabaseBackupService->startBackup($database, trim(strip_tags($request->input('name', 'Default Backup'))), config('backups.database', 'panel'))['created']) {
            throw new DisplayException('Failed to make the backup.');
        }

        return ['success' => true, 'data' => []];
    }

    /**
     * @param ManageDatabaseBackupRequest $request
     * @param Server $server
     * @param $id
     * @return Application|RedirectResponse|Redirector|BinaryFileResponse
     * @throws DisplayException
     */
    public function download(ManageDatabaseBackupRequest $request, Server $server, $id)
    {
        $backup = DB::table('database_backups')->where('id', '=', (int) $id)->where('server_id', '=', $server->id)->first();
        if (!$backup) {
            throw new DisplayException('Backup not found.');
        }

        if ($backup->driver == 's3') {
            return redirect(Storage::disk('s3')->temporaryUrl(sprintf('databases/%s', $backup->file), now()->addHour(), ['ResponseContentDisposition' => 'attachment']));
        }

        return response()->download(Storage::path(sprintf('databases/%s', $backup->file)));
    }

    /**
     * @param ManageDatabaseBackupRequest $request
     * @param Server $server
     * @param $id
     * @return array
     * @throws DisplayException
     */
    public function deploy(ManageDatabaseBackupRequest $request, Server $server, $id)
    {
        $backup = DB::table('database_backups')->where('id', '=', (int) $id)->where('server_id', '=', $server->id)->first();
        if (!$backup) {
            throw new DisplayException('Backup not found.');
        }

        $database = Database::where('id', '=', $backup->database_id)->where('server_id', '=', $server->id)->first();
        if (!$database) {
            throw new DisplayException('Database not found.');
        }

        try {
            if (!$this->deployDatabaseBackupService->deploy($database, $backup->id)) {
                throw new DisplayException('Failed to deploy the database.');
            }
        } catch (DisplayException|RecordNotFoundException $e) {
            throw new DisplayException('Failed to deploy the database.');
        }

        return ['success' => true, 'data' => []];
    }

    /**
     * @param ManageDatabaseBackupRequest $request
     * @param Server $server
     * @param $id
     * @return array
     * @throws DisplayException
     */
    public function delete(ManageDatabaseBackupRequest $request, Server $server, $id)
    {
        $backup = DB::table('database_backups')->where('id', '=', (int) $id)->where('server_id', '=', $server->id)->first();
        if (!$backup) {
            throw new DisplayException('Backup not found.');
        }

        if (!DeleteDatabaseBackupService::delete($backup->id)) {
            throw new DisplayException('Failed to delete the backup.');
        }

        return ['success' => true, 'data' => []];
    }
}
