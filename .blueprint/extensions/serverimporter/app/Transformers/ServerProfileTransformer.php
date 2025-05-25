<?php

namespace Pterodactyl\BlueprintFramework\Extensions\serverimporter\Transformers;

use Pterodactyl\Transformers\Api\Client\BaseClientTransformer;
use Pterodactyl\BlueprintFramework\Extensions\serverimporter\Models\ImporterServerProfile;

class ServerProfileTransformer extends BaseClientTransformer
{
    public function getResourceName(): string
    {
        return 'importer_server_profile';
    }

    public function transform(ImporterServerProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'name' => $profile->name,
            'host' => $profile->host,
            'port' => $profile->port,
            'mode' => $profile->mode,
        ];
    }
}
