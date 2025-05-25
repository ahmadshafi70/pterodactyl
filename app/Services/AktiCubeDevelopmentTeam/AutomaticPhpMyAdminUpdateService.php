<?php

namespace Pterodactyl\Services\AktiCubeDevelopmentTeam;

use Pterodactyl\Models\AutomaticPhpMyAdmin;

class AutomaticPhpMyAdminUpdateService
{
    public function handle(AutomaticPhpMyAdmin $automaticPhpMyAdmin, array $data): AutomaticPhpMyAdmin
    {
        $automaticPhpMyAdmin->fill($data);
        $automaticPhpMyAdmin->save();

        return $automaticPhpMyAdmin;
    }
}
