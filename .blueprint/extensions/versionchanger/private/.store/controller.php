<?php

namespace Pterodactyl\Http\Controllers\Admin\Extensions\versionchanger;

use Illuminate\View\View;
use Pterodactyl\Models\Egg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;
use Pterodactyl\BlueprintFramework\Extensions\versionchanger\VersionChangerUtils;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Admin\BlueprintAdminLibrary;

class versionchangerSettingsFormRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'type' => 'required|string',
        ];
    }
}

class versionchangerExtensionController extends Controller
{
    public function __construct(
        private ViewFactory $view,
        private BlueprintAdminLibrary $blueprint,
        private VersionChangerUtils $utils,
    ) {}

    public function index(): View
    {
        return $this->view->make(
            'admin.extensions.{identifier}.index', [
                'root' => '/admin/extensions/{identifier}',
                'blueprint' => $this->blueprint,
                'types' => $this->utils->typesOrder(),
                'flat_types' => $this->utils->types(),
                'default_types' => $this->utils->defaultTypesOrder(),
                'stats' => $this->blueprint->dbGet('versionchanger', 'collect_stats') === '1',
                'stats_data' => json_decode($this->blueprint->dbGet('versionchanger', 'installs') ?: '{"types":{},"versions":{}}', true),
                'eggs' => Egg::query()->get(),
                'eggRules' => DB::table('minecraft_version_changer_eggs')->get(),
            ]
        );
    }

    public function put(): View
    {
        $this->blueprint->notify('Stats cleared');
        $this->blueprint->dbForget('versionchanger', 'installs');

        return $this->index();
    }

    public function post(versionchangerSettingsFormRequest $request): View
    {
        $type = $request->input('type');

        switch ($type) {
            case 'configuration':
                $data = $this->validate($request, [
                    'mcvapi_url' => 'nullable|string|url',
                    'mcvapi_key' => 'nullable|string|size:64',
                    'mcvapi_types_order' => 'nullable|string',
                    'mcvapi_image_base_url' => 'nullable|string|url',
                    'mcvapi_image_format' => 'nullable|string|in:png,webp',
                    'collect_stats' => 'nullable|string|in:0,1',        
                ]);

                foreach ($data as $key => $value) {
                    if ($key === 'mcvapi_types_order') {
                        try {
                            json_encode(array_values(json_decode($value, true)), JSON_THROW_ON_ERROR & JSON_PRETTY_PRINT);
                        } catch (\Throwable $e) {
                            $this->blueprint->notify('Invalid JSON for types order');
                            continue;
                        }
                    }
        
                    $this->blueprint->dbSet('versionchanger', $key, $value);
                }

                $this->blueprint->notify('Applied new settings');
                break;

            case 'egg-rule-create':
                DB::table('minecraft_version_changer_eggs')->insert([
                    'types' => '[]',
                    'egg_id' => Egg::query()->first()->id,
                ]);

                $this->blueprint->notify('Egg-Type Override successfully created.');
                break;

            case 'egg-rule-update':
                $data = $this->validate($request, [
                    'id' => 'required|integer|exists:minecraft_version_changer_eggs,id',
                    'types' => 'required|array',
                    'types.*' => 'string|uppercase',
                    'egg_id' => 'integer|exists:eggs,id',
                ]);

                DB::table('minecraft_version_changer_eggs')->where('id', $data['id'])->update([
                    'types' => json_encode($data['types']),
                    'egg_id' => $data['egg_id'],
                ]);

                $this->blueprint->notify('Egg-Type Override successfully updated.');
                break;
        }

        return $this->index();
    }

    public function delete(Request $request): View
    {
        $id = $request->route('id');

        DB::table('minecraft_version_changer_eggs')->where('id', (int) $id)->delete();

        $this->blueprint->notify('Egg-Type Override successfully deleted.');

        return $this->index();
    }
}