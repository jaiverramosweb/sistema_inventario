<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'surname',
        'email',
        'phone',
        'source',
        'status',
        'user_id',
        'sucursal_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sucursale()
    {
        return $this->belongsTo(Sucursale::class, 'sucursal_id');
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }

    public function activities()
    {
        return $this->hasMany(CrmActivity::class);
    }
}
