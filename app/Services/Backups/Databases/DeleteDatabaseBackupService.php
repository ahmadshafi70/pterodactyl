<?php

namespace Pterodactyl\Services\Backups\Databases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Pterodactyl\Exceptions\DisplayException;

class DeleteDatabaseBackupService
{
    /**
     * @param $backupId
     * @return bool
     */
    public static function delete($backupId)
    {
        $backup = DB::table('database_backups')->where('id', '=', $backupId)->first();
        if (!$backup) {
            return false;
        }

        if ($backup->driver == 's3') {
            Storage::disk('s3')->delete(sprintf('databases/%s', $backup->file));
        }  else {
            if (!Storage::exists(sprintf('databases/%s', $backup->file))) {
                return false;
            }

            Storage::delete(sprintf('databases/%s', $backup->file));
        }

        DB::table('database_backups')->where('id', '=', $backupId)->delete();

        return true;
    }
}
