@extends('layouts.admin')

@section('title')
    TCP sheild
@endsection

@section('content-header')
    <h1>{{ $server->name }}<small>{{ str_limit($server->description) }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.servers') }}">TCP Sheild</a></li>

    </ol>
@endsection
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="text-lg font-bold">TCPShield Integration</h3>
        <button class="btn btn-primary float-right" onclick="document.getElementById('integrationModal').showModal()">New Integration</button>
    </div>

    <div class="card-body">
        @if (count($domains) === 0)
            <p class="text-center text-gray-500">It looks like you have no domains added.</p>
        @else
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Enabled</th>
                        <th>Primary</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($domains as $domain)
                        <tr>
                            <td>{{ $domain->domain }}</td>
                            <td>
                                <form method="POST" action="{{ route('tcpshield.toggle', $domain->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $domain->enabled ? 'btn-success' : 'btn-danger' }}">
                                        {{ $domain->enabled ? 'On' : 'Off' }}
                                    </button>
                                </form>
                            </td>
                            <td>
                                @if ($domain->is_primary)
                                    <span class="badge badge-primary">Primary</span>
                                @else
                                    <form method="POST" action="{{ route('tcpshield.primary', $domain->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-secondary">Make Primary</button>
                                    </form>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('tcpshield.delete', $domain->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<dialog id="integrationModal" class="modal">
    <form method="POST" action="{{ route('tcpshield.store') }}" class="modal-box">
        @csrf
        <h3 class="font-bold text-lg">Create new Integration</h3>
        <input type="text" name="domain" class="input input-bordered w-full mt-3" placeholder="e.g., test.mygame.net" required>
        <div class="modal-action">
            <button type="button" class="btn" onclick="integrationModal.close()">Cancel</button>
            <button type="submit" class="btn btn-primary">Create Integration</button>
        </div>
    </form>
</dialog>
@endsection
@endsection