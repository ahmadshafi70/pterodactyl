<?php

namespace Pterodactyl\BlueprintFramework\Extensions\versionchanger;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Pterodactyl\Facades\Activity;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;
use Pterodactyl\Repositories\Eloquent\ServerVariableRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Client\BlueprintClientLibrary;
use Pterodactyl\BlueprintFramework\Extensions\versionchanger\Requests\VersionChangerGetRequest;
use Pterodactyl\BlueprintFramework\Extensions\versionchanger\Requests\VersionChangerInstallRequest;

class VersionChangerController extends ClientApiController
{
    public function __construct(
        private ServerVariableRepository $variableRepository,
        private DaemonFileRepository $fileRepository,
        private DaemonPowerRepository $powerRepository,
        private BlueprintClientLibrary $blueprint,
        private VersionChangerUtils $utils,
        private string $fields = 'id,type,projectVersionId,versionId,name,experimental,created'
    ) {
        parent::__construct();
    }

    public function installed(VersionChangerGetRequest $request, Server $server): array
    {
        try {
            $res = $this->powerRepository->setServer($server)->getHttpClient()->get(
                sprintf('/api/servers/%s/version', $server->uuid)
            );

            $data = json_decode($res->getBody()->getContents(), true);
            $hash = $data['hash'] ?? null;

            if ($hash === null) {
                return [
                    'success' => true,
                    'build' => null,
                    'latest' => null,
                ];
            }

            $data_api = $this->utils->lookup([
                'hash' => [
                    'sha256' => $hash,
                ],
            ]);

            if (!$data_api) {
                return [
                    'success' => true,
                    'build' => null,
                    'latest' => null,
                ];
            }

            return [
                'success' => true,
                'build' => $data_api['build'],
                'latest' => $data_api['latest'],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => true,
                'build' => null,
                'latest' => null,
            ];
        }
    }

    public function install(VersionChangerInstallRequest $request, Server $server): array
    {
        $data = $request->validated();

        $data_api = $this->utils->lookup([
            'id' => $data['build'],
        ]);

        $this->powerRepository->setServer($server)->send('kill');

        if ($data['delete_files']) {
            $files = $this->fileRepository->setServer($server)->getDirectory('/');

            if (count($files) > 0) {
                $this->fileRepository->setServer($server)->deleteFiles(
                    '/',
                    collect($files)->map(fn ($file) => $file['name'])->toArray()
                );
            }
        }

        $this->fileRepository->setServer($server)->deleteFiles('/', ['libraries']);

        if ($this->blueprint->dbGet('versionchanger', 'collect_stats') === '1') {
            $this->utils->incrementInstalls($data_api['build']['type'], $data_api['build']['versionId'] ?? $data_api['build']['projectVersionId']);
        }

        if ($data['accept_eula']) {
            $this->fileRepository->setServer($server)->putContent('/eula.txt', 'eula=true');
        }

        $eggRule = DB::table('minecraft_version_changer_eggs')->whereJsonContains('types', [$data_api['build']['type']])->first();
        if ($eggRule) {
            $egg = Egg::query()->find($eggRule->egg_id);

            $server->forceFill([
                'egg_id' => $egg->id,
                'nest_id' => $egg->nest_id,
                'startup' => $server->startup === $server->egg->startup ? $egg->startup : $server->startup,
            ])->save();

            $server->refresh();
        }

        foreach ($data_api['build']['installation'] as $chunk) {
            foreach ($chunk as $step) {
                switch ($step['type']) {
                    case 'download':
                        $this->fileRepository->setServer($server)->pull($step['url'], '/', [
                            'filename' => $step['file'],
                            'foreground' => true,
                        ]);

                        break;

                    case 'unzip':
                        $this->fileRepository->setServer($server)->decompressFile(
                            $step['location'] === '.' ? '/' : $step['location'], $step['file']
                        );

                        break;

                    case 'remove':
                        $this->fileRepository->setServer($server)->deleteFiles('/', [$step['location']]);

                        break;
                }
            }
        }

        Activity::event('server:version.install')
            ->property('type', $data_api['build']['type'])
            ->property('version', $data_api['build']['versionId'] ?? $data_api['build']['projectVersionId'])
            ->property('build', $data_api['build']['name'])
            ->property('deleteFiles', $data['delete_files'])
            ->log();

        try {
            $variable = $server->variables()->where('env_variable', 'SERVER_JARFILE')->first();
            $this->variableRepository->updateOrCreate([
                'server_id' => $server->id,
                'variable_id' => $variable->id,
            ], [
                'variable_value' => 'server.jar',
            ]);
        } catch (\Throwable $e) {
        }

        $java = $data_api['version']['java'] ?? 21;

        $availableJavaVersions = [];
        foreach ($server->egg->docker_images as $image) {
            $availableJavaVersions[] = (int) preg_replace("/[^0-9]/", '', explode(':', $image)[1]);
        }

        if (in_array($java, $availableJavaVersions)) {
            $server->forceFill([
                'image' => array_values($server->egg->docker_images)[array_search($java, $availableJavaVersions)],
            ])->save();
        }

        return [
            'success' => true,
        ];
    }

    public function types(VersionChangerGetRequest $request): array
    {
        $data_api = $this->utils->types();
        $types_order = $this->utils->typesOrder();

        $data = [];

        foreach ($types_order as $category => $types) {
            $data[$category] = [];

            foreach ($types as $type) {
                if (isset($data_api[$type])) {
                    $data[$category][$type] = $data_api[$type];
                }
            }
        }

        return [
            'success' => true,
            'types' => $data,
        ];
    }

    public function versions(VersionChangerGetRequest $request, Server $server, string $type): array
    {
        $data_api = $this->utils->versions($type);
        if (!$data_api) {
            return [
                'success' => false,
                'error' => 'Unable to fetch versions for this type.',
            ];
        }

        return [
            'success' => true,
            'builds' => $data_api,
        ];
    }

    public function builds(VersionChangerGetRequest $request, Server $server, string $type, string $version): array
    {
        $data_api = $this->utils->builds($type, $version);
        if (!$data_api) {
            return [
                'success' => false,
                'error' => 'Unable to fetch builds for this version.',
            ];
        }

        return [
            'success' => true,
            'builds' => $data_api,
        ];
    }
}
