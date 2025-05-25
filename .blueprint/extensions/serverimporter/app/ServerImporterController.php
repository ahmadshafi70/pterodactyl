<?php

namespace Pterodactyl\BlueprintFramework\Extensions\serverimporter;

use phpseclib3\Net\SFTP;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Facades\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Pterodactyl\Jobs\Server\ImportServerJob;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Services\Servers\StartupModificationService;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\BlueprintFramework\Extensions\serverimporter\Models\ImporterServerProfile;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Client\BlueprintClientLibrary;
use Pterodactyl\BlueprintFramework\Extensions\serverimporter\Models\ImporterCredentialProfile;
use Pterodactyl\BlueprintFramework\Extensions\serverimporter\Requests\ServerImporterImportRequest;
use Pterodactyl\BlueprintFramework\Extensions\serverimporter\Transformers\ServerProfileTransformer;
use Pterodactyl\BlueprintFramework\Extensions\serverimporter\Requests\ServerImporterProfilesRequest;
use Pterodactyl\BlueprintFramework\Extensions\serverimporter\Transformers\CredentialProfileTransformer;
use Pterodactyl\BlueprintFramework\Extensions\serverimporter\Requests\ServerImporterTestCredentialsRequest;
use Pterodactyl\BlueprintFramework\Extensions\serverimporter\Requests\ServerImporterStoreServerProfileRequest;
use Pterodactyl\BlueprintFramework\Extensions\serverimporter\Requests\ServerImporterStoreCredentialProfileRequest;

class ServerImporterController extends ClientApiController
{
    public function __construct(
        private DaemonFileRepository $fileRepository,
        private DaemonPowerRepository $powerRepository,
        private DaemonServerRepository $serverRepository,
        private StartupModificationService $startupModificationService,
        private BlueprintClientLibrary $blueprint
    ) {
        parent::__construct();
    }

    private function test(string $host, int $port, string $username, string $password, string $mode, string $from = '/'): bool
    {
        $skipLoginCheck = (int) $this->blueprint->dbGet('serverimporter', 'skip_login_check');
        $skipFilesCheck = (int) $this->blueprint->dbGet('serverimporter', 'skip_files_check');

        if (!$skipLoginCheck) switch ($mode) {
            case 'sftp':
                $sftp = null;

                try {
                    $sftp = new SFTP($host, $port, 5);
                    if (!$sftp->login($username, $password)) {
                        return false;
                    }

                    if (!$skipFilesCheck) {
                        $count = count($sftp->nlist($from));
                        if ($count === 0) {
                            return false;
                        }
                    }
                } catch (\Throwable $exception) {
                    return false;
                } finally {
                    if ($sftp) {
                        $sftp->disconnect();
                    }
                }

                break;

            case 'ftp':
                $ftp = null;

                try {
                    $host = gethostbyname($host);

                    $ftp = ftp_connect($host, $port, 5);
                    if (!$ftp) {
                        return false;
                    }

                    if (!ftp_login($ftp, $username, $password)) {
                        return false;
                    }

                    if (!$skipFilesCheck) {
                        $files = ftp_nlist($ftp, $from);
                        if ($files && count($files) === 0) {
                            return false;
                        }
                    }
                } catch (\Throwable $exception) {
                    return false;
                } finally {
                    if ($ftp) {
                        ftp_close($ftp);
                    }
                }

                break;
        }

        return true;
    }

    public function import(ServerImporterImportRequest $request, Server $server): JsonResponse
    {
        $data = $request->validated();

        $this->powerRepository->setServer($server)->send('kill');

        $importerEgg = Egg::where('author', 'egg@serverimporter.tld')->firstOrFail();
        if ($server->egg_id === $importerEgg->id) {
            return new JsonResponse([
                'error' => 'An import is already in progress for this server.',
            ], 422);
        }

        if (empty($data['from'])) {
            $data['from'] = '/';
        }

        if (empty($data['to'])) {
            $data['to'] = '/';
        }

        $from = str_replace('//', '/', './' . trim(trim($data['from']), '/'));
        $to = str_replace('//', '/', str_replace('..', '', '/mnt/server/' . rtrim(trim($data['to']), '/')));

        if (!$this->test($data['host'], $data['port'], $data['username'], $data['password'], $data['mode'], $from)) {
            return new JsonResponse([
                'error' => 'Failed to connect to the remote server. Double check your credentials and the import path.',
            ], 400);
        }

        if ($data['delete_files']) {
            $files = $this->fileRepository->setServer($server)->getDirectory('/');

            if (count($files) > 0) {
                $this->fileRepository->setServer($server)->deleteFiles(
                    '/',
                    collect($files)->map(fn ($file) => $file['name'])->toArray()
                );
            }
        }

        $oldEgg = $server->egg;

        $server->forceFill([
            'nest_id' => $importerEgg->nest_id,
            'egg_id' => $importerEgg->id,
        ])->save();

        $server->refresh();

        $this->startupModificationService->setUserLevel(User::USER_LEVEL_ADMIN)->handle($server, [
            'egg_id' => $importerEgg->id,
            'environment' => [
                'IMPORT_MODE' => $data['mode'],
                'IMPORT_HOST' => $data['host'],
                'IMPORT_PORT' => (string) $data['port'],
                'IMPORT_USERNAME' => $data['username'],
                'IMPORT_PASSWORD' => $data['password'],
                'IMPORT_FROM_PATH' => $from,
                'IMPORT_TO_PATH' => $to,
            ],
        ]);

        ImportServerJob::dispatch($server, $oldEgg);

        Activity::event('server:import.import')
            ->property('deleteFiles', $data['delete_files'])
            ->property('host', $data['host'])
            ->property('port', $data['port'])
            ->property('mode', $data['mode'])
            ->property('from', $data['from'])
            ->property('to', $data['to'])
            ->log();

        return new JsonResponse([
            'success' => true,
        ]);
    }

    public function testCredentials(ServerImporterTestCredentialsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $from = str_replace('//', '/', './' . trim(trim($data['from']), '/'));

        if (!$this->test($data['host'], $data['port'], $data['username'], $data['password'], $data['mode'], $from)) {
            return new JsonResponse([
                'error' => 'Failed to connect to the remote server. Double check your credentials and the import path.',
            ], 400);
        }

        return new JsonResponse([
            'success' => true,
        ]);
    }

    public function profiles(ServerImporterProfilesRequest $request): array
    {
        $serverProfiles = ImporterServerProfile::query()->where('user_id', $request->user()->id)->get();
        $credentialProfiles = ImporterCredentialProfile::query()->where('user_id', $request->user()->id)->get();

        return [
            'servers' => array_reverse($this->fractal->collection($serverProfiles)
                ->transformWith($this->getTransformer(ServerProfileTransformer::class))
                ->toArray()),
            'credentials' => array_reverse($this->fractal->collection($credentialProfiles)
                ->transformWith($this->getTransformer(CredentialProfileTransformer::class))
                ->toArray()),
        ];
    }

    public function storeServerProfile(ServerImporterStoreServerProfileRequest $request): JsonResponse
    {
        $data = $request->validated();

        $count = ImporterServerProfile::query()->where('user_id', $request->user()->id)->count();

        $profileLimit = (int) $this->blueprint->dbGet('serverimporter', 'server_profile_limit');
        if ($profileLimit === 0) {
            $profileLimit = 100;
        }

        if ($count > $profileLimit) {
            return new JsonResponse([
                'error' => "You may not have more than $profileLimit server profiles.",
            ], 403);
        }

        try {
            $serverProfile = ImporterServerProfile::create([
                'user_id' => $request->user()->id,
                'name' => $data['name'],
                'host' => $data['host'],
                'port' => $data['port'],
                'mode' => $data['mode'],
            ]);

            return new JsonResponse($this->fractal->item($serverProfile)
                ->transformWith($this->getTransformer(ServerProfileTransformer::class))
                ->toArray(), 201);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'error' => 'You already have a server profile with that name.',
            ], 409);
        }
    }

    public function storeCredentialProfile(ServerImporterStoreCredentialProfileRequest $request): JsonResponse
    {
        $data = $request->validated();

        $count = ImporterCredentialProfile::query()->where('user_id', $request->user()->id)->count();

        $profileLimit = (int) $this->blueprint->dbGet('serverimporter', 'credential_profile_limit');
        if ($profileLimit === 0) {
            $profileLimit = 100;
        }

        if ($count > $profileLimit) {
            return new JsonResponse([
                'error' => "You may not have more than $profileLimit credential profiles.",
            ], 403);
        }

        try {
            $credentialProfile = ImporterCredentialProfile::create([
                'user_id' => $request->user()->id,
                'name' => $data['name'],
                'username' => $data['username'],
                'password' => Crypt::encryptString($data['password']),
            ]);

            return new JsonResponse($this->fractal->item($credentialProfile)
                ->transformWith($this->getTransformer(CredentialProfileTransformer::class))
                ->toArray(), 201);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'error' => 'You already have a credential profile with that name.',
            ], 409);
        }
    }

    public function deleteServerProfile(ServerImporterProfilesRequest $request): JsonResponse
    {
        $serverProfile = ImporterServerProfile::query()->where('id', $request->route('profile'))->where('user_id', $request->user()->id)->first();
        if (!$serverProfile) {
            return new JsonResponse([
                'error' => 'Server profile not found.',
            ], 404);
        }

        $serverProfile->delete();

        return new JsonResponse([], 204);
    }

    public function deleteCredentialProfile(ServerImporterProfilesRequest $request): JsonResponse
    {
        $credentialProfile = ImporterCredentialProfile::query()->where('id', $request->route('profile'))->where('user_id', $request->user()->id)->first();
        if (!$credentialProfile) {
            return new JsonResponse([
                'error' => 'Credential profile not found.',
            ], 404);
        }

        $credentialProfile->delete();

        return new JsonResponse([], 204);
    }

    public function updateServerProfile(ServerImporterStoreServerProfileRequest $request): JsonResponse
    {
        $data = $request->validated();
        $serverProfile = ImporterServerProfile::query()->where('id', $request->route('profile'))->where('user_id', $request->user()->id)->first();
        if (!$serverProfile) {
            return new JsonResponse([
                'error' => 'Server profile not found.',
            ], 404);
        }

        try {
            $serverProfile->update([
                'name' => $data['name'],
                'host' => $data['host'],
                'port' => $data['port'],
                'mode' => $data['mode'],
            ]);

            return new JsonResponse([], 204);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'error' => 'You already have a server profile with that name.',
            ], 409);
        }
    }

    public function updateCredentialProfile(ServerImporterStoreCredentialProfileRequest $request): JsonResponse
    {
        $data = $request->validated();
        $credentialProfile = ImporterCredentialProfile::query()->where('id', $request->route('profile'))->where('user_id', $request->user()->id)->first();
        if (!$credentialProfile) {
            return new JsonResponse([
                'error' => 'Credential profile not found.',
            ], 404);
        }

        try {
            $credentialProfile->update([
                'name' => $data['name'],
                'username' => $data['username'],
                'password' => Crypt::encryptString($data['password']),
            ]);

            return new JsonResponse([], 204);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'error' => 'You already have a credential profile with that name.',
            ], 409);
        }
    }
}
