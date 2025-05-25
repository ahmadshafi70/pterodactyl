<?php

namespace Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Models;

use Pterodactyl\Models\Model;

/**
 * Subdomain.
 *
 * @property int $id
 * @property array $eggs
 * @property string $domain
 * @property string $zone_id
 * @property array $disallowed_subdomains_regexes
 * @property array $api_data
 *
 * @method static \Database\Factories\Subdomain factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Subdomain newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Subdomain newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Subdomain query()
 * @method static \Illuminate\Database\Eloquent\Builder|Subdomain whereId($value)
 *
 * @mixin \Eloquent
 */
class Subdomain extends Model
{
    public const CREATED_AT = null;
    public const UPDATED_AT = null;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'subdomain';

    /**
     * The table associated with the model.
     */
    protected $table = 'subdomains';

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'eggs' => 'array',
        'disallowed_subdomains_regexes' => 'array',
        'api_data' => 'array',
    ];

    /**
     * Fields that are mass assignable.
     */
    protected $fillable = [
        'eggs',
        'domain',
        'zone_id',
        'disallowed_subdomains_regexes',
        'api_data',
    ];

    /**
     * Rules to protect against invalid data entry to DB.
     */
    public static array $validationRules = [
        'eggs' => 'required|array',
        'domain' => 'required|string',
        'zone_id' => 'required|string',
        'disallowed_subdomains_regexes' => 'required|array',
        'api_data' => 'required|array',
    ];

    public function serverSubdomains()
    {
        return $this->hasMany(ServerSubdomain::class, 'subdomain_id');
    }
}
