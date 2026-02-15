<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PipelineStage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'color',
        'order',
        'description'
    ];

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }
}
