<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Procedure extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'service_id',
        'clinics_id',
        'availment_date',
        'status',
        'approval_code',
        'remarks',
    ];

    /**
     * Get the member associated with the procedure.
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
     /**
     * Get the clinic associated with the procedure.
     */
    public function clinic()
    {
        return $this->belongsTo(Clinics::class);
    }
    /**
     * Get the service associated with the procedure.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the units linked to this procedure.
     */
    public function units(): HasMany
    {
        return $this->hasMany(ProcedureUnit::class);
    }
    
    public function getClinicNameAttribute()
    {
        // Get clinic from the service
        return $this->service->clinic->clinic_name ?? '—';
    }

    public function getDentistNameAttribute()
    {
        // Option 1: If procedure has a direct dentist relationship, use it
        if ($this->clinic?->dentists) {
            // If multiple dentists, just get the owner
            $ownerDentist = $this->clinic->dentists->firstWhere('is_owner', true);
            return $ownerDentist
                ? ($ownerDentist->user?->name ?? $ownerDentist->first_name . ' ' . $ownerDentist->last_name)
                : '—';
        }

        return '—';
    }
}
