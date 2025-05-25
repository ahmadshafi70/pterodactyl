<?php

namespace Pterodactyl\Http\Requests\Admin\NodeBackup;

use Pterodactyl\Models\NodeBackup;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class StoreNodeBackupRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return NodeBackup::$validationRules;
    }
}
