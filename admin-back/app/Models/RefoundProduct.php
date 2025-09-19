<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefoundProduct extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'client_id',
        'product_id',
        'unit_id',
        'warehouse_id',
        'sale_detail_id',
        'quantity',
        'type',
        'state',
        'description',
        'resoslution_date',
        'resoslution_description'
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

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function saleDetail()
    {
        return $this->belongsTo(SaleDetail::class);
    }
}
