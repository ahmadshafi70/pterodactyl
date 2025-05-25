<?php

namespace Pterodactyl\Models;

use Ramsey\Uuid\Uuid;

class NodeBackupServer extends Model
{
    public const RESOURCE_NAME = 'nodebackupserver';

    public const RESTORATION_TYPE_CLASSIC = 'classic';
    public const RESTORATION_TYPE_RECREATE = 'recreate';

    protected $table = 'node_backup_servers';

    protected bool $immutableDates = true;

    protected $casts = [
        'id' => 'int',
        'node_backup_id' => 'int',
        'server_id' => 'int',
        'uuid' => 'string',
        'upload_id' => 'string',
        'disk' => 'string',
        'checksum' => 'string',
        'bytes' => 'int',
        'is_successful' => 'bool',
        'restoration_type' => 'string',
        'restoration_node_id' => 'int',
        'completed_at' => 'datetime',
        'started_at' => 'datetime',
        'restoration_started_at' => 'datetime',
        'restoration_completed_at' => 'datetime',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public static array $validationRules = [
        'node_backup_id' => 'required|numeric|exists:node_backups,id',
        'server_id' => 'required|numeric|exists:servers,id',
        'uuid' => 'required|uuid',
        'upload_id' => 'nullable|string',
        'disk' => 'required|string',
        'checksum' => 'nullable|string',
        'bytes' => 'numeric',
        'is_successful' => 'boolean',
        'restoration_type' => 'nullable|string',
        'restoration_node_id' => 'nullable|numeric|exists:nodes,id',
    ];

    public function nodeBackupGroup(): NodeBackupGroup
    {
        return $this->nodeBackup()->nodeBackupGroup();
    }

    public function nodeBackup(): NodeBackup
    {
        return NodeBackup::query()->findOrFail($this->node_backup_id);
    }

    public function server(): Server
    {
        return Server::query()->findOrFail($this->server_id);
    }

    public function node(): Node
    {
        return $this->server()->node;
    }

    public function restorationNode(): ?Node
    {
        return Node::query()->find($this->restoration_node_id);
    }

    public function done(): bool
    {
        return !is_null($this->completed_at) && !is_null($this->started_at);
    }

    public function isSuccessful(): bool
    {
        return $this->done() && $this->is_successful;
    }

    public function isRestoring(): bool
    {
        return !is_null($this->restoration_started_at) && is_null($this->restoration_completed_at);
    }

    public function inProgress(): bool
    {
        return is_null($this->completed_at);
    }

    public static function generateUniqueUuid(): string
    {
        $uuid = Uuid::uuid4()->toString();

        if (NodeBackupServer::query()->where('uuid', $uuid)->exists() || Backup::query()->where('uuid', $uuid)->exists()) {
            return self::generateUniqueUuid();
        }

        return $uuid;
    }

    public function uuidShort(): string
    {
        return substr($this->uuid, 0, 8);
    }

    public function size(): int
    {
        return round($this->bytes / 1024 / 1024, 2);
    }
}
