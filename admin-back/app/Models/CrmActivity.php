<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmActivity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'opportunity_id',
        'lead_id',
        'user_id',
        'type',
        'description',
        'activity_date'
    ];

    protected $casts = [
        'activity_date' => 'datetime'
    ];

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
