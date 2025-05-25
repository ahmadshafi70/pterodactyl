<?php

namespace Pterodactyl\BlueprintFramework\Extensions\serverimporter\Models;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ImporterCredentialProfile.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $username
 * @property string $password
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Pterodactyl\Models\User $user
 *
 * @method static \Database\Factories\ImporterCredentialProfile factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|ImporterCredentialProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImporterCredentialProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImporterCredentialProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder|ImporterCredentialProfile whereId($value)
 *
 * @mixin \Eloquent
 */
class ImporterCredentialProfile extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'importer_credential_profile';

    /**
     * The table associated with the model.
     */
    protected $table = 'importer_credential_profiles';

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
    ];

    /**
     * Fields that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'name',
        'username',
        'password',
    ];

    /**
     * Rules to protect against invalid data entry to DB.
     */
    public static array $validationRules = [
        'user_id' => 'required|integer|exists:users,id',
        'name' => 'required|string',
        'username' => 'nullable|string',
        'password' => 'required|string',
    ];

    /**
     * Return the user associated with this profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
