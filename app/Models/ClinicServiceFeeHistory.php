<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicServiceFeeHistory extends Model
{
    protected $fillable = [
        'clinic_id',
        'service_id',
        'old_fee',
        'new_fee',
        'effective_date',
        'approved_by',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
