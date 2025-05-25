<?php

namespace Pterodactyl\Http\Controllers\Admin\Extensions\subdomainmanager;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Jobs\Server\DeleteSubdomainsJob;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;
use Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Models\Subdomain;
use Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\SubdomainManagerUtils;
use Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Models\ServerSubdomain;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Admin\BlueprintAdminLibrary;

class subdomainmanagerPostFormRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'type' => 'required|string',
        ];
    }
}

class subdomainmanagerExtensionController extends Controller
{
    public function __construct(
        private ViewFactory $view,
        private BlueprintAdminLibrary $blueprint,
        private SubdomainManagerUtils $utils,
    ) {}

    public function index(): View
    {
        $queryParameters = request()->query();

        $subdomain = null;

        if (isset($queryParameters['subdomain'])) {
            /** @var Subdomain $subdomain */
            $subdomain = Subdomain::query()->where('id', $queryParameters['subdomain'])->first();
        }

        return $this->view->make(
            'admin.extensions.subdomainmanager.index', [
                'root' => '/admin/extensions/subdomainmanager',
                'blueprint' => $this->blueprint,
                'viewing' => isset($queryParameters['viewing']) ? $queryParameters['viewing'] : 'subdomains',
                'subdomains' => Subdomain::query()->get(),
                'subdomain' => $subdomain,
                'server_subdomains' => $subdomain
                    ? ServerSubdomain::query()->where('subdomain_id', $subdomain->id)->with([
                        'server', 'server.user', 'server.egg', 'server.egg.nest'
                    ])->paginate(25)->withQueryString()
                    : null,
                'defaultApiData' => json_encode([
                    'posts' => [
                        [
                            'type' => 'A',
                            'name' => '{subdomain}.{domain}',
                            'content' => '{ip_alias_forceip}',
                            'comment' => '{comment}',
                            'ttl' => 120,
                        ],
                        [
                            'type' => 'SRV',
                            'name' => '_minecraft._tcp.{subdomain}.{domain}',
                            'data' => [
                                'priority' => 0,
                                'weight' => 5,
                                'port' => '{port}',
                                'target' => '{subdomain}.{domain}',
                            ],
                            'comment' => '{comment}',
                            'ttl' => 120,
                        ],
                    ]
                ], JSON_PRETTY_PRINT),
            ]
        );
    }

    public function put(Request $request): View
    {
        $status = $this->utils->test();

        if ($status) {
            $this->blueprint->notify('Successfully connected to Cloudflare API.');
        } else {
            $this->blueprint->notify('Failed to connect to Cloudflare API.');
        }

        return $this->index();
    }

    public function post(subdomainmanagerPostFormRequest $request): View
    {
        $type = $request->input('type');

        switch ($type) {
            case 'configuration':
                $data = $this->validate($request, [
                    'cloudflare_token' => 'required|string',
                    'subdomain_limit' => 'required|integer|min:1',
                ]);

                foreach ($data as $key => $value) {
                    if ($key === 'cloudflare_token' && $value === '<hidden>') {
                        continue;
                    }

                    $this->blueprint->dbSet('subdomainmanager', $key, $value);
                }

                $this->blueprint->notify('Applied new settings');
                break;

            case 'subdomain-new':
                $data = $this->validate($request, [
                    'domain' => 'required|string',
                    'zone_id' => 'required|string',
                    'eggs' => 'array',
                    'eggs.*' => 'required|integer|exists:eggs,id',
                    'disallowed_subdomains_regexes' => 'nullable|string',
                    'api_data' => 'required|string',
                ]);

                $data['eggs'] = isset($data['eggs']) ? $data['eggs'] : [];
                $data['disallowed_subdomains_regexes'] = explode("\n", $data['disallowed_subdomains_regexes'] ?? '');

                try {
                    $data['api_data'] = json_decode($data['api_data'], true);
                } catch (\Exception $exception) {
                    $this->blueprint->notify('Invalid JSON provided for API data.', 'error');
                    return $this->index();
                }

                Subdomain::create($data);

                $this->blueprint->notify('Subdomain successfully created.');
                break;

            case 'subdomain-edit':
                $data = $this->validate($request, [
                    'id' => 'required|integer',
                    'eggs' => 'array',
                    'eggs.*' => 'required|integer|exists:eggs,id',
                    'disallowed_subdomains_regexes' => 'nullable|string',
                    'api_data' => 'required|string',
                ]);

                $data['eggs'] = isset($data['eggs']) ? $data['eggs'] : [];
                $data['disallowed_subdomains_regexes'] = explode("\n", $data['disallowed_subdomains_regexes'] ?? '');

                try {
                    $data['api_data'] = json_decode($data['api_data'], true);
                } catch (\Exception $exception) {
                    $this->blueprint->notify('Invalid JSON provided for API data.', 'error');
                    return $this->index();
                }

                Subdomain::query()->where('id', $data['id'])->update($data);

                $this->blueprint->notify('Subdomain successfully updated.');
                break;
        }

        return $this->index();
    }

    public function delete(Request $request): View
    {
        $id = $request->route('id');

        switch ($request->route('target')) {
            case 'subdomain':
                DeleteSubdomainsJob::dispatch(ServerSubdomain::query()->where('subdomain_id', $id)->pluck('id')->toArray(), $id);
                $this->blueprint->notify('Subdomain deletion queued.');

                break;
        }

        return $this->index();
    }
}