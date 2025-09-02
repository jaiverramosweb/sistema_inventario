<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_id',
        'product_id',
        'product_categoryid',
        'unit_id',
        'warehouse_id',
        'quantity',
        'price_unit',
        'discount',
        'iva',
        'subtotal',
        'total',
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

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productCategory()
    {
        return $this->belongsTo(Category::class, 'product_categoryid');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
