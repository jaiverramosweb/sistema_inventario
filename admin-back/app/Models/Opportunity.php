<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'estimated_amount',
        'pipeline_stage_id',
        'client_id',
        'lead_id',
        'user_id',
        'expected_closed_at',
        'closed_at',
        'priority',
        'description'
    ];

    protected $casts = [
        'expected_closed_at' => 'date',
        'closed_at' => 'date',
        'estimated_amount' => 'decimal:2'
    ];

    public function pipelineStage()
    {
        return $this->belongsTo(PipelineStage::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activities()
    {
        return $this->hasMany(CrmActivity::class);
    }
}
