<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\NodeBackupServer;

class NodeBackupServerTransformer extends BaseClientTransformer
{
    public function getResourceName(): string
    {
        return NodeBackupServer::RESOURCE_NAME;
    }

    public function transform(NodeBackupServer $backup): array
    {
        return [
            'uuid' => $backup->uuid,
            'is_successful' => $backup->is_successful,
            'checksum' => $backup->checksum,
            'bytes' => $backup->bytes,
            'created_at' => $backup->created_at->toAtomString(),
            'completed_at' => $backup->completed_at ? $backup->completed_at->toAtomString() : null,
        ];
    }
}
