@extends('layouts.admin')

@section('title')
    Automatic phpMyAdmin
@endsection

@section('content-header')
    <h1>Automatic phpMyAdmin<small>This part allows you to configure this addon.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Automatic phpMyAdmin</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">List of phpMyAdmin installations</h3>
                    <div class="box-tools search01">
                        <form action="" method="GET">
                            <div class="input-group input-group-sm">
                                <input type="text" name="filter[name]" class="form-control pull-right" value="{{ request()->input('filter.name') }}" placeholder="Search a phpMyAdmin installation">
                                <div class="input-group-btn">
                                    <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                                    <a href="{{ route('admin.automatic-phpmyadmin.new') }}"><button type="button" class="btn btn-sm btn-primary" style="border-radius: 0 3px 3px 0;margin-left:-1px;">Create a new installation</button></a>
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
                            <th>URL</th>
                            <th>Assigned Database Host</th>
                            <th>phpMyAdmin Server ID</th>
                            <th>One Click Admin Login</th>
                            <th></th>
                        </tr>
                        @foreach ($automatic_pmas as $automatic_pma)
                            <tr>
                                <td><code>{{  $automatic_pma->id }}</code></td>
                                <td><a href="{{ route('admin.automatic-phpmyadmin.view', $automatic_pma->id) }}">{{ $automatic_pma->name }}</a></td>
                                <td>{{ $automatic_pma->url }}</td>
                                @if ($automatic_pma->linked_database_host)
                                    <td><a href="{{ route('admin.databases.view', $automatic_pma->linked_database_host) }}">{{ $automatic_pma->database_host()?->name }}</a></td>
                                @else
                                    <td><span class="label label-success">None</span></td>
                                @endif
                                @if ($automatic_pma->phpmyadmin_server_id)
                                    <td>{{ $automatic_pma->phpmyadmin_server_id }}</td>
                                @else
                                    <td><span class="label label-success">None</span></td>
                                @endif
                                @if ($automatic_pma->one_click_admin_login_enabled)
                                    <td><span class="label label-success">Enabled</span></td>
                                @else
                                    <td><span class="label label-danger">Disabled</span></td>
                                @endif
                                <td class="text-center">
                                    <button data-action="delete" data-id="{{ $automatic_pma->id }}" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if($automatic_pmas->hasPages())
                    <div class="box-footer with-border">
                        <div class="col-md-12 text-center">{!! $automatic_pmas->appends(['query' => Request::input('query')])->render() !!}</div>
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
                title: 'Are you sure you wanna delete this installation ?',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d9534f',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Delete',
                showLoaderOnConfirm: true,
            }, function () {
                $.ajax({
                    method: 'DELETE',
                    url: '/admin/automatic-phpmyadmin/delete/' + self.data('id'),
                    data: {
                        _token: '{{ csrf_token() }}'
                    }, complete: function () {
                        window.location.href = '/admin/automatic-phpmyadmin';
                    }
                });
            });
        });
    </script>
@endsection
