<?php

namespace Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Transformers;

use Pterodactyl\Transformers\Api\Client\AllocationTransformer;
use Pterodactyl\Transformers\Api\Client\BaseClientTransformer;
use Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Models\ServerSubdomain;

class SubdomainTransformer extends BaseClientTransformer
{
    public function getResourceName(): string
    {
        return 'server_subdomain';
    }

    public function transform(ServerSubdomain $subdomain): array
    {
        return [
            'id' => $subdomain->id,
            'subdomain' => $subdomain->subdomain,
            'domain' => $subdomain->domain->domain,
            'allocation' => $subdomain->allocation && $subdomain->allocation->server_id === $subdomain->server_id
                ? $this->makeTransformer(AllocationTransformer::class)->transform($subdomain->allocation)
                : null,
            'created_at' => $subdomain->created_at->toAtomString(),
        ];
    }
}
