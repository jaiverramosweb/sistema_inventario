<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'title',
        'imagen',
        'price_general',
        'price_company',
        'description',
        'is_discount',
        'max_descount',
        'is_gift',
        'available',
        'status',
        'status_stok',
        'warranty_day',
        'tax_selected',
        'importe_iva',
        'sku'
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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function wallets()
    {
        return $this->hasMany(ProductWallet::class);
    }
    public function warehouses()
    {
        return $this->hasMany(ProductWarehouse::class);
    }

    public function getProductImagenAttribute()
    {
        $link = null;
        if($this->imagen){
            if(str_contains($this->imagen,"https://") || str_contains($this->imagen,"http://")){
                $link = $this->imagen;
            }else{
                $link =  env('APP_URL').'storage/'.$this->imagen;
            }
        }
        return $link;
    }

    public function scopeFilterAdvance($query, $search, $category_id, $warehouse_id, $unit_id, $sucursale_id, $available, $is_gift)
    {
        if($search){
            $query->where(DB::raw("products.title || ' ' || products.sku"), 'ilike', '%' . $search . '%');
        }

        if($category_id){
            $query->where('category_id', $category_id);
        }

        if($available){
            $query->where('available', $available);
        }

        if($is_gift){
            $query->where('is_gift', $is_gift);
        }

        if($warehouse_id){
            $query->whereHas("warehouses", function($warehouse) use($warehouse_id) {
                $warehouse->where('warehouse_id', $warehouse_id);
            });
        }
        
        if($unit_id){
            $query->whereHas("warehouses", function($warehouse) use($unit_id) {
                $warehouse->where('unit_id', $unit_id);
            });
        }

        if($sucursale_id){
            $query->whereHas("wallets", function($wallet) use($sucursale_id) {
                $wallet->where("sucursal_id", $sucursale_id);
            });
        }

        return $query;
    }
}
