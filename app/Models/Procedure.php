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
        'clinic_id',
        'availment_date',
        'status',
        'approval_code',
        'remarks',
        'quantity',
        'applied_fee',
        'adc_number',
        'is_fee_adjusted',
        'adc_number_from',
        'is_migrated',
    ];

    protected $casts = [
        'availment_date' => 'date',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_SIGN = 'signed';
    public const STATUS_VALID = 'valid';
    public const STATUS_REJECT = 'invalid';
    public const STATUS_RETURN = 'returned';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $with = ['units'];

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
        return $this->belongsTo(Clinic::class);
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
    public function units()
    {
        return $this->belongsToMany(Unit::class, 'procedure_units', 'procedure_id', 'unit_id')
            ->using(ProcedureUnit::class)
            ->withPivot(['quantity', 'surface_id', 'input_quantity'])
            ->withTimestamps();
    }

    public function surface_units()
    {
        return $this->belongsToMany(Unit::class, 'procedure_units', 'procedure_id', 'surface_id')
            ->using(ProcedureUnit::class)
            ->withPivot(['quantity', 'unit_id', 'input_quantity'])
            ->withTimestamps();
    }
    public function getClinicNameAttribute()
    {
        // Get clinic from the service
        return $this->service->clinic->clinic_name ?? '—';
    }

    public function signatures()
    {
        return $this->hasMany(ProcedureSignature::class);
    }

    public function isFullySigned(): bool
    {
        return $this->signatures()->count() >= 3;
    }
    public function getSignatureUrlAttribute(): ?string
    {
        return $this->signature_path ? Storage::url($this->signature_path) : null;
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


    public function generatedSoas()
    {
        return $this->belongsToMany(GeneratedSoa::class, 'generated_soa_procedure')
            ->withPivot('amount')
            ->withTimestamps();
    }
}
