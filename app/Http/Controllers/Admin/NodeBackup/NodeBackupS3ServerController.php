<?php

namespace Pterodactyl\Http\Controllers\Admin\NodeBackup;

use Prologue\Alerts\AlertsMessageBag;
use Spatie\QueryBuilder\QueryBuilder;
use Pterodactyl\Models\NodeBackupGroup;
use Pterodactyl\Models\NodeBackupS3Server;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Http\Requests\Admin\NodeBackup\StoreNodeBackupS3ServerRequest;
use Pterodactyl\Http\Requests\Admin\NodeBackup\UpdateNodeBackupS3ServerRequest;

class NodeBackupS3ServerController extends Controller
{
    protected AlertsMessageBag $alert;

    protected Encrypter $encrypter;

    public function __construct(
        AlertsMessageBag $alert,
        Encrypter $encrypter
    ) {
        $this->alert = $alert;
        $this->encrypter = $encrypter;
    }

    public function index(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        return view('admin.node_backup.s3_server.index', [
            's3_servers' => QueryBuilder::for(NodeBackupS3Server::query())->allowedFilters(['name'])->paginate(10),
        ]);
    }

    public function create(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        return view('admin.node_backup.s3_server.new');
    }

    public function store(StoreNodeBackupS3ServerRequest $request): \Illuminate\Http\RedirectResponse
    {
        try {
            $data = $request->validated();

            $data['access_key_id'] = $this->encrypter->encrypt($data['access_key_id']);
            $data['secret_access_key'] = $this->encrypter->encrypt($data['secret_access_key']);

            $nodeBackupS3Server = NodeBackupS3Server::query()
                ->create($data);

            $this->alert->success('Successfully created new S3-Server.')->flash();

            return redirect()->route('admin.node-backup.s3-server.view', $nodeBackupS3Server->id);
        } catch (\Exception $e) {
            $this->alert->danger($e->getMessage())->flash();

            return redirect()->route('admin.node-backup.s3-server');
        }
    }

    public function view(int $nodeBackupS3ServerId)
    {
        $nodeBackupS3Server = NodeBackupS3Server::query()
            ->findOrFail($nodeBackupS3ServerId);

        $nodeBackupS3Server->access_key_id = $this->encrypter->decrypt($nodeBackupS3Server->access_key_id);
        $nodeBackupS3Server->secret_access_key = $this->encrypter->decrypt($nodeBackupS3Server->secret_access_key);

        return view('admin.node_backup.s3_server.view', [
            's3_server' => $nodeBackupS3Server,
        ]);
    }

    public function update(UpdateNodeBackupS3ServerRequest $request, int $nodeBackupS3ServerId): \Illuminate\Http\RedirectResponse
    {
        try {
            $data = $request->validated();

            $data['access_key_id'] = $this->encrypter->encrypt($data['access_key_id']);
            $data['secret_access_key'] = $this->encrypter->encrypt($data['secret_access_key']);

            NodeBackupS3Server::query()
                ->findOrFail($nodeBackupS3ServerId)
                ->update($data);

            $this->alert->success('Successfully updated the S3-Server.')->flash();

            return redirect()->route('admin.node-backup.s3-server.view', $nodeBackupS3ServerId);
        } catch (\Exception $e) {
            $this->alert->danger($e->getMessage())->flash();

            return redirect()->route('admin.node-backup.s3-server');
        }
    }

    public function destroy(int $nodeBackupS3ServerId): \Illuminate\Http\RedirectResponse
    {
        try {
            if (NodeBackupGroup::query()->where('s3_server_id', $nodeBackupS3ServerId)->exists()) {
                $this->alert->danger('Cannot delete S3-Server because it is still in use.')->flash();

                return redirect()->route('admin.node-backup.s3-server', $nodeBackupS3ServerId);
            }

            NodeBackupS3Server::query()
                ->findOrFail($nodeBackupS3ServerId)
                ->delete();

            $this->alert->success('Successfully deleted the S3-Server.')->flash();

            return redirect()->route('admin.node-backup.s3-server');
        } catch (\Exception $e) {
            $this->alert->danger($e->getMessage())->flash();

            return redirect()->route('admin.node-backup.s3-server');
        }
    }
}
