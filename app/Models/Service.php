<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    //

    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'account_service')->withPivot('quantity')->withTimestamps();
    }

    public function dentists()
    {
        return $this->belongsToMany(Dentist::class, 'dentist_service')->withPivot('fee')->withTimestamps();
    }
    public function procedures()
    {
        return $this->belongsToMany(Procedure::class, 'procedure_service')
                    ->withPivot('tooth_number', 'extract_number')
                    ->withTimestamps();
    }
}
