<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

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

    public function getDateEmitionFormatAttribute()
    {
        return Carbon::parse($this->date_emition)->format("Y/m/d");
    }


    public function scopeFilterAdvance($query, $search, $warehouse_id, $unit_id, $provider_id, $type_comprobant, $start_date, $end_date, $search_product, $user)
    {
        if($search){
            $query->where("id", $search);
        }

        if($warehouse_id){
            $query->where("warehouse_id", $warehouse_id);
        }        

        if($provider_id){
            $query->where("provider_id", $provider_id);
        }

        if($type_comprobant){
            $query->where("type_comprobant", $type_comprobant);
        }

        if($start_date && $end_date){
            $query->whereBetween("date_emition", [Carbon::parse($start_date)->format("Y-m-d")." 00:00:00", Carbon::parse($end_date)->format("Y-m-d")." 23:59:59"]);
        }

        if($unit_id){
            $query->whereHas("puchaseDetails", function($q) use($unit_id){
                $q->where("unit_id", $unit_id);
            });
        }

        if($search_product){
            $query->whereHas("puchaseDetails", function($q) use($search_product){
                $q->whereHas("product", function($subq) use($search_product){
                    $subq->where(DB::raw("products.title || ' ' || products.sku"), 'ilike', '%' . $search_product . '%');
                });
            });     
        }

        if($user){
            if($user->role_id != 1){
                if($user->role_id == 2){
                    $query->where("sucursal_id", $user->sucuarsal_id);
                } else {
                    $query->where("user_id", $user->id);
                }
            }
        }

        return $query;
    }
}
