<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    //
    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'account_service')
            ->withPivot('quantity', 'remarks', 'is_unlimited')
            ->withTimestamps();
    }

    public function renewal()
    {
        return $this->belongsToMany(AccountRenewal::class, 'account_renewal_services')
            ->withPivot('quantity', 'remarks', 'is_unlimited')
            ->withTimestamps();
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class, 'clinic_id');
    }
    public function procedures()
    {
        return $this->belongsToMany(Procedure::class, 'procedure_service')
            ->withPivot('tooth_number', 'extract_number')
            ->withTimestamps();
    }

    public function unitType()
    {
        return $this->belongsTo(UnitType::class, 'unit_type', 'name');
    }
}
