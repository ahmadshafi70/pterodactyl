<?php

namespace Pterodactyl\Repositories\Wings;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Psr\Http\Message\ResponseInterface;
use Pterodactyl\Models\NodeBackupServer;
use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class DaemonNodeBackupRepository extends DaemonRepository
{
    protected ?string $adapter;

    public function setBackupAdapter(string $adapter): self
    {
        $this->adapter = $adapter;

        return $this;
    }

    public function backup(NodeBackupServer $backup, string $ignored_files = ''): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            return $this->getHttpClient()->post(
                sprintf('/api/servers/%s/backup', $this->server->uuid),
                [
                    'json' => [
                        'adapter' => $this->adapter ?? config('backups.default'),
                        'uuid' => $backup->uuid,
                        'ignore' => $ignored_files,
                    ],
                ]
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }

    public function restore(NodeBackupServer $backup, string $url = null, bool $truncate = false): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            return $this->getHttpClient()->post(
                sprintf('/api/servers/%s/backup/%s/restore', $this->server->uuid, $backup->uuid),
                [
                    'json' => [
                        'adapter' => $backup->disk,
                        'truncate_directory' => $truncate,
                        'download_url' => $url ?? '',
                    ],
                ]
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }

    public function delete(NodeBackupServer $backup): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            return $this->getHttpClient()->delete(
                sprintf('/api/servers/%s/backup/%s', $this->server->uuid, $backup->uuid)
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }

    public function getDetails(): array
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->get(
                sprintf('/api/servers/%s', $this->server->uuid)
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception, false);
        }

        return json_decode($response->getBody()->__toString(), true);
    }
}
