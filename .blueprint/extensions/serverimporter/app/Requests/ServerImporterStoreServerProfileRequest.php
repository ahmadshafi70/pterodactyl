<?php

namespace Pterodactyl\BlueprintFramework\Extensions\serverimporter\Requests;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class ServerImporterStoreServerProfileRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return 'file.delete';
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:1|max:255',
            'host' => 'required|string|max:130|min:3',
            'port' => 'required|integer|min:1|max:65535',
            'mode' => 'required|string|in:ftp,sftp',
        ];
    }
}
