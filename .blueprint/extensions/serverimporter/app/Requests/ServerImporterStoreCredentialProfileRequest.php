<?php

namespace Pterodactyl\BlueprintFramework\Extensions\serverimporter\Requests;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class ServerImporterStoreCredentialProfileRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return 'file.delete';
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:1|max:255',
            'username' => 'sometimes|string|min:1|max:130|nullable',
            'password' => 'required|string|min:1|max:512|nullable',
        ];
    }
}
