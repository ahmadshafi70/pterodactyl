<?php

namespace Pterodactyl\Jobs\Server;

use Pterodactyl\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Models\Subdomain;
use Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\SubdomainManagerUtils;
use Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Models\ServerSubdomain;

class DeleteSubdomainsJob extends Job implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $serverSubdomainIds,
        public ?int $deleteSubdomainAfter = null,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(
        SubdomainManagerUtils $utils,
    ): void {
        foreach ($this->serverSubdomainIds as $serverSubdomainId) {
            /** @var ServerSubdomain $serverSubdomain */
            $serverSubdomain = ServerSubdomain::query()->where('id', $serverSubdomainId)->first();
            if (!$serverSubdomain) {
                continue;
            }

            $records = $utils->listIds($serverSubdomain->domain->zone_id, $serverSubdomain->id);

            if (!empty($records)) {
                $utils->batch(
                    $serverSubdomain->domain->zone_id,
                    [
                        'deletes' => array_map(
                            fn ($id) => ['id' => $id],
                            $records,
                        ),
                    ],
                );
            }

            ServerSubdomain::query()->where('id', $serverSubdomainId)->delete();
        }

        if ($this->deleteSubdomainAfter) {
            Subdomain::query()->where('id', $this->deleteSubdomainAfter)->delete();
        }

        $subdomainsWithoutParent = ServerSubdomain::query()->where('server_id', null)->get();
        foreach ($subdomainsWithoutParent as $subdomain) {
            $records = $utils->listIds($subdomain->zone_id, $subdomain->id);

            if (!empty($records)) {
                $utils->batch(
                    $subdomain->zone_id,
                    [
                        'deletes' => array_map(
                            fn ($id) => ['id' => $id],
                            $records,
                        ),
                    ],
                );
            }

            ServerSubdomain::query()->where('id', $subdomain->id)->delete();
        }
    }
}
