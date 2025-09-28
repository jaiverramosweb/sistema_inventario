<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
