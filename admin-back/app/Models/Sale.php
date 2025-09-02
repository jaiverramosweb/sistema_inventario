<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'client_id',
        'type_client',
        'sucursal_id',
        'subtotal',
        'total',
        'iva',
        'state',
        'state_mayment',
        'debt',
        'paid_out',
        'date_validation',
        'date_completed',
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

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function sucursale()
    {
        return $this->belongsTo(Sucursale::class, 'sucursal_id');
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }
}
