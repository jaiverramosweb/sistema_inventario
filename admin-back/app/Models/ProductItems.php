<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductItems extends Model
{
    protected $table = 'product_items';

    protected $fillable = [
        'parent_product_id',  // La Laptop
        'child_product_id',   // El Disco/RAM
        'cost_at_installation', // Costo que tenía la pieza al momento de ponerla
        'affects_final_price',  // Boolean: ¿Suma al valor del equipo?
        'user_id'
    ];

    // El equipo al que pertenece este componente
    public function parentProduct()
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    // La pieza en sí (para sacar serial, marca, modelo)
    public function childProduct()
    {
        return $this->belongsTo(Product::class, 'child_product_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
