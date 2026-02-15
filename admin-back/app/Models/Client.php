<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'surname',
        'email',
        'phone',
        'type_client',
        'type_document',
        'n_document',
        'date_birthday',
        'user_id',
        'sucursal_id',
        'gender',
        'status',
        'id_department',
        'id_municipality',
        'id_district',
        'department',
        'municipality',
        'district',
        'address'
    ];

    public function setCreatedAtAttribute($value)
    {
        date_default_timezone_set('America/Bogota');
        $this->attributes["created_at"] = Carbon::now();
    }

    public function setUpdatedAtAttribute($value)
    {
        date_default_timezone_set("America/Bogota");
        $this->attributes["updated_at"] = Carbon::now();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sucursale()
    {
        return $this->belongsTo(Sucursale::class, 'sucursal_id');
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }
}
