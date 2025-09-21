<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

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

    public function scopeFilterAdvance($query, $search_product, $warehouse_id, $unit_id, $type, $state, $sale_id, $search_client, $start_date, $end_date)
    {
        if($search_product){
            $query->whereHas('product', function($q) use ($search_product) {
                $q->where(DB::raw("products.title || ' ' || products.sku"), 'ilike', '%' . $search_product . '%');
            });
        }

        if($warehouse_id){
            $query->where("warehouse_id", $warehouse_id);
        }

        if($unit_id){
            $query->where("unit_id", $unit_id);
        }

        if($type){
            $query->where("type", $type);
        }

        if($state){
            $query->where("state", $state);
        }

        if($sale_id){
            $query->whereHas('saleDetail', function($q) use ($sale_id) {
                $q->where("sale_id", $sale_id);
            });
        }

        if($search_client){
            $query->whereHas('client', function($q) use ($search_client) {
                $q->where(DB::raw("clients.name || ' ' || clients.n_document || ' ' || clients.phone || ' ' || COALESCE(clients.email,'')"), 'ilike', '%' . $search_client . '%');
            });
        }

        if($start_date && $end_date){
            $query->whereBetween('created_at', [Carbon::parse($start_date)->format("Y-m-d 00:00:00"), Carbon::parse($end_date)->format("Y-m-d 23:59:59")]);   
        }

        return $query;
    }
}
