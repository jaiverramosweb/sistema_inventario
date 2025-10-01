<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Conversion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'product_id',
        'warehause_id',
        'unit_start_id',
        'unit_end_id',
        'quantity_start',
        'quantity_end',
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
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehause()
    {
        return $this->belongsTo(Warehouse::class, 'warehause_id');
    }

    public function unitStart()
    {
        return $this->belongsTo(Unit::class, 'unit_start_id');
    }

    public function unitEnd()
    {
        return $this->belongsTo(Unit::class, 'unit_end_id');
    }

    public function scopeFilterAdvance($query, $search, $conversion_id, $warehouse_id, $unit_start_id, $unit_end_id, $start_date, $end_date)
    {
        if($search){
            $query->whereHas('product', function($q) use ($search) {
                $q->where(DB::raw("products.title || ' ' || products.sku"), 'ilike', '%' . $search . '%');
            });                
        }

        if($conversion_id){
            $query->where("id", $conversion_id);
        }

        if($warehouse_id){
            $query->where("warehause_id", $warehouse_id);
        }

        if($unit_start_id){
            $query->where("unit_start_id", $unit_start_id);
        }

        if($unit_end_id){
            $query->where("unit_end_id", $unit_end_id);
        }

        if($start_date && $end_date){
            $query->whereBetween('created_at', [Carbon::parse($start_date)->format("Y-m-d 00:00:00"), Carbon::parse($end_date)->format("Y-m-d 23:59:59")]);   
        }

        return $query;
    }
}
