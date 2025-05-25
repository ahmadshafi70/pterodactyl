<?php

namespace Pterodactyl\Services\Backups\Databases;

use Pterodactyl\Models\Database;
use Illuminate\Support\Facades\DB;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Storage;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Repositories\Eloquent\DatabaseRepository;

class DeployDatabaseBackupService
{
    /**
     * @param DatabaseRepository $databaseRepository
     * @param DynamicDatabaseConnection $dynamicDatabaseConnection
     * @param Encrypter $encrypter
     */
    public function __construct(private DatabaseRepository $databaseRepository, private DynamicDatabaseConnection $dynamicDatabaseConnection, private Encrypter $encrypter)
    {
    }

    /**
     * @param Database $database
     * @param $backupId
     * @return bool
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function deploy(Database $database, $backupId)
    {
        $backup = DB::table('database_backups')->where('id', '=', $backupId)->first();
        if (!$backup) {
            return false;
        }

        // Drop the old database
        $this->dynamicDatabaseConnection->set('dynamic', $database->database_host_id);
        $this->databaseRepository->dropDatabase($database->database);
        $this->databaseRepository->createDatabase($database->database);
        $this->databaseRepository->assignUserToDatabase($database->database, $database->username, $database->remote);
        $this->databaseRepository->flush();

        // Download the backup file from s3 if needed
        if ($backup->driver == 's3') {
            // Get the content from s3
            $content = Storage::drive('s3')->get(sprintf('databases/%s', $backup->file));

            // Save to the local file
            Storage::disk('local')->put(sprintf('databases/%s', $backup->file), $content);
        }

        // Deploy the backup
        $output = null;
        $retVal = null;
        exec(sprintf('mysql --user=%s --port=%s --host=%s -p%s %s < %s', $database->host->username, $database->host->port, $database->host->host, $this->encrypter->decrypt($database->host->password), $database->database, Storage::path(sprintf('/databases/%s', $backup->file))), $output, $retVal);

        // Delete the backup file cache if driver is s3
        if ($backup->driver == 's3') {
            Storage::disk('local')->delete(sprintf('databases/%s', $backup->file));
        }

        return $retVal == 0;
    }
}
