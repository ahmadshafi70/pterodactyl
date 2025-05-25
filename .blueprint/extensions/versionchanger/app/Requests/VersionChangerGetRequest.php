<?php

namespace Pterodactyl\BlueprintFramework\Extensions\versionchanger\Requests;

use Pterodactyl\Models\Permission;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class VersionChangerGetRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return Permission::ACTION_FILE_READ_CONTENT;
    }
}
