<?php

namespace Pterodactyl\Services\Backups\Databases;

use Carbon\Carbon;
use Pterodactyl\Models\Database;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Encryption\Encrypter;

class CreateDatabaseBackupService
{
    /**
     * @param Encrypter $encrypter
     */
    public function __construct(private Encrypter $encrypter)
    {
    }

    /**
     * @param $options
     * @return array|void
     */
    private function processBackup($options)
    {
        // Delete old host file
        Storage::disk('local')->delete(sprintf('databases/host%s.cnf', $options['db_host_id']));

        // Save the pre-configured Host file
        $hostFile[] = '[mysqldump]';
        $hostFile[] = sprintf('user=%s', $options['db_uname']);
        $hostFile[] = sprintf('password="%s"', $options['db_password']);
        Storage::disk('local')->put(sprintf('databases/host%s.cnf', $options['db_host_id']), implode(PHP_EOL, $hostFile));

        // Generate the backup name
        $backup_file_name = sprintf('%s-%s.sql', date('Y-m-d--H:i:s'), $options['db_to_backup']);

        // Create the dump
        $mysqlDump = '$(which mysqldump)';
        $config = sprintf('--defaults-extra-file=%s -P %s -h %s ', sprintf('%s/host%s.cnf', Storage::path('databases'), $options['db_host_id']), $options['db_port'], $options['db_host']);
        $databases = sprintf('--databases %s', $options['db_to_backup']);
        $store = sprintf('--result-file %s/%s', Storage::path('databases'), $backup_file_name);

        // Process the dump
        $output = null;
        $retVal = null;
        exec("${mysqlDump} ${config} {$databases} ${store}", $output, $retVal);

        // Make sure dump is created
        if ($retVal != 0) {
            Storage::delete(sprintf('databases/%s', $backup_file_name));
        }

        // Delete the host file (security)
        Storage::disk('local')->delete(sprintf('databases/host%s.cnf', $options['db_host_id']));

        // Transfer to S3 if needed
        if ($options['driver'] == 's3') {
            // Fetch the current file
            $content = Storage::disk('local')->get(sprintf('databases/%s', $backup_file_name));

            // Upload it to S3
            Storage::disk('s3')->put(sprintf('databases/%s', $backup_file_name), $content);

            // Delete the local
            Storage::disk('local')->delete(sprintf('databases/%s', $backup_file_name));
        }

        return [
            'created' => $retVal == 0,
            'name' => $backup_file_name,
        ];
    }

    /**
     * @param Database $database
     * @param $name
     * @return array|void
     */
    public function startBackup(Database $database, $name, $driver)
    {
        $host = $database->host()->first();

        $options = [
            'db_host_id' => $host->id,
            'db_host' => $host->host,
            'db_uname' => $host->username,
            'db_port' => $host->port,
            'db_password' => $this->encrypter->decrypt($host->password),
            'db_to_backup' => $database->database,
            'driver' => $driver,
        ];

        $create = $this->processBackup($options);

        if ($create['created']) {
            DB::table('database_backups')->insert([
                'server_id' => $database->server_id,
                'database_id' => $database->id,
                'name' => $name,
                'file' => $create['name'],
                'driver' => $driver,
                'created_at' => Carbon::now(),
            ]);
        }

        return $create;
    }
}
