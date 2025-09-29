<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Transport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'warehause_start_id',
        'warehause_end_id',
        'date_emision',
        'state',
        'impote',
        'total',
        'iva',
        'date_delivery',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function warehouseStart()
    {
        return $this->belongsTo(Warehouse::class, 'warehause_start_id');
    }

    public function warehouseEnd()
    {
        return $this->belongsTo(Warehouse::class, 'warehause_end_id');
    }

    public function transportDetails()
    {
        return $this->hasMany(TransportDetail::class);
    }

    public function getDateEmitionFormatAttribute()
    {
        return Carbon::parse($this->date_emision)->format("Y/m/d");
    }

    public function getDateDeliveryFormatAttribute()
    {
        return $this->date_delivery ? Carbon::parse($this->date_delivery)->format("Y/m/d") : null;
    }

     public function getDateExitFormatAttribute()
    {
        return $this->date_exit ? Carbon::parse($this->date_exit)->format("Y/m/d") : null;
    }

    public function scopeFilterAdvance($query, $search, $warehause_start_id, $warehause_end_id, $unit_id, $start_date, $end_date, $search_product)
    {
        if($search){
            $query->where("id", $search);
        }

        if($warehause_start_id){
            $query->where("warehause_start_id", $warehause_start_id);
        }        

        if($warehause_end_id){
            $query->where("warehause_end_id", $warehause_end_id);
        }

        if($start_date && $end_date){
            $query->whereBetween("date_emision", [Carbon::parse($start_date)->format("Y-m-d")." 00:00:00", Carbon::parse($end_date)->format("Y-m-d")." 23:59:59"]);
        }

        if($unit_id){
            $query->whereHas("transportDetails", function($q) use($unit_id){
                $q->where("unit_id", $unit_id);
            });
        }

        if($search_product){
            $query->whereHas("transportDetails", function($q) use($search_product){
                $q->whereHas("product", function($subq) use($search_product){
                    $subq->where(DB::raw("products.title || ' ' || products.sku"), 'ilike', '%' . $search_product . '%');
                });
            });     
        }

        return $query;
    }
}
