<?php

namespace Pterodactyl\BlueprintFramework\Extensions\versionchanger;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Client\BlueprintClientLibrary as BlueprintExtensionLibrary;
use Pterodactyl\Repositories\Eloquent\ServerVariableRepository;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;

class VersionChangerUtils
{
    public function __construct(
        private ServerVariableRepository $variableRepository,
        private DaemonFileRepository $fileRepository,
        private DaemonPowerRepository $powerRepository,
        private BlueprintExtensionLibrary $blueprint,
        private string $fields = 'id,type,projectVersionId,versionId,name,experimental,created'
    ) {
    }

    public function incrementInstalls(string $type, string $version): void {
        DB::beginTransaction();

        $data = json_decode($this->blueprint->dbGet('versionchanger', 'installs') ?: '{}', true);

        if (!isset($data['types'])) {
            $data['types'] = [];
        }

        if (!isset($data['versions'])) {
            $data['versions'] = [];
        }

        if (!isset($data['types'][$type])) {
            $data['types'][$type] = [
                'total' => 0,
                'versions' => [],
            ];
        }

        if (!isset($data['types'][$type]['versions'][$version])) {
            $data['types'][$type]['versions'][$version] = 0;
        }

        if (!isset($data['versions'][$version])) {
            $data['versions'][$version] = 0;
        }

        $data['types'][$type]['total']++;
        $data['types'][$type]['versions'][$version]++;
        $data['versions'][$version]++;

        $this->blueprint->dbSet('versionchanger', 'installs', json_encode($data));

        DB::commit();
    }

    public function pickAndSortArrayKeys(array $array, array $keys): array
    {
        $keys = array_filter($keys);
        if (empty($keys)) {
            return $array;
        }

        $result = [];

        foreach ($keys as $key) {
            $key = trim($key);
            if (array_key_exists($key, $array)) {
                $result[$key] = $array[$key];
            }
        }

        return $result;
    }

    public function lookup(array $data): array|null
    {
        try {
            $url = $this->blueprint->dbGet('versionchanger', 'mcvapi_url') ?: 'https://versions.mcjars.app';
            $name = config('app.name');

            $req = Http::withUserAgent("Version Changer by 0x7d8 @ $name")
                ->timeout(5)
                ->retry(2, 100, throw: false)
                ->withHeaders([
                    'Authorization' => $this->blueprint->dbGet('versionchanger', 'mcvapi_key') ?: '',
                    'Origin' => config('app.url'),
                ])
                ->post("$url/api/v2/build?fields=$this->fields,installation", $data);

            if (!$req->ok()) {
                return null;
            }

            $data_api = json_decode($req->body(), true);

            return [
                'build' => $data_api['build'],
                'latest' => $data_api['latest'],
                'version' => $data_api['version'],
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function defaultTypesOrder(): array
    {
        return Cache::remember('minecraft_version_types_order_default', 300, function () {
            $url = $this->blueprint->dbGet('versionchanger', 'mcvapi_url') ?: 'https://versions.mcjars.app';
            $name = config('app.name');

            $req = Http::withUserAgent("Version Changer by 0x7d8 @ $name")
                ->timeout(5)
                ->retry(2, 100, throw: false)
                ->get("$url/api/v2/types");

            if (!$req->ok()) {
                return [];
            }

            $types = [];
            $data_api = json_decode($req->body(), true)['types'];

            foreach ($data_api as $category => $types_api) {
                $category = strtoupper($category[0]) . substr($category, 1);
                $types[$category] = [];

                foreach ($types_api as $key => $type) {
                    $types[$category][] = $key;
                }
            }

            if (!$this->blueprint->dbGet('versionchanger', 'mcvapi_types_order')) {
                $this->blueprint->dbSet('versionchanger', 'mcvapi_types_order', json_encode($types, JSON_PRETTY_PRINT));
            }

            return $types;
        });
    }

    public function typesOrder(): array
    {
        $order = json_decode($this->blueprint->dbGet('versionchanger', 'mcvapi_types_order') ?: json_encode($this->defaultTypesOrder()), true);

        return $order;
    }

    public function types(): array
    {
        $useWebp = $this->blueprint->dbGet('versionchanger', 'mcvapi_image_format') === 'webp';
        $imageBase = $this->blueprint->dbGet('versionchanger', 'mcvapi_image_base_url') ?: 'https://s3.mcjars.app/icons/';

        $url = $this->blueprint->dbGet('versionchanger', 'mcvapi_url') ?: 'https://versions.mcjars.app';
        $name = config('app.name');

        $data = Cache::remember('minecraft_version_types', 300, function () use ($url, $name) {
            $key = $this->blueprint->dbGet('versionchanger', 'mcvapi_key');

            if (!$key) {
                $req = Http::withUserAgent("Version Changer by 0x7d8 @ $name")
                    ->timeout(5)
                    ->retry(2, 100, throw: false)
                    ->get("$url/api/v2/types");

                if (!$req->ok()) {
                    return [];
                }

                $types = [];
                $data_api = json_decode($req->body(), true)['types'];

                foreach ($data_api as $category => $types_api) {
                    foreach ($types_api as $key => $type) {
                        $types[$key] = $type;
                    }
                }

                return $types;
            } else {
                $req = Http::withUserAgent("Version Changer by 0x7d8 @ $name")
                    ->timeout(5)
                    ->retry(2, 100, throw: false)
                    ->withHeaders([
                        'Authorization' => $key,
                        'Origin' => env('APP_URL'),
                    ])
                    ->get("$url/api/organization/v1/types");

                if (!$req->ok()) {
                    return [];
                }
    
                return json_decode($req->body(), true)['types'];
            }
        });

        foreach ($data as $key => $type) {
            $lowercase = strtolower($key);
            $fileEnding = $useWebp ? 'webp' : 'png';

            $data[$key]['icon'] = "{$imageBase}{$lowercase}.{$fileEnding}";
        }

        return $data;
    }

    public function versions(string $type): array|null
    {
        $url = $this->blueprint->dbGet('versionchanger', 'mcvapi_url') ?: 'https://versions.mcjars.app';
        $name = config('app.name', 'Pterodactyl');
        $type = strtoupper($type);

        $data = Cache::remember("minecraft_version_type_versions_{$type}", 120, function () use ($type, $url, $name) {
            $req = Http::withUserAgent("Version Changer by 0x7d8 @ $name")
                ->timeout(5)
                ->retry(2, 100, throw: false)
                ->withHeaders([
                    'Authorization' => $this->blueprint->dbGet('versionchanger', 'mcvapi_key') ?: '',
                    'Origin' => env('APP_URL'),
                ])
                ->get("$url/api/v2/builds/{$type}?fields=$this->fields");

            if (!$req->ok()) {
                return null;
            }

            return json_decode($req->body(), true)['builds'];
        });

        return $data;
    }

    public function builds(string $type, string $version): array|null
    {
        $url = $this->blueprint->dbGet('versionchanger', 'mcvapi_url') ?: 'https://versions.mcjars.app';
        $name = config('app.name', 'Pterodactyl');
        $type = strtoupper($type);

        $data = Cache::remember("minecraft_version_type_builds_{$type}_{$version}", 120, function () use ($type, $version, $url, $name) {
            $req = Http::withUserAgent("Version Changer by 0x7d8 @ $name")
                ->timeout(5)
                ->retry(2, 100, throw: false)
                ->withHeaders([
                    'Authorization' => $this->blueprint->dbGet('versionchanger', 'mcvapi_key') ?: '',
                    'Origin' => env('APP_URL'),
                ])
                ->get("$url/api/v2/builds/{$type}/{$version}?fields=$this->fields");

            if (!$req->ok()) {
                return null;
            }

            return json_decode($req->body(), true)['builds'];
        });

        return $data;
    }
}
