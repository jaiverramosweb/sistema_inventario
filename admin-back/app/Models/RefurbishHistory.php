<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefurbishHistory extends Model
{
    protected $table = 'refurbish_history';
    protected $fillable = [
        'product_id', 'user_id', 'sucursal_id', 'action', 
        'component_id', 'previous_equipment_cost', 'new_equipment_cost', 
        'cost_impact', 'component_serial', 'comments'
    ];

    public function equipment() {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function component() {
        return $this->belongsTo(Product::class, 'component_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
