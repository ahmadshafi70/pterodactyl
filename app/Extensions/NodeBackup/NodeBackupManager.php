<?php

namespace Pterodactyl\Extensions\NodeBackup;

use Aws\S3\S3Client;
use Illuminate\Support\Str;
use Webmozart\Assert\Assert;
use League\Flysystem\FilesystemAdapter;
use Pterodactyl\Models\NodeBackupGroup;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Extensions\Filesystem\S3Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

class NodeBackupManager
{
    protected Encrypter $encrypter;

    public function __construct(Encrypter $encrypter)
    {
        $this->encrypter = $encrypter;
    }

    protected array $adapters = [];

    protected ?NodeBackupGroup $nodeBackupGroup;

    public function setNodeBackupGroup(NodeBackupGroup $nodeBackupGroup): self
    {
        $this->nodeBackupGroup = $nodeBackupGroup;

        return $this;
    }

    public function adapter(): FilesystemAdapter
    {
        return $this->get($this->nodeBackupGroup->getAdapter());
    }

    public function set(string $name, FilesystemAdapter $disk): self
    {
        $this->adapters[$name] = $disk;

        return $this;
    }

    protected function get(string $name): FilesystemAdapter
    {
        return $this->adapters[$name] = $this->resolve($name);
    }

    protected function resolve(string $adapter): FilesystemAdapter
    {
        $adapterMethod = 'create' . Str::studly($adapter) . 'Adapter';

        if (method_exists($this, $adapterMethod)) {
            $instance = $this->{$adapterMethod}();

            Assert::isInstanceOf($instance, FilesystemAdapter::class);

            return $instance;
        }

        throw new \InvalidArgumentException("Adapter [$adapter] is not supported.");
    }

    public function createWingsAdapter(): FilesystemAdapter
    {
        return new InMemoryFilesystemAdapter();
    }

    public function createS3Adapter(): FilesystemAdapter
    {
        $config = $this->nodeBackupGroup->s3Server()->toAdapterConfig();

        $config['credentials']['key'] = $this->encrypter->decrypt($config['credentials']['key']);
        $config['credentials']['secret'] = $this->encrypter->decrypt($config['credentials']['secret']);

        $client = new S3Client($config);

        return new S3Filesystem($client, $config['bucket'], $config['prefix'] ?? '', $config['options'] ?? []);
    }

    public function forget(array|string $adapter): self
    {
        foreach ((array) $adapter as $adapterName) {
            unset($this->adapters[$adapterName]);
        }

        return $this;
    }
}
