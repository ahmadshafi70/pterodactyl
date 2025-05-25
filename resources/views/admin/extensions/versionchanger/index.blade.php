@extends('layouts.admin')
<?php 
    $EXTENSION_ID = "versionchanger";
    $EXTENSION_NAME = "Minecraft Version Changer";
    $EXTENSION_VERSION = "1.1.5";
    $EXTENSION_DESCRIPTION = "Minecraft Version Changer allows you to adjust your minecraft servers version instantly.";
    $EXTENSION_ICON = "/assets/extensions/versionchanger/versionchanger_icon.jpg";
?>
@include('blueprint.admin.template')

@section('title')
	{{ $EXTENSION_NAME }}
@endsection

@section('content-header')
	@yield('extension.header')
@endsection

@section('content')
@yield('extension.config')
@yield('extension.description')
<?php
  $response = cache()->remember('product-versionchanger', 30 * 60, function () {
    return @file_get_contents("https://api.2038.buzz/products/versionchanger", false, stream_context_create([
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

  $nonceIdentifier = '38dfd3d0dc9852beadfe778daf237e33';
  $nonceIdentifierWithoutReplacement = '%%__NONCE' . '__%%';
?>

<div class="row">
  <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
    <div class="box {{ $version !== 'Unknown' ? $version !== "1.1.5" ? 'box-danger' : 'box-primary' : 'box-primary' }}">
      <div class="box-header with-border">
        <h3 class="box-title"><i class='bx bx-git-repo-forked' ></i> Information</h3>
      </div>
      <div class="box-body">
        <p>
          Thank you for purchasing <b>Minecraft Version Changer</b>! You are currently using version <code>1.1.5</code> (latest version is <code>{{ $version }}</code>).
          If you have any questions or need help, please visit our <a href="https://discord.gg/0x7f6" target="_blank">Discord</a>.
          <b>{{ $nonceIdentifier === $nonceIdentifierWithoutReplacement ? "This is an indev version of the product!" : "" }}</b>
        </p>

        <div class="table-responsive" style="max-height: 500px; margin-bottom: 10px;">
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
                  <td style="{{ "1.1.5" === $change['version'] ? 'text-decoration: underline; font-weight: bold;' : '' }}">{{ $change['version'] }}</td>
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
        <form method="post" action="{{ route('admin.extensions.versionchanger.index') }}">
          {{ csrf_field() }}
          <div class="form-group">
            <input type="hidden" name="type" value="configuration">

            <label for="mcvapi_url">MCVAPI URL</label>
            <input type="text" placeholder="https://versions.mcjars.app" name="mcvapi_url" id="mcvapi_url" class="form-control" value="{{ $blueprint->dbGet('versionchanger', 'mcvapi_url') ?: 'https://versions.mcjars.app' }}">
    
            <label for="mcvapi_key" style="margin-top: 10px">MCVAPI API Key <small>(<a href="https://mcjars.app/organizations" target="_blank">Get it here</a>)</small></label>
            <input type="text" placeholder="Can be left empty" name="mcvapi_key" id="mcvapi_key" class="form-control" value="{{ $blueprint->dbGet('versionchanger', 'mcvapi_key') ?: '' }}">

            <label for="mcvapi_types_order" style="margin-top: 10px">MCVAPI Types Order</label>
            <textarea name="mcvapi_types_order" id="mcvapi_types_order" class="form-control" rows="5" style="resize: none">{{ json_encode($types, JSON_PRETTY_PRINT) }}</textarea>

            <label for="mcvapi_types_default" style="margin-top: 10px">Default MCVAPI Types Order</label>
            <textarea name="mcvapi_types_default" id="mcvapi_types_default" class="form-control" rows="5" disabled style="resize: none">{{ json_encode($default_types, JSON_PRETTY_PRINT) }}</textarea>

            <label for="mcvapi_image_base_url" style="margin-top: 10px">MCVAPI Icon Base URL</label>
            <input type="text" placeholder="https://s3.mcjars.app/icons/" name="mcvapi_image_base_url" id="mcvapi_image_base_url" class="form-control" value="{{ $blueprint->dbGet('versionchanger', 'mcvapi_image_base_url') ?: 'https://s3.mcjars.app/icons/' }}">

            <label for="mcvapi_image_format" style="margin-top: 10px">MCVAPI Icon Format</label>
            <select name="mcvapi_image_format" id="mcvapi_image_format" class="form-control">
              <option value="png" {{ $blueprint->dbGet('versionchanger', 'mcvapi_image_format') !== 'webp' ? 'selected' : '' }}>PNG</option>
              <option value="webp" {{ $blueprint->dbGet('versionchanger', 'mcvapi_image_format') === 'webp' ? 'selected' : '' }}>WebP</option>
            </select>

            <label for="collect_stats" style="margin-top: 10px">Collect Stats (fully local)</label>
            <select name="collect_stats" id="collect_stats" class="form-control">
              <option value="1" {{ $stats ? 'selected' : '' }}>Yes</option>
              <option value="0" {{ !$stats ? 'selected' : '' }}>No</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Save</button>
          <button name="_method" value="PUT" class="btn btn-danger" {{ !count(array_keys($stats_data['types'])) ? 'disabled' : '' }}>Reset Local Stats</button>
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
        <img src="/extensions/versionchanger/versionchanger_banner.jpg" class="img-rounded" alt="Banner" style="width: 100%;">
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
    <div class="box">
      <div class="box-header with-border">
        <h3 class="box-title"><i class='bx bx-chart'></i> Type-Egg Overrides</h3>
      </div>
      <div class="box-body">
        <form method="post" action="{{ route('admin.extensions.versionchanger.index') }}" style="display: inline;">
          @csrf
          <input type="hidden" name="type" value="egg-rule-create">
          <button style="margin-bottom: 10px;" type="submit" class="btn btn-primary"><i class='bx bx-plus'></i> Add</button>
        </form>

        @if(count($eggRules) > 0)
          <table class="table">
            <thead>
              <tr>
                <th>Id</th>
                <th>Types</th>
                <th>Egg</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($eggRules as $eggRule)
                <tr>
                  <form method="post" action="{{ route('admin.extensions.versionchanger.index') }}" style="display: inline;">
                    @csrf
                    <input type="hidden" name="type" value="egg-rule-update">
                    <input type="hidden" name="id" value="{{ $eggRule->id }}">
                    <td>{{ $eggRule->id }}</td>
                    <td style="width: 30%">
                      <select name="types[]" id="types" class="form-control" multiple>
                        @foreach($flat_types as $type => $data)
                          <option value="{{ $type }}" {{ in_array($type, json_decode($eggRule->types)) ? 'selected' : '' }}>{{ $data['name'] }}</option>
                        @endforeach
                      </select>
                    </td>
                    <td style="width: 50%">
                      <select name="egg_id" id="egg_id" class="form-control">
                        @foreach($eggs as $egg)
                          <option value="{{ $egg->id }}" {{ $egg->id == $eggRule->egg_id ? 'selected' : '' }}>{{ $egg->name }}</option>
                        @endforeach
                      </select>
                    </td>
                    <td style="width: 20%">
                      <button type="submit" class="btn btn-primary"><i class='bx bx-save'></i> Save</button>
                      <button type="button" class="btn btn-danger" id="delete-rule" data-id="{{ $eggRule->id }}"><i class='bx bx-trash'></i> Delete</button>
                    </td>
                  </form>
                </tr>
              @endforeach
            </tbody>
          </table>
        @else
          <div>
            No Type-Egg overrides found.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<div class="row">
  <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
    <div class="box">
      <div class="box-header with-border">
        <h3 class="box-title"><i class='bx bx-chart'></i> Local Statistics</h3>
      </div>
      <div class="box-body">
        <div id="types_chart" class="col-lg-6" style="width: 50%; height: 300px;"></div>
        <div id="versions_chart" class="col-lg-6" style="width: 50%; height: 300px;"></div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
    <div class="box">
      <div class="box-header with-border">
        <h3 class="box-title"><i class='bx bx-chart'></i> Global Statistics</h3>
      </div>
      <div class="box-body">
        <div id="global_types_chart" class="col-lg-6" style="width: 50%; height: 300px;"></div>
        <div id="global_versions_chart" class="col-lg-6" style="width: 50%; height: 300px;"></div>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  google.charts.load('current', {'packages':['corechart']});
  google.charts.setOnLoadCallback(drawChartTypes);
  google.charts.setOnLoadCallback(drawChartVersions);

  // chart for types total
  function drawChartTypes() {
    var data = new google.visualization.DataTable();
    data.addColumn('string', 'Type');
    data.addColumn('number', 'Total');

    data.addRows([
      @foreach ($stats_data['types'] as $type => $type_data)
        ['{{ $type }}', {{ $type_data['total'] }}],
      @endforeach
    ]);

    var options = {
      title: 'Downloads per Type',
      titleTextStyle: {
        color: 'white'
      },
      is3D: true,
      backgroundColor: 'transparent',
      legend: {
        textStyle: {
          color: 'white'
        }
      },
    };

    var chart = new google.visualization.PieChart(document.getElementById('types_chart'));
    chart.draw(data, options);
  }

  // chart for versions total
  function drawChartVersions() {
    var data = new google.visualization.DataTable();
    data.addColumn('string', 'Version');
    data.addColumn('number', 'Total');

    data.addRows([
      @foreach ($stats_data['versions'] as $version => $total)
        ['{{ $version }}', {{ $total }}],
      @endforeach
    ]);

    var options = {
      title: 'Downloads per Version',
      titleTextStyle: {
        color: 'white'
      },
      is3D: true,
      backgroundColor: 'transparent',
      legend: {
        textStyle: {
          color: 'white'
        }
      },
    };

    var chart = new google.visualization.PieChart(document.getElementById('versions_chart'));
    chart.draw(data, options);
  }

  // fetch global stats
  Promise.all([
    fetch('https://versions.mcjars.app/api/v2/lookups/types').then(response => response.json()),
    fetch('https://versions.mcjars.app/api/v2/lookups/versions').then(response => response.json())
  ]).then(([types, versions]) => {
    if (!types.success || !versions.success) {
      return;
    }

    google.charts.setOnLoadCallback(drawChartGlobalTypes);
    google.charts.setOnLoadCallback(drawChartGlobalVersions);

    // chart for global types total
    function drawChartGlobalTypes() {
      var data = new google.visualization.DataTable();
      data.addColumn('string', 'Type');
      data.addColumn('number', 'Total');

      data.addRows([
        ...Object.entries(types.types).map(([type, data]) => [type, data.total])
      ]);

      var options = {
        title: 'Downloads per Type',
        titleTextStyle: {
          color: 'white'
        },
        is3D: true,
        backgroundColor: 'transparent',
        legend: {
          textStyle: {
            color: 'white'
          }
        },
      };

      var chart = new google.visualization.PieChart(document.getElementById('global_types_chart'));
      chart.draw(data, options);
    }

    // chart for global versions total
    function drawChartGlobalVersions() {
      var data = new google.visualization.DataTable();
      data.addColumn('string', 'Version');
      data.addColumn('number', 'Total');

      data.addRows([
        ...Object.entries(versions.versions).map(([version, data]) => [version, data.total])
      ]);

      var options = {
        title: 'Downloads per Version',
        titleTextStyle: {
          color: 'white'
        },
        is3D: true,
        backgroundColor: 'transparent',
        legend: {
          textStyle: {
            color: 'white'
          }
        },
      };

      var chart = new google.visualization.PieChart(document.getElementById('global_versions_chart'));
      chart.draw(data, options);
    }
  })
</script>

@endsection

@section('footer-scripts')
@parent
<script>
  $('select[id*="types"]').select2();

  $('.btn-danger').slice(1).on('click', function() {
    let id = $(this).data('id');
    let form = $('<form>', {
      'method': 'POST',
      'action': '{{ route("admin.extensions.versionchanger.index") }}/egg-rule/' + id
    });
    
    form.append('@csrf');
    form.append($('<input>', {
      'type': 'hidden',
      'name': '_method',
      'value': 'DELETE'
    }));
    
    $(document.body).append(form);
    form.submit();
  });
</script>
@endsection
