<?php

namespace Pterodactyl\Http\Requests\Admin\NodeBackup;

use Pterodactyl\Models\NodeBackupS3Server;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class UpdateNodeBackupS3ServerRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return NodeBackupS3Server::getRules();
    }
}
