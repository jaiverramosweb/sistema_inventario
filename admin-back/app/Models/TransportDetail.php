<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'transport_id',
        'product_id',
        'unit_id',
        'quantity',
        'price_unit',
        'total',
        'state',
        'user_delivery_id',
        'date_delivery',
        'user_exit_id',
        'date_exit',
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

    public function transport()
    {
        return $this->belongsTo(Transport::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function userIn()
    {
        return $this->belongsTo(User::class, 'user_delivery_id');
    }

    public function getDateDeliveryFormatAttribute()
    {
        return Carbon::parse($this->date_delivery)->format("Y/m/d");
    }

    public function userOut()
    {
        return $this->belongsTo(User::class, 'user_exit_id');
    }

    public function getDateExitFormatAttribute()
    {
        return Carbon::parse($this->date_exit)->format("Y/m/d");
    }
}
