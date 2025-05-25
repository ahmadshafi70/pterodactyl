<?php

namespace Pterodactyl\BlueprintFramework\Extensions\subdomainmanager;

use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Allocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Models\Subdomain;
use Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Models\ServerSubdomain;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Client\BlueprintClientLibrary;
use Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Transformers\SubdomainTransformer;
use Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Requests\SubdomainManagerStoreRequest;
use Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Requests\SubdomainManagerIndexRequest;
use Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Requests\SubdomainManagerUpdateRequest;
use Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Requests\SubdomainManagerDestroyRequest;

class SubdomainManagerController extends ClientApiController
{
    public function __construct(
        private BlueprintClientLibrary $blueprint,
        private SubdomainManagerUtils $utils,
    ) {
        parent::__construct();
    }

    private function usableDomains(Server $server): array
    {
        $subdomains = Subdomain::query()->whereJsonContains('eggs', [(string) $server->egg_id])->get();

        return $subdomains->map(function ($subdomain) {
            return [
                'id' => $subdomain->id,
                'domain' => $subdomain->domain,
            ];
        })->toArray();
    }

    public function index(SubdomainManagerIndexRequest $request, Server $server): array
    {
        $subdomains = $this->fractal->collection(ServerSubdomain::query()->where('server_id', $server->id)->get())
            ->transformWith($this->getTransformer(SubdomainTransformer::class))
            ->toArray()['data'];

        return [
            'limit' => (int) ($this->blueprint->dbGet('subdomainmanager', 'subdomain_limit') ?? 5),
            'domains' => $this->usableDomains($server),
            'subdomains' => $subdomains,
        ];
    }

    public function store(SubdomainManagerStoreRequest $request, Server $server): JsonResponse
    {
        $data = $request->validated();
        $limit = (int) ($this->blueprint->dbGet('subdomainmanager', 'subdomain_limit') ?? 5);

        /** @var Subdomain $subdomain */
        $subdomain = Subdomain::query()->where('id', $data['domain'])->whereJsonContains('eggs', [(string) $server->egg_id])->first();
        if (!$subdomain) {
            return new JsonResponse([
                'error' => 'Subdomain not found.',
            ], 404);
        }

        $count = ServerSubdomain::query()->where('server_id', $server->id)->count();
        if ($count >= $limit) {
            return new JsonResponse([
                'error' => 'Subdomain limit reached.',
            ], 400);
        }

        $regexes = $subdomain->disallowed_subdomains_regexes;
        for ($i = 0; $i < count($regexes); $i++) {
            if (!$regexes[$i]) {
                continue;
            }

            if (preg_match($regexes[$i], $data['subdomain'])) {
                return new JsonResponse([
                    'error' => 'Subdomain not allowed.',
                ], 400);
            }
        }

        $allocation = Allocation::query()->where('id', $data['allocation'])->where('server_id', $server->id)->first();
        if (!$allocation) {
            return new JsonResponse([
                'error' => 'Allocation not found.',
            ], 404);
        }

        try {
            DB::beginTransaction();

            /** @var ServerSubdomain $serverSubdomain */
            $serverSubdomain = ServerSubdomain::create([
                'server_id' => $server->id,
                'allocation_id' => $allocation->id,
                'subdomain_id' => $subdomain->id,
                'subdomain' => $data['subdomain'],
            ]);

            try {
                $this->utils->batch($subdomain->zone_id, json_decode($this->utils->placeholders(json_encode($subdomain->api_data), $data['subdomain'], $subdomain->domain, $serverSubdomain->id, $allocation), true));
            } catch (\Exception $exception) {
                $serverSubdomain->delete();
                Log::error($exception);

                throw $exception;
            }
        } catch (\Exception $exception) {
            DB::rollBack();

            return new JsonResponse([
                'error' => 'This subdomain is already in use.',
            ], 500);
        }

        DB::commit();

        Activity::event('server:subdomain.create')
            ->property([
                'subdomain' => $data['subdomain'],
                'domain' => $subdomain->domain,
                'allocation' => "{$allocation->alias}:{$allocation->port}",
            ])
            ->log();

        return new JsonResponse($this->fractal->item($serverSubdomain)
            ->transformWith($this->getTransformer(SubdomainTransformer::class))
            ->toArray(), 200);
    }

    public function update(SubdomainManagerUpdateRequest $request, Server $server, string $subdomain): JsonResponse
    {
        $data = $request->validated();
        /** @var ServerSubdomain $serverSubdomain */
        $serverSubdomain = ServerSubdomain::query()->where('server_id', $server->id)->where('id', (int) $subdomain)->first();
        if (!$serverSubdomain) {
            return new JsonResponse([
                'error' => 'Subdomain not found.',
            ], 404);
        }

        $allocation = Allocation::query()->where('id', $data['allocation'])->where('server_id', $server->id)->first();
        if (!$allocation) {
            return new JsonResponse([
                'error' => 'Allocation not found.',
            ], 404);
        }

        $records = $this->utils->listIds($serverSubdomain->domain->zone_id, $serverSubdomain->id);

        $this->utils->batch($serverSubdomain->domain->zone_id, array_merge(json_decode($this->utils->placeholders(json_encode($serverSubdomain->domain->api_data), $serverSubdomain->subdomain, $serverSubdomain->domain->domain, $serverSubdomain->id, $allocation), true), [
            'deletes' => array_map(
                fn ($id) => ['id' => $id],
                $records,
            )
        ]));

        $serverSubdomain->update([
            'allocation_id' => $allocation->id,
        ]);

        Activity::event('server:subdomain.update')
            ->property([
                'subdomain' => $serverSubdomain->subdomain,
                'domain' => $serverSubdomain->domain->domain,
                'allocation' => "{$allocation->alias}:{$allocation->port}",
            ])
            ->log();

        return new JsonResponse([], 204);
    }

    public function destroy(SubdomainManagerDestroyRequest $request, Server $server, string $subdomain)
    {
        /** @var ServerSubdomain $serverSubdomain */
        $serverSubdomain = ServerSubdomain::query()->where('server_id', $server->id)->where('id', (int) $subdomain)->first();
        if (!$serverSubdomain) {
            return new JsonResponse([
                'error' => 'Subdomain not found.',
            ], 404);
        }

        try {
            $records = $this->utils->listIds($serverSubdomain->domain->zone_id, $serverSubdomain->id);

            if (!empty($records)) {
                $this->utils->batch($serverSubdomain->domain->zone_id, [
                    'deletes' => array_map(
                        fn ($id) => ['id' => $id],
                        $records,
                    ),
                ]);
            }

            $serverSubdomain->delete();
        } catch (\Exception $exception) {
            return new JsonResponse([
                'error' => 'Failed to delete subdomain.',
            ], 500);
        }

        Activity::event('server:subdomain.delete')
            ->property([
                'subdomain' => $serverSubdomain->subdomain,
                'domain' => $serverSubdomain->domain->domain,
                'allocation' => $serverSubdomain->allocation ? "{$serverSubdomain->allocation->alias}:{$serverSubdomain->allocation->port}" : null,
            ])
            ->log();

        return new JsonResponse([], 204);
    }
}
