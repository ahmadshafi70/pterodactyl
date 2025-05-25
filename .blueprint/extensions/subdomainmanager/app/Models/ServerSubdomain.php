<?php

namespace Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Models;

use Pterodactyl\Models\Model;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Allocation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ServerSubdomain.
 *
 * @property int $id
 * @property int $server_id
 * @property int $subdomain_id
 * @property string $subdomain
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Pterodactyl\Models\Server $server
 * @property \Pterodactyl\BlueprintFramework\Extensions\subdomainmanager\Models\Subdomain $domain
 * @property \Pterodactyl\Models\Allocation|null $allocation
 *
 * @method static \Database\Factories\ServerSubdomain factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|ServerSubdomain newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ServerSubdomain newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ServerSubdomain query()
 * @method static \Illuminate\Database\Eloquent\Builder|ServerSubdomain whereId($value)
 *
 * @mixin \Eloquent
 */
class ServerSubdomain extends Model
{
    public const UPDATED_AT = null;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'server_subdomain';

    /**
     * The table associated with the model.
     */
    protected $table = 'server_subdomains';

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'server_id' => 'int',
        'subdomain_id' => 'int',
        self::CREATED_AT => 'datetime',
    ];

    /**
     * Fields that are mass assignable.
     */
    protected $fillable = [
        'server_id',
        'allocation_id',
        'subdomain_id',
        'subdomain',
    ];

    /**
     * Load the domain relationship by default.
     */
    protected $with = ['domain', 'allocation'];

    /**
     * Rules to protect against invalid data entry to DB.
     */
    public static array $validationRules = [
        'server_id' => 'required|exists:servers,id',
        'allocation_id' => 'nullable|exists:allocations,id',
        'subdomain_id' => 'required|exists:subdomains,id',
        'subdomain' => 'required|string',
    ];

    /**
     * Returns the server this subdomain is assigned to.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    /**
     * Returns the subdomain this server is assigned to.
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Subdomain::class, 'subdomain_id');
    }

    /**
     * Returns the allocation this subdomain is assigned to.
     */
    public function allocation(): BelongsTo
    {
        return $this->belongsTo(Allocation::class, 'allocation_id');
    }
}
