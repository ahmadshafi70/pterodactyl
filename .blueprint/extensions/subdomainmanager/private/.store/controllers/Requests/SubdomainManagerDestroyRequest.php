<?php

namespace Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Requests;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class SubdomainManagerDestroyRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return 'subdomains.delete';
    }
}
