<?php

namespace Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Requests;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class SubdomainManagerStoreRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return 'subdomains.create';
    }

    public function rules(): array
    {
        return [
            'subdomain' => 'required|string|min:3|max:32',
            'domain' => 'required|integer|exists:subdomains,id',
            'allocation' => 'required|integer|exists:allocations,id',
        ];
    }
}
