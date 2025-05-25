<?php

namespace Pterodactyl\Jobs\Server;

use Pterodactyl\Jobs\Job;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Pterodactyl\Services\Servers\ReinstallServerService;

class ImportServerJob extends Job implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Server $server,
        public Egg $egg,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(
        ReinstallServerService $reinstallServerService,
    ): void {
        rescue(function () use ($reinstallServerService) {
            $reinstallServerService->handle($this->server);
        });

        sleep(7);

        $this->server->forceFill([
            'nest_id' => $this->egg->nest_id,
            'egg_id' => $this->egg->id,
        ])->save();
    }
}
