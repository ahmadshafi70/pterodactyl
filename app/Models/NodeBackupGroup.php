<?php

namespace Pterodactyl\Models;

use Cron\CronExpression;
use Carbon\CarbonImmutable;

class NodeBackupGroup extends Model
{
    public const RESOURCE_NAME = 'nodebackupgroup';

    protected $table = 'node_backup_groups';

    protected bool $immutableDates = true;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'description' => 'string',
        'nodes_id' => 'array',
        's3_server_id' => 'integer',
        'cron_minute' => 'string',
        'cron_hour' => 'string',
        'cron_day_of_month' => 'string',
        'cron_month' => 'string',
        'cron_day_of_week' => 'string',
        'max_server_size' => 'integer',
        'retention_days' => 'integer',
        'max_being_made_backups' => 'integer',
        'ignored_files' => 'string',
        'is_active' => 'boolean',
        'to_be_deleted' => 'boolean',
        'keep_deleted' => 'boolean',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
    ];

    public static array $validationRules = [
        'name' => 'required|string',
        'description' => 'nullable|string',
        'nodes_id' => 'required|array|min:1',
        's3_server_id' => 'sometimes|integer|nullable|exists:node_backup_s3_servers,id',
        'cron_minute' => 'required|string',
        'cron_hour' => 'required|string',
        'cron_day_of_month' => 'required|string',
        'cron_month' => 'required|string',
        'cron_day_of_week' => 'required|string',
        'max_server_size' => 'required|integer|min:-1',
        'retention_days' => 'required|integer|min:-1',
        'max_being_made_backups' => 'required|integer|min:1|max:10',
        'ignored_files' => 'nullable|string',
        'is_active' => 'required|boolean',
        'to_be_deleted' => 'boolean',
        'keep_deleted' => 'boolean',
    ];

    public function nodes(): array|\Illuminate\Database\Eloquent\Collection
    {
        return Node::query()->whereIn('id', $this->nodes_id)->get();
    }

    public function servers(): \Illuminate\Database\Eloquent\Collection|array
    {
        return Server::query()->whereIn('node_id', $this->nodes_id)->get();
    }

    public function nodeBackups(): array|\Illuminate\Database\Eloquent\Collection
    {
        return NodeBackup::query()->where('node_backup_group_id', $this->id)->get();
    }

    public function s3Server(): NodeBackupS3Server
    {
        return NodeBackupS3Server::query()->where('id', $this->s3_server_id)->first();
    }

    public function getAdapter(): string
    {
        return (is_null($this->s3_server_id) || $this->s3_server_id === 0) ? 'wings' : 's3';
    }

    public function size(): int
    {
        $size = 0;
        $nodeBackups = NodeBackup::query()->where('node_backup_group_id', $this->id)->get();
        foreach ($nodeBackups as $nodeBackup) {
            $size += $nodeBackup->size();
        }

        return $size;
    }

    public function getCronExpression(): string
    {
        return sprintf('%s %s %s %s %s', $this->cron_minute, $this->cron_hour, $this->cron_day_of_month, $this->cron_month, $this->cron_day_of_week);
    }

    public function getNextRunDate(): CarbonImmutable
    {
        return CarbonImmutable::createFromTimestamp(
            (new CronExpression($this->getCronExpression()))->getNextRunDate()->getTimestamp()
        );
    }

    public function isProcessing(): bool
    {
        $nodeBackups = NodeBackup::query()
            ->where('node_backup_group_id', $this->id)
            ->get()
            ->filter(fn ($backup) => $backup->getCompletedAt() === null);

        return $nodeBackups->count() > 0;
    }
}
