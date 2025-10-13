<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    //

 
    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'account_service')
            ->withPivot('quantity', 'remarks', 'is_unlimited') // <-- ADDED FIELDS
            ->withTimestamps();
    }

    public function clinic()
    {
        return $this->belongsTo(Clinics::class, 'clinic_id');
    }
    public function procedures()
    {
        return $this->belongsToMany(Procedure::class, 'procedure_service')
                    ->withPivot('tooth_number', 'extract_number')
                    ->withTimestamps();
    }
}
