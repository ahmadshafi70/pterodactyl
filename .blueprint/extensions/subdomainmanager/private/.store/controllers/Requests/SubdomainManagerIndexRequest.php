<?php

namespace Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Requests;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class SubdomainManagerIndexRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return 'subdomains.read';
    }
}
