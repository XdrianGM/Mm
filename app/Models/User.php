<?php

namespace Everest\Models;

use Everest\Rules\Username;
use Everest\Facades\Activity;
use Laravel\Cashier\Billable;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\In;
use Illuminate\Auth\Authenticatable;
use Illuminate\Notifications\Notifiable;

use function Illuminate\Events\queueable;

use Illuminate\Database\Eloquent\Builder;
use Everest\Models\Traits\HasAccessTokens;
use Everest\Traits\Helpers\AvailableLanguages;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Everest\Notifications\SendPasswordReset as ResetPasswordNotification;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

/**
 * Everest\Models\User.
 *
 * @property int $id
 * @property string|null $external_id
 * @property string $uuid
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string|null $remember_token
 * @property string $language
 * @property int|null $admin_role_id
 * @property bool $root_admin
 * @property string|null $state
 * @property bool $use_totp
 * @property string|null $totp_secret
 * @property \Illuminate\Support\Carbon|null $totp_authenticated_at
 * @property bool $gravatar
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $avatar_url
 * @property string $recovery_code
 * @property string|null $admin_role_name
 * @property string $md5
 * @property \Everest\Models\AdminRole|null $adminRole
 * @property \Illuminate\Database\Eloquent\Collection|\Everest\Models\ApiKey[] $apiKeys
 * @property int|null $api_keys_count
 * @property \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property int|null $notifications_count
 * @property \Illuminate\Database\Eloquent\Collection|\Everest\Models\RecoveryToken[] $recoveryTokens
 * @property int|null $recovery_tokens_count
 * @property \Illuminate\Database\Eloquent\Collection|\Everest\Models\Server[] $servers
 * @property int|null $servers_count
 * @property \Illuminate\Database\Eloquent\Collection|\Everest\Models\UserSSHKey[] $sshKeys
 * @property int|null $ssh_keys_count
 * @property \Illuminate\Database\Eloquent\Collection|\Everest\Models\ApiKey[] $tokens
 * @property int|null $tokens_count
 *
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 * @method static Builder|User whereCreatedAt($value)
 * @method static Builder|User whereEmail($value)
 * @method static Builder|User whereExternalId($value)
 * @method static Builder|User whereGravatar($value)
 * @method static Builder|User whereId($value)
 * @method static Builder|User whereLanguage($value)
 * @method static Builder|User whereNameFirst($value)
 * @method static Builder|User whereNameLast($value)
 * @method static Builder|User wherePassword($value)
 * @method static Builder|User whereRememberToken($value)
 * @method static Builder|User whereRootAdmin($value)
 * @method static Builder|User whereTotpAuthenticatedAt($value)
 * @method static Builder|User whereTotpSecret($value)
 * @method static Builder|User whereUpdatedAt($value)
 * @method static Builder|User whereUseTotp($value)
 * @method static Builder|User whereUsername($value)
 * @method static Builder|User whereUuid($value)
 *
 * @mixin \Barryvdh\LaravelIdeHelper\Eloquent
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Authenticatable;
    use Authorizable;
    use AvailableLanguages;
    use CanResetPassword;
    use HasAccessTokens;
    use Notifiable;
    use Billable;

    public const USER_LEVEL_USER = 0;
    public const USER_LEVEL_ADMIN = 1;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'user';

    /**
     * Level of servers to display when using access() on a user.
     */
    protected string $accessLevel = 'all';

    /**
     * The table associated with the model.
     */
    protected $table = 'users';

    /**
     * A list of mass-assignable variables.
     */
    protected $fillable = [
        'external_id',
        'username',
        'email',
        'password',
        'language',
        'use_totp',
        'totp_secret',
        'totp_authenticated_at',
        'gravatar',
        'state',
        'root_admin',
        'recovery_code',
    ];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'root_admin' => 'boolean',
        'use_totp' => 'boolean',
        'gravatar' => 'boolean',
        'totp_authenticated_at' => 'datetime',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     */
    protected $hidden = ['password', 'recovery_code', 'remember_token', 'totp_secret', 'totp_authenticated_at'];

    /**
     * Default values for specific fields in the database.
     */
    protected $attributes = [
        'external_id' => null,
        'root_admin' => false,
        'language' => 'en',
        'use_totp' => false,
        'totp_secret' => null,
        'state' => null,
    ];

    /**
     * Rules verifying that the data being stored matches the expectations of the database.
     */
    public static array $validationRules = [
        'uuid' => 'required|string|size:36|unique:users,uuid',
        'email' => 'required|email|between:1,191|unique:users,email',
        'external_id' => 'sometimes|nullable|string|max:191|unique:users,external_id',
        'username' => 'required|between:1,191|unique:users,username',
        'password' => 'sometimes|nullable|string',
        'root_admin' => 'boolean',
        'language' => 'string',
        'state' => 'sometimes|nullable|string',
        'use_totp' => 'boolean',
        'totp_secret' => 'nullable|string',
        'recovery_code' => 'nullable|string',
    ];

    /**
     * Implement language verification by overriding Eloquence's gather
     * rules function.
     */
    public static function getRules(): array
    {
        $rules = parent::getRules();

        $rules['language'][] = new In(array_keys((new self())->getAvailableLanguages()));
        $rules['username'][] = new Username();

        return $rules;
    }

    /**
     * Return the user model in a format that can be passed over to React templates.
     */
    public function toReactObject(): array
    {
        return Collection::make($this->append(['avatar_url', 'admin_role_name'])->toArray())
            ->except(['id', 'external_id', 'admin_role', 'admin_role_id'])
            ->toArray();
    }

    /**
     * Store the username as a lowercase string.
     */
    public function setUsernameAttribute(string $value)
    {
        $this->attributes['username'] = mb_strtolower($value);
    }

    public function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => 'https://www.gravatar.com/avatar/' . $this->md5 . '.jpg',
        );
    }

    public function adminRoleName(): Attribute
    {
        return Attribute::make(
            get: fn () => is_null($this->adminRole) ? ($this->root_admin ? 'None' : null) : $this->adminRole->name,
        );
    }

    public function md5(): Attribute
    {
        return Attribute::make(
            get: fn () => md5(strtolower($this->email)),
        );
    }

    public function isSuspended(): bool
    {
        return $this->state === 'suspended';
    }

    /**
     * Returns all the activity logs where this user is the subject — not to
     * be confused by activity logs where this user is the _actor_.
     */
    public function activity(): MorphToMany
    {
        return $this->morphToMany(ActivityLog::class, 'subject', 'activity_log_subjects');
    }

    public function adminRole(): HasOne
    {
        return $this->hasOne(AdminRole::class, 'id', 'admin_role_id');
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class)
            ->where('key_type', ApiKey::TYPE_ACCOUNT);
    }

    public function recoveryTokens(): HasMany
    {
        return $this->hasMany(RecoveryToken::class);
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class, 'owner_id');
    }

    public function sshKeys(): HasMany
    {
        return $this->hasMany(UserSSHKey::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Returns all the servers that a user can access by way of being the owner of the
     * server, or because they are assigned as a subuser for that server.
     */
    public function accessibleServers(): Builder
    {
        return Server::query()
            ->select('servers.*')
            ->leftJoin('subusers', 'subusers.server_id', '=', 'servers.id')
            ->where(function (Builder $builder) {
                $builder->where('servers.owner_id', $this->id)->orWhere('subusers.user_id', $this->id);
            })
            ->groupBy('servers.id');
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::updated(queueable(function (User $customer) {
            if ($customer->hasStripeId()) {
                $customer->syncStripeCustomerDetails();
            }
        }));
    }
}
