<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Databases;

use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class ManageDatabaseBackupRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return 'database.backup';
    }
}
