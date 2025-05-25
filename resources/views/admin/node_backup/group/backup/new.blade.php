@extends('layouts.admin')

@section('title')
    Creating a backup
@endsection

@section('content-header')
    <h1>Node-Backup<small>Create a new backup.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.node-backup') }}">Node Backup</a></li>
        <li><a href="{{ route('admin.node-backup.group.view', $backup_group->id) }}">{{ $backup_group->name }}</a></li>
        <li class="active">Create a new backup</li>
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
                                <input type="text" id="name" autocomplete="off" name="name" class="form-control" value="{{ old('name') }}" />
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <input type="submit" value="Create backup" class="btn btn-success btn-sm">
                        <a href="{{ route('admin.node-backup.group.view', $backup_group->id) }}" class="btn btn-default btn-sm">Go Back</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection