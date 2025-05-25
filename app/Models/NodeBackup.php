<?php

namespace Pterodactyl\Models;

use Carbon\CarbonImmutable;
use Ramsey\Uuid\Uuid;

class NodeBackup extends Model
{
    public const RESOURCE_NAME = 'nodebackup';

    protected $table = 'node_backups';

    protected bool $immutableDates = true;

    protected $casts = [
        'id' => 'int',
        'node_backup_group_id' => 'int',
        'uuid' => 'string',
        'name' => 'string',
        'to_be_deleted' => 'bool',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public static array $validationRules = [
        'node_backup_group_id' => 'numeric|exists:node_backup_groups,id',
        'uuid' => 'uuid',
        'name' => 'required|string',
        'to_be_deleted' => 'boolean',
    ];

    public function nodeBackupGroup(): NodeBackupGroup
    {
        return NodeBackupGroup::query()
            ->where('id', $this->node_backup_group_id)->firstOrFail();
    }

    public function numberOfBackups(): int
    {
        return NodeBackupServer::query()
            ->where('node_backup_id', $this->id)
            ->count();
    }

    public function numberOfFinishedBackups(): int
    {
        return NodeBackupServer::query()
            ->where('node_backup_id', $this->id)
            ->whereNotNull('completed_at')
            ->count();
    }

    public function hasNoFailures(): bool
    {
        return NodeBackupServer::query()
            ->where('node_backup_id', $this->id)
            ->where('is_successful', false)
            ->whereNotNull('completed_at')
            ->whereNotNull('started_at')
            ->count() === 0;
    }

    public function isRestoring(): bool
    {
        return NodeBackupServer::query()
            ->where('node_backup_id', $this->id)
            ->whereNotNull('restoration_type')
            ->whereNull('restoration_completed_at')
            ->count() > 0;
    }

    public function getCompletedAt(): ?CarbonImmutable
    {
        if (NodeBackupServer::where('node_backup_id', $this->id)
            ->whereNull('completed_at')->count() !== 0) {
            return null;
        }
        return NodeBackupServer::where('node_backup_id', $this->id)
            ->orderByDesc('completed_at')
            ->first()?->completed_at;
    }

    public function uuidShort(): string
    {
        return substr($this->uuid, 0, 8);
    }

    public function size(): int
    {
        return round(
            NodeBackupServer::query()
                ->where('node_backup_id', $this->id)
                ->sum('bytes') / 1024 / 1024,
            2
        );
    }

    public static function generateUniqueUuid(): string
    {
        $uuid = Uuid::uuid4()->toString();

        if (NodeBackup::query()->where('uuid', $uuid)->exists()) {
            return self::generateUniqueUuid();
        }

        return $uuid;
    }
}
