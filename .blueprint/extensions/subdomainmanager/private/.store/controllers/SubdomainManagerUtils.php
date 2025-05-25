<?php

namespace Pterodactyl\BlueprintFramework\Extensions\subdomainmanager;

use Pterodactyl\Models\Allocation;
use Illuminate\Support\Facades\Http;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Client\BlueprintClientLibrary;

class SubdomainManagerUtils
{
    public function __construct(
        private BlueprintClientLibrary $blueprint,
    ) {
    }

    public function client()
    {
        $token = $this->blueprint->dbGet('subdomainmanager', 'cloudflare_token');

        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
    }

    public function test(): bool
    {
        $req = $this->client()->get('https://api.cloudflare.com/client/v4/zones');

        return $req->ok();
    }

    public function placeholders(string $text, string $subdomain, string $domain, int $serverSubdomainId, Allocation $allocation): string
    {
        $data = [
            '{subdomain}' => $subdomain,
            '{domain}' => $domain,
            '{ip_alias_forceip}' => gethostbyname($allocation->ip_alias ?? $allocation->ip),
            '{ip_alias}' => $allocation->ip_alias ?? $allocation->ip,
            '{ip}' => $allocation->ip,
            '{port}' => (string) $allocation->port,
            '{comment}' => $this->getComment($serverSubdomainId),
        ];

        return str_replace(array_keys($data), array_values($data), $text);
    }

    public function getComment(int $serverSubdomainId): string
    {
        return "subdomainmanager.subdomain_id=$serverSubdomainId";
    }

    public function batch(string $zoneId, array $records)
    {
        $res = $this->client()->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records/batch", $records);

        if (!$res->ok()) {
            throw new \Exception("Failed to batch update DNS records: {$res->body()}");
        }

        return $res->json();
    }

    public function listIds(string $zoneId, int $serverSubdomainId): array
    {
        $res = $this->client()->get("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records", [
            'comment' => $this->getComment($serverSubdomainId),
        ]);

        if (!$res->ok()) {
            throw new \Exception("Failed to list DNS records: {$res->body()}");
        }

        return array_map(fn($record) => $record['id'], $res->json()['result']);
    }
}
