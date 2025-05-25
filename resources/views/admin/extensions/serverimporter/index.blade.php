@extends('layouts.admin')
<?php 
    $EXTENSION_ID = "serverimporter";
    $EXTENSION_NAME = "Server Importer";
    $EXTENSION_VERSION = "1.1.2";
    $EXTENSION_DESCRIPTION = "Allow importing Servers using SFTP/FTP Credentials from any server to your server.";
    $EXTENSION_ICON = "/assets/extensions/serverimporter/serverimporter_icon.jpg";
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
  $id = 8;

  $response = cache()->remember('product-' . $id, 30 * 60, function () use ($id) {
    return @file_get_contents("https://products.rjns.dev/api/products/{$id}", false, stream_context_create([
      'http' => [
        'timeout' => 1
      ]
    ]));
  });

  if ($response === FALSE) {
    $version = 'Unknown';
    $providers = [];
  } else {
    $data = json_decode($response, true);

    $version = $data['product']['version'];
    $providers = array_values($data['providers']);
  }

  $nonceIdentifier = 'f78f1ad7ebb8e5aa75098be49f8fc4c2';
  $nonceIdentifierWithoutReplacement = '%%__NONCE' . '__%%';
?>

<div class="row">
  <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
    <div class="box {{ $version !== 'Unknown' ? $version !== "1.1.2" ? 'box-danger' : 'box-primary' : 'box-primary' }}">
      <div class="box-header with-border">
        <h3 class="box-title"><i class='bx bx-git-repo-forked' ></i> Information</h3>
      </div>
      <div class="box-body">
        <p>
          Thank you for purchasing <b>Server Importer</b>! You are currently using version <code>1.1.2</code> (latest version is <code>{{ $version }}</code>).
          If you have any questions or need help, please visit our <a href="https://rjansen.dev/discord" target="_blank">Discord</a>.
          <b>{{ $nonceIdentifier === $nonceIdentifierWithoutReplacement ? "This is an indev version of the product!" : "" }}</b>
        </p>

        <div class="row" style="margin-top: 10px;">
          @foreach ($providers as $provider)
            <div class="col-md-6">
              <a href="{{ $provider['link'] }}" target="_blank" class="btn btn-primary btn-block"><i class='bx bx-store'></i> {{ $provider['name'] }}</a>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
    <div class="box">
      <div class="box-header with-border">
        <h3 class="box-title"><i class='bx bxs-info-square'></i> Banner</h3>
      </div>
      <div class="box-body">
        <img src="/extensions/serverimporter/serverimporter_banner.jpg" class="img-rounded img-responsive" alt="Banner" style="max-width: 600px; margin: 0 auto;">
      </div>
    </div>
  </div>

  <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
    <div class="box">
      <div class="box-header with-border">
        <h3 class="box-title"><i class='bx bx-cog'></i> Configuration</h3>
      </div>
      <div class="box-body">
        <form method="post">
          {{ csrf_field() }}
          <div class="form-group">
            <label for="server_profile_limit">Max Server Profiles (per user)</label>
            <input type="number" min="1" placeholder="100" name="server_profile_limit" id="server_profile_limit" class="form-control" value="{{ $blueprint->dbGet('serverimporter', 'server_profile_limit') ?: '100' }}">

            <label for="credential_profile_limit" style="margin-top: 10px">Max Credentials (per user)</label>
            <input type="number" min="1" placeholder="100" name="credential_profile_limit" id="credential_profile_limit" class="form-control" value="{{ $blueprint->dbGet('serverimporter', 'credential_profile_limit') ?: '100' }}">

            <label for="skip_login_check" style="margin-top: 10px">Skip Login Check</label>
            <select name="skip_login_check" id="skip_login_check" class="form-control">
              <option value="0" {{ $blueprint->dbGet('serverimporter', 'skip_login_check') == 0 ? 'selected' : '' }}>No</option>
              <option value="1" {{ $blueprint->dbGet('serverimporter', 'skip_login_check') == 1 ? 'selected' : '' }}>Yes</option>
            </select>

            <label for="skip_files_check" style="margin-top: 10px">Skip Files Check</label>
            <select name="skip_files_check" id="skip_files_check" class="form-control">
              <option value="0" {{ $blueprint->dbGet('serverimporter', 'skip_files_check') == 0 ? 'selected' : '' }}>No</option>
              <option value="1" {{ $blueprint->dbGet('serverimporter', 'skip_files_check') == 1 ? 'selected' : '' }}>Yes</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Save</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
