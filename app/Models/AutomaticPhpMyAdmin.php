<?php

namespace Pterodactyl\Models;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $url
 * @property string $cookie_name
 * @property string $cookie_domain
 * @property string $encryption_key
 * @property string $encryption_iv
 * @property bool $one_click_admin_login_enabled
 * @property int $linked_database_host
 * @property int $phpmyadmin_server_id
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 */
class AutomaticPhpMyAdmin extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'automaticphpmyadmin';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'automatic_phpmyadmin';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'name' => 'string',
        'description' => 'string',
        'url' => 'string',
        'cookie_name' => 'string',
        'cookie_domain' => 'string',
        'encryption_key' => 'string',
        'encryption_iv' => 'string',
        'one_click_admin_login_enabled' => 'boolean',
        'linked_database_host' => 'integer',
        'phpmyadmin_server_id' => 'integer',
    ];

    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'url',
        'cookie_name',
        'cookie_domain',
        'encryption_key',
        'encryption_iv',
        'one_click_admin_login_enabled',
        'linked_database_host',
        'phpmyadmin_server_id'
    ];

    /**
     * @var array
     */
    public static array $validationRules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'url' => 'required|string',
        'cookie_name' => 'required|string|max:10',
        'cookie_domain' => 'required|string',
        'encryption_key' => 'required|string',
        'encryption_iv' => 'required|string',
        'one_click_admin_login_enabled' => 'required|boolean',
        'linked_database_host' => 'nullable|integer',
        'phpmyadmin_server_id' => 'required|integer',
    ];

    /**
     * Default values for specific columns that are generally not changed on base installations.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object
     */
    public function database_host()
    {
        return DatabaseHost::query()->where('id', $this->linked_database_host)->first();
    }
}
