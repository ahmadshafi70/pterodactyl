<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Client\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\AktiCubeDevelopmentTeam\StoreAutomaticPhpMyAdminRequest;
use Pterodactyl\Http\Requests\Admin\AktiCubeDevelopmentTeam\UpdateAutomaticPhpMyAdminRequest;
use Pterodactyl\Models\AutomaticPhpMyAdmin;
use Pterodactyl\Repositories\Eloquent\DatabaseHostRepository;
use Pterodactyl\Services\AktiCubeDevelopmentTeam\AutomaticPhpMyAdminCreationService;
use Pterodactyl\Services\AktiCubeDevelopmentTeam\AutomaticPhpMyAdminUpdateService;
use Spatie\QueryBuilder\QueryBuilder;

class AutomaticPhpMyAdminController extends Controller
{
    protected $alert;

    /**
     * @var \Pterodactyl\Services\AktiCubeDevelopmentTeam\AutomaticPhpMyAdminCreationService
     */
    protected $creationService;

    /**
     * @var \Pterodactyl\Services\AktiCubeDevelopmentTeam\AutomaticPhpMyAdminUpdateService
     */
    protected $updateService;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\DatabaseHostRepository
     */
    protected $databaseHostRepository;

    public function __construct(
        AlertsMessageBag $alert,
        AutomaticPhpMyAdminCreationService $creationService,
        AutomaticPhpMyAdminUpdateService $updateService,
        DatabaseHostRepository $databaseHostRepository,
    )
    {
        $this->alert = $alert;
        $this->creationService = $creationService;
        $this->updateService = $updateService;
        $this->databaseHostRepository = $databaseHostRepository;
    }

    /**
     * Return the admin index view.
     */
    public function index(): View
    {
        $automatic_pmas = QueryBuilder::for(AutomaticPhpMyAdmin::query())->allowedFilters(['name', 'url'])->paginate(10);

        return view('admin.automatic_phpmyadmin.index', [
            'automatic_pmas' => $automatic_pmas,
        ]);
    }

    /**
     * Display the form to create a new installation of phpMyAdmin
     */
    public function create()
    {
        return view('admin.automatic_phpmyadmin.new', [
            'database_hosts' => $this->databaseHostRepository->all(),
        ]);
    }

    /**
     * Create a new installation of phpMyAdmin
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(StoreAutomaticPhpMyAdminRequest $request): RedirectResponse
    {
        try {
            $data = $request->normalize();
            $automatic_pma = $this->creationService->handle($data);
            $this->alert->success('This phpMyAdmin installation was successfuly created.')->flash();

            return redirect()->route('admin.automatic-phpmyadmin.view', $automatic_pma->id);
        } catch (\Exception $exception) {
            $this->alert->danger($exception->getMessage())->flash();

            return redirect()->route('admin.automatic-phpmyadmin');
        }
    }

    /**
     * Display the form to edit an installation of phpMyAdmin
     */
    public function view(int $id)
    {
        $automatic_pma = AutomaticPhpMyAdmin::query()->where('id', $id)->firstOrFail();

        return view('admin.automatic_phpmyadmin.view', [
            'automatic_pma' => $automatic_pma,
            'database_hosts' => $this->databaseHostRepository->all(),
        ]);
    }

    /**
     * Update the specified installation of phpMyAdmin
     */
    public function update(UpdateAutomaticPhpMyAdminRequest $request, int $id): RedirectResponse
    {
        $automatic_pma = AutomaticPhpMyAdmin::query()->where('id', $id)->firstOrFail();

        try {
            $data = $request->normalize();
            $this->updateService->handle($automatic_pma, $data);
            $this->alert->success('This phpMyAdmin installation was successfuly updated.')->flash();

            return redirect()->route('admin.automatic-phpmyadmin.view', $automatic_pma->id);
        } catch (\Exception $exception) {
            $this->alert->danger($exception->getMessage())->flash();

            return redirect()->route('admin.automatic-phpmyadmin');
        }
    }

    /**
     * Delete a phpMyAdmin installation
     */
    public function destroy(int $id): RedirectResponse
    {
        if (AutomaticPhpMyAdmin::query()->where('id', $id)->delete()) {
            $this->alert->success('This phpMyAdmin installation has been successfully deleted.')->flash();
        } else {
            $this->alert->danger('An error occurred while deleting this phpMyAdmin installation.')->flash();
        }

        return redirect()->route('admin.automatic-phpmyadmin');
    }
}
