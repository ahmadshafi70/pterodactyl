@extends('layouts.admin')

@section('title')
    Viewing {{ $node_backup->name }}
@endsection

@section('content-header')
    <h1>Node Backup<small>This part allows you to see a backup made for a group, and download and/or recover it individually to a server.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.node-backup') }}">Node Backup</a></li>
        <li><a href="{{ route('admin.node-backup.group.view', $backup_group->id) }}">{{ $backup_group->name }}</a></li>
        <li class="active">{{ $node_backup->name }}</li>
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
                    <p>Number of server backups: <b>{{ $node_backup->numberOfBackups() }}</b></p>
                    <p>Backup Total Size: <b>{{ round($node_backup->size() / 1024, 2) }} GiB</b></p>
                    <p class="text-red">When clicking a button, don't spam it ! Once you clicked it just wait for the page to refresh !</p>
                    <p class="text-red">In function of how many servers and how many nodes there is, this can take a lot of time.</p>
                    @if (!$node_backup->getCompletedAt())
                        <div id="progressbar" style="align-content: center">
                            <div class="progress" style="width: 100%; height: 20px;">
                                <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width: {{ ($node_backup->numberOfFinishedBackups() / $node_backup->numberOfBackups()) * 100 }}%; height: 20px;">
                                    <p class="text-center" style="z-index: 1; color: black;">{{ $node_backup->numberOfFinishedBackups() }}/{{ $node_backup->numberOfBackups() }} Backups done</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">List of Server Backups for {{ $node_backup->name }}</h3>
                    <div class="box-tools search01">
                        <form action="" method="GET">
                            <div class="input-group input-group-sm">
                                <input type="text" name="filter[server]" class="form-control pull-right" value="{{ request()->input('filter.server') }}" placeholder="Search a server backup">
                                <div class="input-group-btn">
                                    <button type="submit" class="btn btn-default" style="margin-right: 4px;"><i class="fa fa-search"></i></button>
                                    @if (!$node_backup->getCompletedAt())
                                        <a href="{{ route('admin.node-backup.group.backup.stop', [$backup_group->id, $node_backup->id]) }}" class="btn btn-danger btn-sm" style="margin-right: 4px;">Stop Backup</a>
                                    @endif
                                    @if ($node_backup->getCompletedAt() && !$node_backup->hasNoFailures())
                                        <a href="{{ route('admin.node-backup.group.backup.try-again', [$backup_group->id, $node_backup->id]) }}" class="btn btn-danger btn-sm" style="margin-right: 4px;">Run Failed Backups</a>
                                    @endif
                                    <a href="{{ route('admin.node-backup.group.view', $backup_group->id) }}" class="btn btn-default btn-sm">Go Back</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="box-body table-responsive no-padding" id="tableUpdate">
                    <table class="table table-hover">
                        <tbody>
                        <tr>
                            <th class="text-center">UUID</th>
                            <th class="text-center">Server</th>
                            <th class="text-center">Node</th>
                            <th class="text-center">Disk</th>
                            <th class="text-center">Size</th>
                            <th class="text-center">Checksum</th>
                            <th class="text-center">Started At</th>
                            <th class="text-center">Completed At</th>
                            <th></th>
                        </tr>
                        @foreach ($node_backup_servers as $node_backup_server)
                            <tr>
                                @if ($node_backup_server->isSuccessful())
                                    <td class="text-center"><code>{{ $node_backup_server->uuidShort() }}</code></td>
                                    <td class="text-center">
                                        @if ($server = $node_backup_server->server())
                                            <a href="{{ route('admin.servers.view', $node_backup_server->server()->id) }}">
                                                {{ $server->name }}
                                            </a>
                                        @else
                                            {{ $node_backup_server->server_name }} (deleted {{ $node_backup_server->server_uuid }})
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($node = $node_backup_server->node())
                                            <a href="{{ route('admin.nodes.view', $node_backup_server->node()->id) }}">
                                                {{ $node->name }}
                                            </a>
                                        @else
                                            Node Deleted ({{ $node_backup_server->node_id }})
                                        @endif
                                    </td>
                                    <td class="text-center"><code>{{ $node_backup_server->disk }}</code></td>
                                    <td class="text-center">{{ $node_backup_server->size() }} MiB</td>
                                    <td class="text-center"><code>{{ $node_backup_server->checksum }}</code></td>
                                    <td class="text-center">{{ $node_backup_server->started_at ?? 'Not started'}}</td>
                                    <td class="text-center">{{ $node_backup_server->completed_at }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.node-backup.group.backup.server.download', [$backup_group->id, $node_backup->id, $node_backup_server->id]) }}" class="btn btn-xs btn-primary"><i class="fa fa-download"></i></a>
                                        <button class="btn btn-xs btn-success" data-id="{{ $node_backup_server->id }}" data-toggle="modal" data-target="#restoreNodeServerBackupModal"><i class="fa fa-refresh"></i></button>
                                    </td>
                                @elseif ($node_backup_server->inProgress())
                                    <td class="text-center"><code>{{ $node_backup_server->uuidShort() }}</code></td>
                                    <td class="text-center">
                                        @if ($server = $node_backup_server->server())
                                            <a href="{{ route('admin.servers.view', $node_backup_server->server()->id) }}">
                                                {{ $server->name }}
                                            </a>
                                        @else
                                            {{ $node_backup_server->server_name }} (deleted {{ $node_backup_server->server_uuid }})
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($node = $node_backup_server->node())
                                            <a href="{{ route('admin.nodes.view', $node_backup_server->node()->id) }}">
                                                {{ $node->name }}
                                            </a>
                                        @else
                                            Node Deleted ({{ $node_backup_server->node_id }})
                                        @endif
                                    </td>
                                    <td class="text-center"><i class="fa fa-refresh fa-spin"></i></td>
                                    <td class="text-center"><i class="fa fa-refresh fa-spin"></i></td>
                                    <td class="text-center"><i class="fa fa-refresh fa-spin"></i></td>
                                    <td class="text-center">{{ $node_backup_server->created_at }}</td>
                                    <td class="text-center"><i class="fa fa-refresh fa-spin"></i></td>
                                    <td class="text-center"><i class="fa fa-refresh fa-spin"></i></td>
                                @else
                                    <td class="text-center"><code>{{ $node_backup_server->uuidShort() }}</code></td>
                                    <td class="text-center">
                                        @if ($server = $node_backup_server->server())
                                            <a href="{{ route('admin.servers.view', $node_backup_server->server()->id) }}">
                                                {{ $server->name }}
                                            </a>
                                        @else
                                            {{ $node_backup_server->server_name }} (deleted {{ $node_backup_server->server_uuid }})
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($node = $node_backup_server->node())
                                            <a href="{{ route('admin.nodes.view', $node_backup_server->node()->id) }}">
                                                {{ $node->name }}
                                            </a>
                                        @else
                                            Node Deleted ({{ $node_backup_server->node_id }})
                                        @endif
                                    </td>
                                    <td class="text-center"><code>{{ $node_backup_server->disk }}</code></td>
                                    <td class="text-center"><span class="label label-danger">Failed</span></td>
                                    <td class="text-center"><span class="label label-danger">Failed</span></td>
                                    <td class="text-center"><span class="label label-danger">Failed</span></td>
                                    <td class="text-center"><span class="label label-danger">Failed</span></td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.node-backup.group.backup.server.try-again', [$backup_group->id, $node_backup->id, $node_backup_server->id]) }}" class="btn btn-xs btn-danger"><i class="fa fa-refresh"></i></a>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="text-center">Total</th>
                                <th class="text-center" id="totalServers">{{ $node_backup_servers->count() }}</th>
                                <th class="text-center" colspan="2"></th>
                                <th class="text-center" id="totalSize"></th>
                                <th class="text-center" colspan="4"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @if($node_backup_servers->hasPages())
                    <div class="box-footer with-border">
                        <div class="col-md-12 text-center">{!! $node_backup_servers->appends(['query' => Request::input('query')])->render() !!}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="modal fade" id="restoreNodeServerBackupModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Restore Node Backup Server</h4>
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
        $(document).ready(function () {
            let totalSize = 0;
            $('tbody tr').each(function () {
                totalSize += parseInt($(this).find('td:nth-child(5)').html()) || 0;
            });
            $('#totalSize').html(totalSize > 1024 ? (totalSize / 1024).toFixed(2) + ' GiB' : totalSize + ' MiB');

            $('#nodes_id').select2({
                tags: true,
                selectOnClose: false,
                placeholder: 'Select node(s)',
                tokenSeparators: [','],
            });

            let nodeBackupServerId = null;
            $('#restoreNodeServerBackupModal').on('show.bs.modal', function (event) {
                nodeBackupServerId = $(event.relatedTarget).data('id');
            });

            $('#pRestorationType').change(function () {
                if ($(this).val() === '{{ Pterodactyl\Models\NodeBackupServer::RESTORATION_TYPE_RECREATE }}') {
                    $('#nodesChoice').removeClass('hidden');
                } else {
                    $('#nodesChoice').addClass('hidden');
                }
            });

            $('#restoreNodeServerBackupModal button[type="submit"]').click(function (event) {
                event.preventDefault();
                let type = $('#pRestorationType').val();
                let nodeId = $('#nodes_id').val();

                switch (type) {
                    case '{{ Pterodactyl\Models\NodeBackupServer::RESTORATION_TYPE_CLASSIC }}':
                        window.location.href = '/admin/node-backup/group/{{ $backup_group->id }}/backup/{{ $node_backup->id }}/server-backup/' + nodeBackupServerId + '/restore'
                        break;
                    case '{{ Pterodactyl\Models\NodeBackupServer::RESTORATION_TYPE_RECREATE }}':
                        window.location.href = '/admin/node-backup/group/{{ $backup_group->id }}/backup/{{ $node_backup->id }}/server-backup/' + nodeBackupServerId + '/restore-on-another-node/' + nodeId
                        break;
                    default:
                        return;
                }
            });
        });

        @if (!$node_backup->getCompletedAt())
            setInterval(function() {
                $("#progressbar").load(window.location.href + " #progressbar" );
                $("#tableUpdate").load(window.location.href + " #tableUpdate" );
            }, 5000);

            setInterval(function() {
                let totalSize = 0;
                $('tbody tr').each(function () {
                    totalSize += parseInt($(this).find('td:nth-child(5)').html()) || 0;
                });
                $('#totalSize').html(totalSize > 1024 ? (totalSize / 1024).toFixed(2) + ' GiB' : totalSize + ' MiB');
            }, 100);
        @endif
    </script>
@endsection
