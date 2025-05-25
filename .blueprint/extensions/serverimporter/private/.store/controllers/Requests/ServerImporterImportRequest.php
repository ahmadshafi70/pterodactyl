<?php

namespace Pterodactyl\BlueprintFramework\Extensions\serverimporter\Requests;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class ServerImporterImportRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return 'file.delete';
    }

    public function rules(): array
    {
        return [
            'mode' => 'required|string|in:ftp,sftp',
            'username' => 'required|string',
            'password' => 'required|string',
            'host' => 'required|string|max:130|min:3',
            'port' => 'required|integer|min:1|max:65535',
            'from' => 'nullable|string',
            'to' => 'nullable|string',
            'delete_files' => 'sometimes|boolean',
        ];
    }
}
