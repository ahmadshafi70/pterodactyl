<?php
  $response = cache()->remember('product-subdomainmanager', 30 * 60, function () {
    return @file_get_contents("https://api.2038.buzz/products/subdomainmanager", false, stream_context_create([
      'http' => [
        'timeout' => 1
      ]
    ]));
  });

  if (!$response) {
    $version = 'Unknown';
    $providers = [];
    $changelog = [];
  } else {
    $data = json_decode($response, true);

    $version = $data['product']['version'];
    $providers = array_values($data['providers']);
    $changelog = [];

    foreach ($data['changelogs'] as $key => $change) {
      $changelog[] = [
        'version' => $key,
        'text' => $change['content'],
        'created' => $change['created']
      ];
    }
  }

  $nonceIdentifier = '9822824fd716c8b98e3fa884abd589f8';
  $nonceIdentifierWithoutReplacement = '%%__NONCE' . '__%%';
?>

<div class="row">
  <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
    <div class="box {{ $version !== 'Unknown' ? $version !== "1.0.2" ? 'box-danger' : 'box-primary' : 'box-primary' }}">
      <div class="box-header with-border">
        <h3 class="box-title"><i class='bx bx-git-repo-forked' ></i> Information</h3>
      </div>
      <div class="box-body">
        <p>
          Thank you for purchasing <b>Subdomain Manager</b>! You are currently using version <code>1.0.2</code> (latest version is <code>{{ $version }}</code>).
          If you have any questions or need help, please visit our <a href="https://discord.gg/0x7f6" target="_blank">Discord</a>.
          <b>{{ $nonceIdentifier === $nonceIdentifierWithoutReplacement ? "This is an indev version of the product!" : "" }}</b>
        </p>

        <div class="table-responsive" style="max-height: 250px; margin-bottom: 10px;">
          <table class="table">
            <thead>
              <tr>
                <th style="width: 10px">Version</th>
                <th style="width: 100px">Date</th>
                <th>Changes</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($changelog as $change)
                <tr>
                  <td style="{{ "1.0.2" === $change['version'] ? 'text-decoration: underline; font-weight: bold;' : '' }}">{{ $change['version'] }}</td>
                  <td>{{ Carbon\Carbon::parse($change['created'])->format('Y-m-d') }}</td>
                  <td style="white-space: pre-wrap;">{{ $change['text'] }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="row">
          @foreach ($providers as $provider)
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
              <a href="{{ $provider['link'] }}" target="_blank" class="btn btn-primary btn-block"><i class='bx bx-store'></i> {{ $provider['name'] }}</a>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
    <div class="box">
      <div class="box-header with-border">
        <h3 class="box-title"><i class='bx bx-cog'></i> Configuration</h3>
      </div>
      <div class="box-body">
        <form method="post" action="{{ route('admin.extensions.subdomainmanager.index') }}">
          {{ csrf_field() }}
          <div class="form-group">
            <input type="hidden" name="type" value="configuration">
    
            <label for="cloudflare_token">Cloudflare API Token</label>
            <input type="text" placeholder="API Token" name="cloudflare_token" id="cloudflare_token" class="form-control" value="{{ $blueprint->dbGet('subdomainmanager', 'cloudflare_token') ? '<hidden>' : '' }}">
            <p class="text-muted">This is the API token that will be used to interact with the Cloudflare API. <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank">Get your API token here</a>.</p>

            <label for="subdomain_limit">Server Subdomain Limit</label>
            <input type="number" placeholder="5" name="subdomain_limit" id="subdomain_limit" class="form-control" value="{{ $blueprint->dbGet('subdomainmanager', 'subdomain_limit') ?: '5' }}">
          </div>
          <button type="submit" class="btn btn-primary">Save</button>
          <button name="_method" value="PUT" class="btn btn-default" {{ !$blueprint->dbGet('subdomainmanager', 'cloudflare_token') ? 'disabled' : '' }}>Test</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
    <div class="box">
      <div class="box-header with-border">
        <h3 class="box-title"><i class='bx bxs-info-square'></i> Banner</h3>
      </div>
      <div class="box-body">
        <img src="/extensions/subdomainmanager/subdomainmanager_banner.jpg" class="img-rounded" alt="Banner" style="width: 100%;">
      </div>
    </div>
  </div>
</div>

@if($viewing === 'subdomains')
  <div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title"><i class='bx bx-chart'></i> Subdomains</h3>
        </div>
        <div class="box-body">
          <a style="margin-bottom: 10px" href="{{ route('admin.extensions.subdomainmanager.index') }}?viewing=create-subdomain" class="btn btn-primary"><i class='bx bx-plus'></i> Create Subdomain</a>

          @if(count($subdomains) > 0)
            <table class="table">
              <thead>
                <tr>
                  <th>Id</th>
                  <th>Domain</th>
                  <th>Zone ID</th>
                  <th>Subdomains</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($subdomains as $subdomain)
                  <tr>
                    <td>{{ $subdomain->id }}</td>
                    <td>{{ $subdomain->domain }}</td>
                    <td>{{ $subdomain->zone_id }}</td>
                    <td>{{ $subdomain->serverSubdomains()->count() }}</td>
                    <td>
                      <a href="{{ route('admin.extensions.subdomainmanager.index') }}?viewing=subdomain&subdomain={{ $subdomain->id }}" class="btn btn-primary btn-xs"><i class='bx bx-edit'></i> Edit</a>
                      <form method="post" action="{{ route('admin.extensions.subdomainmanager.index') }}/subdomain/{{ $subdomain->id }}" style="display: inline;">
                        @csrf
                        <button name="_method" value="DELETE" class="btn btn-danger btn-xs"><i class='bx bx-trash'></i> Delete</button>
                      </form>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          @else
            <div>
              No subdomains found.
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@elseif($viewing === 'create-subdomain')
  <div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title"><i class='bx bx-plus'></i> Create Subdomain</h3>
        </div>
        <div class="box-body">
          <form method="post" action="{{ route('admin.extensions.subdomainmanager.index') }}">
            @csrf
            <input type="hidden" name="type" value="subdomain-new">
            <div class="form-group">
              <label for="domain">Domain</label>
              <input type="text" class="form-control" id="domain" name="domain" required value="{{ old('domain') }}">
            </div>
            <div class="form-group">
              <label for="zone_id">Cloudflare Zone ID</label>
              <input type="text" class="form-control" id="zone_id" name="zone_id" required value="{{ old('zone_id') }}">
            </div>
            <div class="form-group">
              <label for="disallowed_subdomains_regexes">Disallowed Subdomain Regexes</label>
              <textarea class="form-control" rows="5" id="disallowed_subdomains_regexes" name="disallowed_subdomains_regexes" style="resize: none;">{{ old('disallowed_subdomains_regexes') }}</textarea>
              <p class="text-muted">This is a newline-seperated array of regexes that are disallowed in subdomains. Example: <code>/^www$/</code></p>
            </div>
            <div class="form-group">
              <label for="api_data">Cloudflare API Data</label>
              <textarea class="form-control" rows="9" id="api_data" name="api_data" style="resize: none;">{{ old('api_data', $defaultApiData) }}</textarea>
              <p class="text-muted">This is the data that will be sent to the Cloudflare API @ <code>POST /zones/{zone_id}/dns_records/batch</code> for each new subdomain.</p>
            </div>
            <div class="form-group">
              <label for="eggs">Eggs</label>
              <select class="form-control" id="eggs" name="eggs[]" multiple>
                @foreach(DB::table('eggs')->get() as $egg)
                  <option value="{{ $egg->id }}">{{ $egg->name }}</option>
                @endforeach
              </select>
            </div>

            <button type="submit" class="btn btn-primary"><i class='bx bx-save'></i> Save</button>
          </form>
        </div>
      </div>
    </div>
  </div>
@elseif($viewing === 'subdomain')
  <div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title"><i class='bx bx-edit'></i> Edit Subdomain</h3>
        </div>
        <div class="box-body">
          <form method="post" action="{{ route('admin.extensions.subdomainmanager.index') }}?viewing=subdomain&subdomain={{ $subdomain->id }}">
            @csrf
            <input type="hidden" name="type" value="subdomain-edit">
            <input type="hidden" name="id" value="{{ $subdomain->id }}">
            <div class="form-group">
              <label for="domain">Domain</label>
              <input type="text" readonly class="form-control" id="domain" name="domain" required value="{{ old('domain', $subdomain->domain) }}">
            </div>
            <div class="form-group">
              <label for="zone_id">Cloudflare Zone ID</label>
              <input type="text" readonly class="form-control" id="zone_id" name="zone_id" required value="{{ old('zone_id', $subdomain->zone_id) }}">
            </div>
            <div class="form-group">
              <label for="disallowed_subdomains_regexes">Disallowed Subdomain Regexes</label>
              <textarea class="form-control" rows="5" id="disallowed_subdomains_regexes" name="disallowed_subdomains_regexes" style="resize: none;">{{ old('disallowed_subdomains_regexes', implode("\n", $subdomain->disallowed_subdomains_regexes)) }}</textarea>
              <p class="text-muted">This is a newline-seperated array of regexes that are disallowed in subdomains. Example: <code>/^www$/</code></p>
            </div>
            <div class="form-group">
              <label for="api_data">Cloudflare API Data</label>
              <textarea class="form-control" rows="9" id="api_data" name="api_data" style="resize: none;">{{ old('api_data', json_encode($subdomain->api_data, JSON_PRETTY_PRINT)) }}</textarea>
              <p class="text-muted">This is the data that will be sent to the Cloudflare API @ <code>POST /zones/{zone_id}/dns_records/batch</code> for each new subdomain.</p>
            </div>
            <div class="form-group">
              <label for="eggs">Eggs</label>
              <select class="form-control" id="eggs" name="eggs[]" multiple>
                @foreach(DB::table('eggs')->get() as $egg)
                  <option value="{{ $egg->id }}" {{ in_array($egg->id, $subdomain->eggs) ? 'selected' : '' }}>{{ $egg->name }}</option>
                @endforeach
              </select>
            </div>

            <button type="submit" class="btn btn-primary"><i class='bx bx-save'></i> Save</button>
            <a href="{{ route('admin.extensions.subdomainmanager.index') }}" class="btn btn-default"><i class='bx bx-arrow-back'></i> Back</a>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title"><i class='bx bx-server'></i> Subdomains</h3>
        </div>
        <div class="box-body table-responsive no-padding">
          <table class="table table-hover">
            <tr>
              <th>Server ID</th>
              <th>Server Name</th>
              <th>Subdomain</th>
              <th>Owner</th>
              <th>Service</th>
            </tr>
            @foreach($server_subdomains as $subdomain)
              <tr data-server="{{ $subdomain->server->uuid }}">
                <td><code>{{ $subdomain->server->uuidShort }}</code></td>
                <td><a href="{{ route('admin.servers.view', $subdomain->server->id) }}">{{ $subdomain->server->name }}</a></td>
                <td><code>{{ $subdomain->subdomain }}.{{ $subdomain->domain->domain }}</code></td>
                <td><a href="{{ route('admin.users.view', $subdomain->server->user->id) }}">{{ $subdomain->server->user->username }}</a></td>
                <td>{{ $subdomain->server->egg->nest->name }} ({{ $subdomain->server->egg->name }})</td>
              </tr>
            @endforeach
          </table>
          @if($server_subdomains->hasPages())
            <div class="box-footer with-border">
              <div class="col-md-12 text-center">{!! $server_subdomains->render() !!}</div>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>  
@endif

@endsection

@section('footer-scripts')
@parent
<script>
  $('#eggs').select2();
</script>