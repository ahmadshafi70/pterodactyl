<?php

namespace Pterodactyl\BlueprintFramework\Extensions\serverimporter\Requests;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class ServerImporterProfilesRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return 'file.delete';
    }
}
