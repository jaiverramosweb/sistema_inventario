<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Puchase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'user_id',
        'sucuarsal_id',
        'date_emition',
        'state',
        'type_comprobant',
        'n_comprobant',
        'provider_id',
        'total',
        'immporte',
        'iva',
        'date_delivery',
        'description'
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
        return $this->belongsTo(User::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursale::class, 'sucuarsal_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function puchaseDetails()
    {
        return $this->hasMany(PuchaseDetail::class);
    }
}
