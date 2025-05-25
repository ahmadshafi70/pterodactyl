<?php

namespace Pterodactyl\Http\Controllers\Admin\Extensions\serverimporter;

use Illuminate\View\View;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Admin\BlueprintAdminLibrary as BlueprintExtensionLibrary;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class serverimporterExtensionController extends Controller
{
  public function __construct(
    private ViewFactory $view,
    private BlueprintExtensionLibrary $blueprint,
  ) {}

  public function index(): View
	{
		return $this->view->make(
			'admin.extensions.serverimporter.index', [
				'root' => '/admin/extensions/serverimporter',
				'blueprint' => $this->blueprint,
			]
		);
	}

	public function post(serverimporterSettingsFormRequest $request): View
	{
		$this->blueprint->notify('Applied new settings');

		foreach ($request->validated() as $key => $value) {
			$this->blueprint->dbSet('serverimporter', $key, $value);
		}

		return $this->view->make(
			'admin.extensions.serverimporter.index', [
				'root' => '/admin/extensions/serverimporter',
				'blueprint' => $this->blueprint,
			]
		);
	}
}

class serverimporterSettingsFormRequest extends AdminFormRequest
{
  public function rules(): array
  {
    return [
      'server_profile_limit' => 'nullable|integer',
			'credential_profile_limit' => 'nullable|integer',
			'skip_login_check' => 'nullable|string|in:0,1',
			'skip_files_check' => 'nullable|string|in:0,1',
    ];
  }
}