<?php

namespace Pterodactyl\Console\Commands\NodeBackup;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Pterodactyl\Models\NodeBackup;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\NodeBackupGroup;
use Pterodactyl\Services\NodeBackup\NodeBackupService;

class ProcessNodeBackupGroups extends Command
{
    protected $signature = 'p:nodebackup:process {--force}';

    protected $description = 'Process NodeBackupGroups that need to be run or processed for retention.';

    private NodeBackupService $nodeBackupService;

    public function handle(): int
    {
        $this->nodeBackupService = $this->getLaravel()->make(NodeBackupService::class);

        $this->processNodeBackupGroupsToRun();
        $this->processNodeBackupGroupsToDelete();
        $this->processNodeBackupGroupsRetention();
        $this->processNodeBackupsToDelete();

        return 0;
    }

    private function processNodeBackupGroupsToRun(): void
    {
        $nodeBackupGroupsToProcessRun = NodeBackupGroup::query()
            ->where('is_active', true)
            ->whereRaw('next_run_at <= NOW()')
            ->orderBy('next_run_at')
            ->get();

        if ($nodeBackupGroupsToProcessRun->count() < 1) {
            $this->info('There are no NodeBackupGroups that need to be run, so no backups to do.');
        } else {
            $bar = $this->output->createProgressBar(count($nodeBackupGroupsToProcessRun));
            foreach ($nodeBackupGroupsToProcessRun as $nodeBackupGroup) {
                $bar->clear();
                if ($nodeBackupGroup->isProcessing()) {
                    $this->info("NodeBackupGroup {$nodeBackupGroup->name} ({$nodeBackupGroup->id}) is already being processed, skipping.");
                } else {
                    $this->processNodeBackupGroupRun($nodeBackupGroup);
                }
                $bar->advance();
                $bar->display();
            }
        }

        $this->line('');
    }

    private function processNodeBackupGroupRun(NodeBackupGroup $nodeBackupGroup): void
    {
        try {
            $this->info("Processing NodeBackupGroup {$nodeBackupGroup->name} ({$nodeBackupGroup->id}) run.");

            $this->nodeBackupService->handleCreation(
                NodeBackup::query()->create([
                    'name' => Carbon::now()->format('Y-m-d H:i:s'),
                    'node_backup_group_id' => $nodeBackupGroup->id,
                    'uuid' => NodeBackup::generateUniqueUuid(),
                ])
            );

            $nodeBackupGroup->update([
                'next_run_at' => $nodeBackupGroup->getNextRunDate(),
            ]);

            $this->info("NodeBackupGroup run {$nodeBackupGroup->name} ({$nodeBackupGroup->id}) has been processed.");
        } catch (\Exception $e) {
            Log::error($e);

            $this->error("An error occurred while processing NodeBackupGroup {$nodeBackupGroup->name} ({$nodeBackupGroup->id}).");
        }
    }

    private function processNodeBackupGroupsToDelete(): void
    {
        $nodeBackupGroupsToDelete = NodeBackupGroup::query()
            ->where('to_be_deleted', true)
            ->get();

        if ($nodeBackupGroupsToDelete->count() < 1) {
            $this->info('There are no NodeBackupGroups that need to be deleted, so no backups to do.');
        } else {
            $bar = $this->output->createProgressBar(count($nodeBackupGroupsToDelete));
            foreach ($nodeBackupGroupsToDelete as $nodeBackupGroup) {
                $bar->clear();
                $this->processNodeBackupGroupToDelete($nodeBackupGroup);
                $bar->advance();
                $bar->display();
            }
        }

        $this->line('');
    }

    private function processNodeBackupGroupToDelete(NodeBackupGroup $nodeBackupGroup): void
    {
        try {
            $this->info("Processing NodeBackupGroup {$nodeBackupGroup->name} ({$nodeBackupGroup->id}) deletion.");

            foreach ($nodeBackupGroup->nodeBackups() as $nodeBackup) {
                $this->nodeBackupService->handleDeletion($nodeBackup);
            }

            $nodeBackupGroup->delete();

            $this->info("NodeBackupGroup deletion {$nodeBackupGroup->name} ({$nodeBackupGroup->id}) has been processed.");
        } catch (\Exception $e) {
            Log::error($e);

            $this->error("An error occurred while processing deletion NodeBackupGroup {$nodeBackupGroup->name} ({$nodeBackupGroup->id}).");
        }
    }

    private function processNodeBackupGroupsRetention(): void
    {
        $nodeBackupGroupToProcessRetention = NodeBackupGroup::query()
            ->where('is_active', true)
            ->where('retention_days', '>', 0)
            ->get();

        if ($nodeBackupGroupToProcessRetention->count() < 1) {
            $this->info('There are no NodeBackupGroups that need to be processed for retention, so no retention to do.');
        } else {
            $bar = $this->output->createProgressBar(count($nodeBackupGroupToProcessRetention));
            foreach ($nodeBackupGroupToProcessRetention as $nodeBackupGroup) {
                $bar->clear();
                $this->processNodeBackupGroupRetention($nodeBackupGroup);
                $bar->advance();
                $bar->display();
            }
        }

        $this->line('');
    }

    private function processNodeBackupGroupRetention(NodeBackupGroup $nodeBackupGroup): void
    {
        try {
            $this->info("Processing NodeBackupGroup {$nodeBackupGroup->name} ({$nodeBackupGroup->id}) retention.");

            $this->nodeBackupService->handleRetention($nodeBackupGroup);

            $this->info("NodeBackupGroup retention {$nodeBackupGroup->name} ({$nodeBackupGroup->id}) has been processed.");
        } catch (\Exception $e) {
            Log::error($e);

            $this->error("An error occurred while processing retention NodeBackupGroup {$nodeBackupGroup->name} ({$nodeBackupGroup->id}).");
        }
    }

    private function processNodeBackupsToDelete(): void
    {
        $nodeBackupsToDelete = NodeBackup::query()
            ->where('to_be_deleted', true)
            ->get();

        if ($nodeBackupsToDelete->count() < 1) {
            $this->info('There are no NodeBackups that need to be deleted, so no deletion to do.');
        } else {
            $bar = $this->output->createProgressBar(count($nodeBackupsToDelete));
            foreach ($nodeBackupsToDelete as $nodeBackup) {
                $bar->clear();
                $this->processNodeBackupToDelete($nodeBackup);
                $bar->advance();
                $bar->display();
            }
        }
    }

    private function processNodeBackupToDelete(NodeBackup $nodeBackup): void
    {
        try {
            $this->info("Processing NodeBackup {$nodeBackup->name} ({$nodeBackup->id}) deletion.");

            $this->nodeBackupService->handleDeletion($nodeBackup, $this->option('force'));

            $this->info("NodeBackup deletion {$nodeBackup->name} ({$nodeBackup->id}) has been processed.");
        } catch (\Exception $e) {
            Log::error($e);

            $this->error("An error occurred while processing deletion NodeBackup {$nodeBackup->name} ({$nodeBackup->id}).");
        }
    }
}
