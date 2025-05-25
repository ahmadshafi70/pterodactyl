
@extends('layouts.admin')

@section('title')
    Creating a new phpMyAdmin installation
@endsection

@section('content-header')
    <h1>Automatic-phpMyAdmin<small>Create a new phpMyAdmin installation.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.automatic-phpmyadmin') }}">Automatic phpMyAdmin</a></li>
        <li class="active">New</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <form method="post">
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Characteristics</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <label for="name" class="control-label">Name <span class="field-required"></span></label>
                            <div>
                                <input type="text" autocomplete="off" name="name" class="form-control" value="{{ old('name') }}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="description" class="control-label">Description <span class="field-optional"></span></label>
                            <textarea name="description" id="pDescription" rows="4" class="form-control">{{ old('description') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="url" class="control-label">URL <span class="field-required"></span></label>
                            <div>
                                <input type="text" autocomplete="off" name="url" class="form-control" value="{{ old('url') }}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ssh_key" class="control-label">Cookie Name <span class="field-required"></span></label>
                            <div>
                                <input type="text" autocomplete="off" name="cookie_name" class="form-control" value="{{ old('cookie_name') }}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ssh_git_repo" class="control-label">Cookie Domain <span class="field-required"></span></label>
                            <div>
                                <input type="text" autocomplete="off" name="cookie_domain" value="{{ old('cookie_domain') }}" class="form-control"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Encryption Key <span class="field-required"></span></label>
                            <div>
                                <input type="text" autocomplete="off" name="encryption_key" value="{{ old('encryption_key') }}" class="form-control"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Encryption IV <span class="field-required"></span></label>
                            <div>
                                <input type="text" autocomplete="off" name="encryption_iv" value="{{ old('encryption_iv') }}" class="form-control"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">phpMyAdmin Server ID <span class="field-optional"></span></label>
                            <div>
                                <input type="text" autocomplete="off" name="phpmyadmin_server_id" value="{{ old('phpmyadmin_server_id') }}" class="form-control"/>
                            </div>
                            <p class="small text-muted no-margin">The corresponding server ID in phpMyAdmin configuration, can be set later but is needed, or it just won't work. When the Database Host selected is none, it is used as the starting number for servers IDs in phpMyAdmin config and making it corresponding to the IDs of the Databases Hosts in ascending order. But when there is a selected Database Host, it'll be used as the server ID for the phpMyAdmin configuration.</p>
                        </div>
                        <div class="form-group">
                            <label for="pDatabaseId" class="control-label">Assigned Database Host <span class="field-optional"></span></label>
                            <select name="linked_database_host" id="pDatabaseId" class="form-control">
                                <option value="">None</option>
                                @foreach($database_hosts as $database_host)
                                    <option value="{{ $database_host->id }}"
                                            @if ( old('linked_database_host') === $database_host->id ) selected @endif
                                    >{{ $database_host->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">One Click Admin Login (Beta, use at your own risk) <span class="field-required"></span></label>
                            <select name="one_click_admin_login_enabled" id="pNodeId" class="form-control">
                                <option value="0">Disabled</option>
                                <option value="1">Enabled</option>
                            </select>
                            <p class="small text-muted no-margin">Allows an administrator to connect to the associate phpMyAdmin of the node and seeing all the server of it.</p>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <input type="submit" value="Create Installation" class="btn btn-success btn-sm">
                    </div>
                </div>
            </div>
        </form>
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Informations</h3>
                </div>
                <div class="box-body">
                    <div class="form-group col-md-12">
                        <h4 class="text-red">IMPORTANT !</h4>
                        <p>If you need help for anything, please join our <a href="https://discord.gg/RJ2A8yYS2m" target="_blank">Discord</a>.</p>
                        <p>Don't forget to read the <a href="https://git.ric-rac.org/ric-rac/addons-documentation/wiki/Installing-Automatic-phpMyAdmin-for-Pterodactyl#configuration" target="_blank">documentation</a> !</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
