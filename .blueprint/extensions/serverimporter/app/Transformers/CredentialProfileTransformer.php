<?php

namespace Pterodactyl\BlueprintFramework\Extensions\serverimporter\Transformers;

use Illuminate\Support\Facades\Crypt;
use Pterodactyl\Transformers\Api\Client\BaseClientTransformer;
use Pterodactyl\BlueprintFramework\Extensions\serverimporter\Models\ImporterCredentialProfile;

class CredentialProfileTransformer extends BaseClientTransformer
{
    public function getResourceName(): string
    {
        return 'importer_credential_profile';
    }

    public function transform(ImporterCredentialProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'name' => $profile->name,
            'username' => $profile->username,
            'password' => Crypt::decrypt($profile->password, false),
        ];
    }
}
