<?php

namespace Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Requests;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class SubdomainManagerUpdateRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return 'subdomains.update';
    }

    public function rules(): array
    {
        return [
            'allocation' => 'required|integer|exists:allocations,id',
        ];
    }
}
