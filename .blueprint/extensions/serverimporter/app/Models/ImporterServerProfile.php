<?php

namespace Pterodactyl\BlueprintFramework\Extensions\serverimporter\Models;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ImporterServerProfile.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $host
 * @property int $port
 * @property string $mode
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Pterodactyl\Models\User $user
 *
 * @method static \Database\Factories\ImporterServerProfile factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|ImporterServerProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImporterServerProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImporterServerProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder|ImporterServerProfile whereId($value)
 *
 * @mixin \Eloquent
 */
class ImporterServerProfile extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'importer_server_profile';

    /**
     * The table associated with the model.
     */
    protected $table = 'importer_server_profiles';

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
        'host',
        'port',
        'mode',
    ];

    /**
     * Rules to protect against invalid data entry to DB.
     */
    public static array $validationRules = [
        'user_id' => 'required|integer|exists:users,id',
        'name' => 'required|string',
        'host' => 'required|string',
        'port' => 'required|integer',
        'mode' => 'required|string|in:sftp,ftp',
    ];

    /**
     * Return the user associated with this profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
