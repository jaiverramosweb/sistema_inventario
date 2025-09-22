<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PuchaseDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'puchase_id',
        'product_id',
        'unit_id',
        'quantity',
        'price_unit',
        'total',
        'state',
        'user_delivery',
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

    public function puchase()
    {
        return $this->belongsTo(Puchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function userDelivery()
    {
        return $this->belongsTo(User::class, 'user_delivery');
    }
}
