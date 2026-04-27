<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicService extends Model
{
    protected $table = 'clinic_services';

    protected $fillable = [
        'clinic_id',
        'service_id',
        'fee',
        'old_fee',
        'new_fee',
        'effective_date',
        'approved_at',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'approved_at'    => 'datetime',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
