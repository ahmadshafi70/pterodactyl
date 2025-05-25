<?php

namespace Pterodactyl\Services\AktiCubeDevelopmentTeam;

use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Contracts\Repository\AktiCubeDevelopmentTeamAutomaticPhpMyAdminRepositoryInterface;

class AutomaticPhpMyAdminCreationService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\AktiCubeDevelopmentTeamAutomaticPhpMyAdminRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * AutomaticPhpMyAdminCreationService constructor.
     */
    public function __construct(Encrypter $encrypter, AktiCubeDevelopmentTeamAutomaticPhpMyAdminRepositoryInterface $repository)
    {
        $this->encrypter = $encrypter;
        $this->repository = $repository;
    }

    /**
     * Create a new phpMyAdmin installation on the panel.
     *
     * @return \Pterodactyl\Models\AutomaticPhpMyAdmin
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(array $data)
    {
        return $this->repository->create($data, true, true);
    }
}
