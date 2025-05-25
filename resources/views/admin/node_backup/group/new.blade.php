@extends('layouts.admin')

@section('title')
    Creating a new node group
@endsection

@section('content-header')
    <h1>Node Backup<small>Create a new node group.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.node-backup') }}">Node Backup</a></li>
        <li class="active">New Node Group</li>
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
                        <div class="form-group">
                            <label for="pDescription" class="control-label">Description <span class="field-optional"></span></label>
                            <textarea name="description" id="pDescription" rows="4" class="form-control">{{ old('description') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="pMaxServerSize" class="control-label">Max Server Size <span class="field-required"></span></label>
                            <div>
                                <input type="text" id="pMaxServerSize" autocomplete="off" name="max_server_size" class="form-control" value="{{ old('max_server_size') }}" />
                            </div>
                            <p class="text-muted small">Define the maximum size of the server in MiB. If the server exceeds this size, it will be excluded from the backup. Set to -1 to disable.</p>
                        </div>
                        <div class="form-group">
                            <label for="pRetentionDays" class="control-label">Retention Days <span class="field-required"></span></label>
                            <div>
                                <input type="text" id="pRetentionDays" autocomplete="off" name="retention_days" class="form-control" value="{{ old('retention_days') }}" />
                            </div>
                            <p class="text-muted small">Define the number of days to keep the backup. Set to -1 to disable. Note that this function is only working when the group is enabled.</p>
                        </div>
                        <div class="form-group">
                            <label for="pMaxBeingMadeBackups" class="control-label">Max Being Made Backups <span class="field-required"></span></label>
                            <div>
                                <input type="text" id="pMaxBeingMadeBackups" autocomplete="off" name="max_being_made_backups" class="form-control" value="{{ old('max_being_made_backups') }}" />
                            </div>
                            <p class="text-muted small">Define the number of server backups being made simultaneously, must be between 1 and 10.</p>
                        </div>
                        <div class="form-group">
                            <label for="nodes_id" class="control-label">Nodes <span class="field-required"></span></label>
                            <select name="nodes_id[]" id="nodes_id" class="form-control" multiple>
                                @foreach($locations as $location)
                                <optgroup label="{{ $location->short }}">
                                    @foreach($location->nodes()->get() as $node)
                                    <option value="{{ $node->id }}" @if(in_array($node->id, old('nodes_id') ?? [])) selected @endif>{{ $node->name }}</option>
                                    @endforeach
                                </optgroup>
                                @endforeach
                            </select>
                            <p class="text-muted small">Select the nodes that will be used to backup the servers.</p>
                        </div>
                        <div class="form-group">
                            <label for="s3_server_id" class="control-label">S3-Server <span class="field-required"></span></label>
                            <select name="s3_server_id" id="s3_server_id" class="form-control">
                                <option value="" @if (old('s3_server_id')==null) selected @endif>Wings</option>
                                @foreach($s3_servers as $s3_server)
                                <option value="{{ $s3_server->id }}" @if(old('s3_server')===$s3_server->id) selected @endif>{{ $s3_server->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-muted small">Select the S3-server that will be used to store the backups. If set to <code>Wings</code> backups will be stored locally on the nodes of the group.</p>
                        </div>
                        <div class="form-group">
                            <label for="cron_job" class="control-label">Cron Job <span class="field-required"></span></label>
                            <div class="row">
                                <div class="col-md-12">
                                    <div style="display: flex;">
                                        <div class="flex-fill" style="margin-right: 2px; text-align: center;">
                                            <label for="cron_minute" class="control-label">Minute</label>
                                            <input type="text" style="text-align: center;" id="cron_minute" autocomplete="off" name="cron_minute" class="form-control" value="{{ old('cron_minute') }}" />
                                        </div>
                                        <div class="flex-fill" style="margin-right: 2px; text-align: center;">
                                            <label for="cron_hour" class="control-label">Hour</label>
                                            <input type="text" style="text-align: center;" id="cron_hour" autocomplete="off" name="cron_hour" class="form-control" value="{{ old('cron_day_of_month') }}" />
                                        </div>
                                        <div class="flex-fill" style="margin-right: 2px; text-align: center;">
                                            <label for="cron_day_of_month" class="control-label">Day of Month</label>
                                            <input type="text" style="text-align: center;" id="cron_day_of_month" autocomplete="off" name="cron_day_of_month" class="form-control" value="{{ old('cron_day_of_month') }}" />
                                        </div>
                                        <div class="flex-fill" style="margin-right: 2px; text-align: center;">
                                            <label for="cron_month" class="control-label">Month</label>
                                            <input type="text" style="text-align: center;" id="cron_month" autocomplete="off" name="cron_month" class="form-control" value="{{ old('cron_month') }}" />
                                        </div>
                                        <div class="flex-fill" style="text-align: center;">
                                            <label for="cron_day_of_week" class="control-label">Day of Week</label>
                                            <input type="text" style="text-align: center;" id="cron_day_of_week" autocomplete="off" name="cron_day_of_week" class="form-control" value="{{ old('cron_day_of_week') }}" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p class="text-muted small">Define if the group is enabled or not. If it is, the specified nodes will be backed up at the specified time by the cron job.</p>
                        </div>
                        <div class="form-group">
                            <label for="pIgnoredFiles" class="control-label">Ignored files <span class="field-optional"></span></label>
                            <textarea name="ignored_files" id="pIgnoredFiles" rows="4" class="form-control">{{ old('ignored_files') }}</textarea>
                            <p class="text-muted small">Enter the files or folders to ignore while generating this backup. Leave blank to use the contents of the <code>.pteroignore</code> file in the root of the server directory if present. Wildcard matching of files and folders is supported in addition to negating a rule by prefixing the path with an exclamation point.</p>
                        </div>
                        <div class="form-group">
                            <label for="is_active" class="control-label">Enabled <span class="field-required"></span></label>
                            <div>
                                <select name="is_active" id="is_active" class="form-control">
                                    <option value="0" @if(old('is_active')==='0' ) selected @endif>No</option>
                                    <option value="1" @if(old('is_active')==='1' ) selected @endif>Yes</option>
                                </select>
                            </div>
                            <p class="text-muted small">Define if the group is enabled or not. If it is, the specified nodes will be backed up at the specified time by the cron job and will be checked for retention.</p>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <input type="submit" value="Create node group" class="btn btn-success btn-sm">
                        <a href="{{ route('admin.node-backup') }}" class="btn btn-default btn-sm">Go Back</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('#nodes_id').select2({
            tags: true,
            selectOnClose: false,
            placeholder: 'Select node(s)',
            tokenSeparators: [','],
        });
    </script>
@endsection