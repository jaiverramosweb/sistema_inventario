<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

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
        'description',
        'discount',
        'state_delivery'
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
    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function getFirstPaymentAttribute()
    {
        return $this->payments->first();
    }

    public function scopeFilterAdvance($query, $search, $type_client, $search_client, $start_date, $end_date, $type, $state_delivery, $state_payment, $search_product)
    {
        if($search){
            $query->where("id", $search);
        }

        if($type_client){
            $query->where("type_client", $type_client);
        }

        if($search_client){
            $query->whereHas("client", function($q) use($search_client){
                $q->where(DB::raw("clients.name || ' ' || clients.n_document || ' ' || clients.phone"), 'ilike', '%' . $search_client . '%');
            });
        }

        if($start_date && $end_date){
            $query->whereBetween("created_at", [Carbon::parse($start_date)->format("Y-m-d")." 00:00:00", Carbon::parse($end_date)->format("Y-m-d")." 23:59:59"]);
        }

        if($type){
            $query->where("state", $type);
        }

        if($state_delivery){
            $query->where("state_delivery", $state_delivery);
        }

        if($state_payment){
            $query->where("state_mayment", $state_payment);
        }

        if($search_product){
            $query->whereHas("saleDetails", function($q) use($search_product){
                $q->whereHas("product", function($subq) use($search_product){
                    $subq->where(DB::raw("products.title || ' ' || products.sku"), 'ilike', '%' . $search_product . '%');
                });
            });
        }

        return $query;
    }
}
