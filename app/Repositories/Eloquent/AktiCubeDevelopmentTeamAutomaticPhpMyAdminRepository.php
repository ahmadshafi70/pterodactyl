<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Contracts\Repository\AktiCubeDevelopmentTeamAutomaticPhpMyAdminRepositoryInterface;
use Pterodactyl\Models\AutomaticPhpMyAdmin;

class AktiCubeDevelopmentTeamAutomaticPhpMyAdminRepository extends EloquentRepository implements AktiCubeDevelopmentTeamAutomaticPhpMyAdminRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function model(): string
    {
        return AutomaticPhpMyAdmin::class;
    }
}
