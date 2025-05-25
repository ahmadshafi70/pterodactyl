@extends('layouts.admin')

@section('title')
    Node Backup S3 Servers
@endsection

@section('content-header')
    <h1>Node Backup<small>This part allows you to configure, handle, and see the S3 Servers used to back up things.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.node-backup') }}">Node Backup</a></li>
        <li class="active">S3 Servers</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Node Backup <b>S3 Servers</b></h3>
                </div>
                <div class="box-body">
                    <p>On this page, you can manage the S3 Servers that will be used to back up servers and nodes.</p>
                </div>
            </div>
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">List of S3-Servers</h3>
                    <div class="box-tools search01">
                        <form action="" method="GET">
                            <div class="input-group input-group-sm">
                                <input type="text" name="filter[name]" class="form-control pull-right" value="{{ request()->input('filter.name') }}" placeholder="Search a S3-Server">
                                <div class="input-group-btn">
                                    <button style="margin-right: 4px;" type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                                    <a style="margin-right: 4px;" href="{{ route('admin.node-backup.s3-server.new') }}"><button type="button" class="btn btn-sm btn-primary">Create a S3-Server</button></a>
                                    <a href="{{ route('admin.node-backup') }}" class="btn btn-default btn-sm">Go Back</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Default Region</th>
                            <th>Bucket</th>
                            <th>Endpoint</th>
                            <th></th>
                        </tr>
                        @foreach($s3_servers as $s3_server)
                            <tr>
                                <td><code>{{ $s3_server->id }}</code></td>
                                <td><a href="{{ route('admin.node-backup.s3-server.view', $s3_server->id) }}">{{ $s3_server->name }}</a></td>
                                <td><code>{{ $s3_server->default_region }}</code></td>
                                <td><code>{{ $s3_server->bucket }}</code></td>
                                <td><code>{{ $s3_server->endpoint }}</code></td>
                                <td class="text-right">
                                    <a href="{{ route('admin.node-backup.s3-server.view', $s3_server->id) }}"><button type="button" class="btn btn-xs btn-primary"><i class="fa fa-wrench"></i></button></a>
                                    <button data-action="delete" data-id="{{ $s3_server->id }}" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if($s3_servers->hasPages())
                    <div class="box-footer with-border">
                        <div class="col-md-12 text-center">{!! $s3_servers->appends(['query' => Request::input('query')])->render() !!}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('[data-action="delete"]').click(function (event) {
            event.preventDefault();
            const self = $(this);
            swal({
                title: 'Are you sure you wanna delete this S3-Server ?',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d9534f',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Delete',
                showLoaderOnConfirm: true,
            }, function () {
                $.ajax({
                    method: 'DELETE',
                    url: '/admin/node-backup/s3-server/' + self.data('id'),
                    data: {
                        _token: '{{ csrf_token() }}'
                    }, complete: function () {
                        window.location.href = '/admin/node-backup/s3-server';
                    }
                });
            });
        });
    </script>
@endsection
