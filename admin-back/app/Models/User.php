<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\Auditable;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles;
    use SoftDeletes;
    use Auditable {
        getAuditExcludedAttributes as private baseAuditExcludedAttributes;
    }

    protected $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'avatar',
        'sucuarsal_id',
        'phone',
        'type_document',
        'document',
        'gender',
        'two_factor_enabled',
        'two_factor_secret_encrypted',
        'two_factor_pending_secret_encrypted',
        'two_factor_confirmed_at',
        'two_factor_last_used_step',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret_encrypted',
        'two_factor_pending_secret_encrypted',
        'two_factor_last_used_step',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function sucursale()
    {
        return $this->belongsTo(Sucursale::class, 'sucuarsal_id');
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }

    public function crmActivities()
    {
        return $this->hasMany(CrmActivity::class);
    }

    public function recoveryCodes()
    {
        return $this->hasMany(UserRecoveryCode::class);
    }

    public function getAuditExcludedAttributes(): array
    {
        return array_merge($this->baseAuditExcludedAttributes(), [
            'password',
            'remember_token',
            'two_factor_secret_encrypted',
            'two_factor_pending_secret_encrypted',
            'two_factor_last_used_step',
        ]);
    }
}
