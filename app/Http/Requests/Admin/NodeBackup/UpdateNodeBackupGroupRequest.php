<?php

namespace Pterodactyl\Http\Requests\Admin\NodeBackup;

use Pterodactyl\Models\NodeBackupGroup;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class UpdateNodeBackupGroupRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return NodeBackupGroup::getRules();
    }
}
