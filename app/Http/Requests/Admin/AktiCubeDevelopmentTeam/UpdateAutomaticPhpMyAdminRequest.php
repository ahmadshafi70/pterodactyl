<?php

namespace Pterodactyl\Http\Requests\Admin\AktiCubeDevelopmentTeam;

use Illuminate\Support\Collection;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;
use Pterodactyl\Models\AutomaticPhpMyAdmin;

class UpdateAutomaticPhpMyAdminRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return Collection::make(
            AutomaticPhpMyAdmin::getRulesForUpdate($this->route()->parameter('automaticphpmyadmin'))
        )->only([
            'name',
            'description',
            'url',
            'cookie_name',
            'cookie_domain',
            'encryption_key',
            'encryption_iv',
            'one_click_admin_login_enabled',
            'linked_database_host',
            'phpmyadmin_server_id',
        ])->toArray();
    }
}
