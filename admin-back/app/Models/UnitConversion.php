<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class UnitConversion extends Model
{
    protected $fillable = [
        'unit_id',
        'unit_to_id'
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

    // la unidad a la que esta relacionada
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    // la unidad a la que se convierte
    public function unit_to()
    {
        return $this->belongsTo(Unit::class, 'unit_to_id');
    }
}
