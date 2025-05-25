@extends('layouts.admin')

@section('title')
Node Backup
@endsection

@section('content-header')
<h1>Node Backup<small>This part allows you to configure, handle, and see the backups.</small></h1>
<ol class="breadcrumb">
    <li><a href="{{ route('admin.index') }}">Admin</a></li>
    <li class="active">Node Backup</li>
</ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">List of Node Groups</h3>
                <div class="box-tools search01">
                    <form action="" method="GET">
                        <div class="input-group input-group-sm">
                            <input type="text" name="filter[name]" class="form-control pull-right" value="{{ request()->input('filter.name') }}" placeholder="Search a node group">
                            <div class="input-group-btn">
                                <button style="margin-right: 4px;" type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                                <a style="margin-right: 4px;" href="{{ route('admin.node-backup.statistics') }}"><button type="button" class="btn btn-sm btn-default">Statistics</button></a>
                                <a style="margin-right: 4px;" href="{{ route('admin.node-backup.s3-server') }}"><button type="button" class="btn btn-sm btn-success">S3-Servers</button></a>
                                <a href="{{ route('admin.node-backup.group.new') }}"><button type="button" class="btn btn-sm btn-primary">Create a new node group</button></a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th class="text-center">ID</th>
                            <th class="text-center">Name</th>
                            <th class="text-center">Assigned Nodes</th>
                            <th class="text-center">cron Schedule</th>
                            <th class="text-center">Next Run At</th>
                            <th class="text-center">Last Run At</th>
                            <th class="text-center">Activated</th>
                            <th class="text-center">Processing</th>
                            <th class="text-center"></th>
                        </tr>
                        @foreach ($backup_groups as $backup_group)
                        <tr>
                            <td class="text-center"><code>{{ $backup_group->id }}</code></td>
                            <td class="text-center"><a href="{{ route('admin.node-backup.group.view', $backup_group->id) }}">{{ $backup_group->name }}</a></td>
                            <td class="text-center">{{ count($backup_group->nodes_id) }}</td>
                            <td class="text-center"><code>{{ $backup_group->getCronExpression() }}</code></td>
                            <td class="text-center">{{ $backup_group->getNextRunDate() }}</td>
                            <td class="text-center">{{ $backup_group->last_run_at ?? 'Never' }}</td>
                            <td class="text-center">
                                @if($backup_group->is_active)
                                <span class="label label-success">Yes</span>
                                @else
                                <span class="label label-danger">No</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($backup_group->isProcessing())
                                <span class="label label-success">Yes</span>
                                @else
                                <span class="label label-danger">No</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.node-backup.group.edit', $backup_group->id) }}"><button type="button" class="btn btn-xs btn-primary"><i class="fa fa-wrench"></i></button></a>
                                <button data-action="delete" data-id="{{ $backup_group->id }}" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($backup_groups->hasPages())
            <div class="box-footer with-border">
                <div class="col-md-12 text-center">{!! $backup_groups->appends(['query' => Request::input('query')])->render() !!}</div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
@parent
<script>
    console.log('If you see this message, it means you\'r looking somewhere you shouldn\'t be looking at.');

    $('[data-action="delete"]').click(function(event) {
        event.preventDefault();
        const self = $(this);
        swal({
            title: 'Are you sure you wanna delete this Node-Group ?',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d9534f',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Delete',
            showLoaderOnConfirm: true,
        }, function() {
            $.ajax({
                method: 'DELETE',
                url: '/admin/node-backup/group/' + self.data('id') + '/delete',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                complete: function() {
                    window.location.href = '/admin/node-backup';
                }
            });
        });
    });
</script>
@endsection