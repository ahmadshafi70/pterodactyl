<?php

namespace Pterodactyl\Http\Requests\Admin\AktiCubeDevelopmentTeam;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class StoreAutomaticPhpMyAdminRequest extends AdminFormRequest {
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'url' => 'required|string',
            'cookie_name' => 'required|string|max:10',
            'cookie_domain' => 'required|string',
            'encryption_key' => 'required|string',
            'encryption_iv' => 'required|string',
            'one_click_admin_login_enabled' => 'required|boolean',
            'linked_database_host' => 'nullable|integer',
            'phpmyadmin_server_id' => 'required|integer',
        ];
    }
}
