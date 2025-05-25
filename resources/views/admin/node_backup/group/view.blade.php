@extends('layouts.admin')

@section('title')
    Viewing {{ $backup_group->name }}
@endsection

@section('content-header')
    <h1>Node Backup<small>This part allows you to see or restore the backups of a specified group.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.node-backup') }}">Node Backup</a></li>
        <li class="active">{{ $backup_group->name }}</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Informations</h3>
                </div>
                <div class="box-body">
                    <div class="col-md-6">
                        <p>Size of the backups of that group: <b>{{ round($backup_group->size() / 1024, 2) }} GiB</b></p>
                        <p>Next automatic backup will be done at <b>{{ $backup_group->getNextRunDate() }}</b>.</p>
                        <p class="text-red">When clicking a button, don't click it more than once! Once you clicked it just wait for the request to be processed and the new page to load!</p>
                        <p class="text-red">Depending on how many servers and how many nodes there are, operations can take a lot of time.</p>
                    </div>
                    <div class="col-md-6 text-center">
                        <u><h4>Associated Nodes:</h4></u>
                        @foreach($backup_group->nodes() as $node)
                            <a href="{{ route('admin.nodes.view', $node->id) }}"><span class="label label-primary">{{ $node->name }}</span></a>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">List of Backups for {{ $backup_group->name }}</h3>
                    <div class="box-tools search01">
                        <form action="" method="GET">
                            <div class="input-group input-group-sm">
                                <input type="text" name="filter[name]" class="form-control pull-right" value="{{ request()->input('filter.name') }}" placeholder="Search a backup">
                                <div class="input-group-btn">
                                    <button type="submit" class="btn btn-default" style="margin-right: 4px;"><i class="fa fa-search"></i></button>
                                    <a style="margin-right: 4px;" href="{{ route('admin.node-backup.group.backup.new', $backup_group->id) }}"><button type="button" class="btn btn-sm btn-primary">New Backup</button></a>
                                    <a href="{{ route('admin.node-backup.group.edit', $backup_group->id) }}"><button type="button" class="btn btn-sm btn-primary">Edit Group</button></a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                        <tr>
                            <th class="text-center">UUID</th>
                            <th class="text-center">Name</th>
                            <th class="text-center">Total Servers Backed Up</th>
                            <th class="text-center">Size</th>
                            <th class="text-center">Started At</th>
                            <th class="text-center">Completed At</th>
                            <th class="text-center">Done</th>
                            <th class="text-center"></th>
                        </tr>
                        @foreach ($node_backups as $node_backup)
                            <tr>
                                <td class="text-center"><code>{{ $node_backup->uuidShort() }}</code></td>
                                <td class="text-center"><a href="{{ route('admin.node-backup.group.backup.view', [$backup_group->id, $node_backup->id]) }}">{{ $node_backup->name }}</a></td>
                                <td class="text-center">{{ $node_backup->numberOfFinishedBackups() }}</td>
                                <td class="text-center">{{ round($node_backup->size() / 1024, 2) }} GiB</td>
                                <td class="text-center">{{ $node_backup->created_at }}</td>
                                @if ($completedAt = $node_backup->getCompletedAt())
                                    <td class="text-center">{{ $completedAt }}</td>
                                @else
                                    <td class="text-center"><i class="fa fa-refresh fa-spin"></i></td>
                                @endif
                                <td class="text-center">
                                    @if($node_backup->getCompletedAt())
                                        <span class="label label-success">Yes</span>
                                    @else
                                        <span class="label label-danger">{{ $node_backup->numberOfFinishedBackups() }}/{{ $node_backup->numberOfBackups() }} Servers</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-xs btn-success" data-id="{{ $node_backup->id }}" data-toggle="modal" data-target="#restoreNodeServerModal"><i class="fa fa-refresh"></i></button>
                                    <button data-action="delete" data-id="{{ $node_backup->id }}" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if($node_backups->hasPages())
                    <div class="box-footer with-border">
                        <div class="col-md-12 text-center">{!! $node_backups->appends(['query' => Request::input('query')])->render() !!}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="modal fade" id="restoreNodeServerModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Restore Node Backup</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <label for="pRestorationType" class="form-label">Restoration Type</label>
                            <select name="type" id="pRestorationType" class="form-control">
                                <option value="{{ Pterodactyl\Models\NodeBackupServer::RESTORATION_TYPE_CLASSIC }}">Classic</option>
                                <option value="{{ Pterodactyl\Models\NodeBackupServer::RESTORATION_TYPE_RECREATE }}">Recreate</option>
                            </select>
                            <p class="text-muted small">The <code>Classic</code> restoration type will simply restore the data on the associated server while the <code>Recreate</code> restoration type will recreate the server on the node you choose and then restore the data.</p>
                        </div>
                        <div class="col-md-12 hidden" id="nodesChoice">
                            <label for="nodes_id" class="form-label">Node to restore on</label>
                            <select name="nodes_id[]" id="nodes_id" class="form-control">
                                @foreach($locations as $location)
                                    <optgroup label="{{ $location->short }}">
                                        @foreach($location->nodes()->get() as $node)
                                            <option value="{{ $node->id }}" @if(in_array($node->id, old('nodes_id') ?? [])) selected @endif>{{ $node->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <p class="text-muted small">The node on which the server will be restored.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm">Restore</button>
                </div>
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
                title: 'Are you sure you wanna delete this Backup ?',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d9534f',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Delete',
                showLoaderOnConfirm: true,
            }, function () {
                $.ajax({
                    method: 'DELETE',
                    url: '/admin/node-backup/group/{{ $backup_group->id }}/backup/' + self.data('id') + '/delete',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    complete: function () {
                        window.location.href = '/admin/node-backup/group/{{ $backup_group->id }}';
                    }
                });
            });
        });

        $(document).ready(function () {
            $('#nodes_id').select2({
                tags: true,
                selectOnClose: false,
                placeholder: 'Select node(s)',
                tokenSeparators: [','],
            });

            let nodeBackupId = null;
            $('#restoreNodeServerModal').on('show.bs.modal', function (event) {
                nodeBackupId = $(event.relatedTarget).data('id');
            });

            $('#pRestorationType').change(function () {
                if ($(this).val() === '{{ Pterodactyl\Models\NodeBackupServer::RESTORATION_TYPE_RECREATE }}') {
                    $('#nodesChoice').removeClass('hidden');
                } else {
                    $('#nodesChoice').addClass('hidden');
                }
            });

            $('#restoreNodeServerModal button[type="submit"]').click(function (event) {
                event.preventDefault();
                let type = $('#pRestorationType').val();
                let nodeId = $('#nodes_id').val();

                switch (type) {
                    case '{{ Pterodactyl\Models\NodeBackupServer::RESTORATION_TYPE_CLASSIC }}':
                        window.location.href = '/admin/node-backup/group/{{ $backup_group->id }}/backup/' + nodeBackupId + '/restore'
                        break;
                    case '{{ Pterodactyl\Models\NodeBackupServer::RESTORATION_TYPE_RECREATE }}':
                        window.location.href = '/admin/node-backup/group/{{ $backup_group->id }}/backup/' + nodeBackupId + '/restore-on-another-node/' + nodeId
                        break;
                    default:
                        return;
                }
            });
        });
    </script>
@endsection
