<?php

namespace Pterodactyl\Jobs\Server;

use Pterodactyl\Jobs\Job;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Services\Minecraft\MinecraftSoftwareService;
use Pterodactyl\Services\Servers\ReinstallServerService;
use Pterodactyl\Services\Servers\StartupModificationService;

class InstallModpackJob extends Job implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Server $server,
        public string $provider,
        public string $modpackId,
        public string $modpackVersionId,
        public bool $deleteServerFiles,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(
        StartupModificationService $startupModificationService,
        DaemonFileRepository $fileRepository,
        ReinstallServerService $reinstallServerService,
        DaemonPowerRepository $daemonPowerRepository,
        DaemonServerRepository $daemonServerRepository,
        MinecraftSoftwareService $minecraftSoftwareService
    ): void {
        // Kill server if running
        $daemonPowerRepository->setServer($this->server)->send('kill');
        $daemonServerRepository->setServer($this->server);

        // Wait for the server to be offline
        while ($daemonServerRepository->getDetails()['state'] !== 'offline') {
            sleep(1);
        }

        if ($this->deleteServerFiles) {
            $fileRepository->setServer($this->server);
            $filesToDelete = collect(
                $fileRepository->getDirectory('/')
            )->pluck('name')->toArray();

            if (count($filesToDelete) > 0) {
                $fileRepository->deleteFiles('/', $filesToDelete);
            }
        }

        $currentEgg = $this->server->egg;

        $installerEgg = Egg::where('author', 'modpack-installer@ric-rac.org')->firstOrFail();

        $startupModificationService->setUserLevel(User::USER_LEVEL_ADMIN);

        rescue(function () use ($startupModificationService, $installerEgg, $reinstallServerService) {
            // This is done in two steps because the service first handles environment variables
            // then service type changes.
            $startupModificationService->handle($this->server, [
                'nest_id' => $installerEgg->nest_id,
                'egg_id' => $installerEgg->id,
            ]);
            $startupModificationService->handle($this->server, [
                'environment' => [
                    'MODPACK_PROVIDER' => $this->provider,
                    'MODPACK_ID' => $this->modpackId,
                    'MODPACK_VERSION_ID' => $this->modpackVersionId,
                ],
            ]);
            $reinstallServerService->handle($this->server);
        });

        sleep(10); // HACK: Should be enough for the daemon to start the installation process

        // Revert the egg back to what it was.
        $startupModificationService->handle($this->server, [
            'nest_id' => $currentEgg->nest_id,
            'egg_id' => $currentEgg->id,
        ]);

        // Wait for the installation process to finish.
        do {
            sleep(10);

            $this->server->refresh();
        } while ($this->server->status === Server::STATUS_INSTALLING);

        // Update Java Docker image depending on the detected Minecraft version.
        $minecraftSoftwareService->setServer($this->server);
        $buildInfo = $minecraftSoftwareService->getServerBuildInformation();

        if (isset($buildInfo['java'])) {
            $availableImages = $this->server->egg->docker_images;
            $newImage = $this->getImageForJavaVersion($availableImages, $buildInfo['java']) ?? 'ghcr.io/pterodactyl/yolks:java_' . $buildInfo['java'];
            $startupModificationService->handle($this->server, [
                'docker_image' => $newImage,
            ]);
        }
    }

    protected function getImageForJavaVersion(array $availableImages, string $javaVersion): ?string
    {
        if (function_exists('array_find')) {
            return array_find($availableImages, fn ($v, $k) => str_ends_with($k, ' ' . $javaVersion));
        }
        foreach ($availableImages as $name => $avImage) {
            if (str_ends_with($name, ' ' . $javaVersion)) {
                return $avImage;
            }
        }
        return null;
    }
}
