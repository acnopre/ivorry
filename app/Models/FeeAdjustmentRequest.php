<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeAdjustmentRequest extends Model
{
    protected $fillable = [
        'procedure_id',
        'current_fee',
        'proposed_fee',
        'reason',
        'status',
        'requested_by',
        'reviewed_by',
        'review_remarks',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
