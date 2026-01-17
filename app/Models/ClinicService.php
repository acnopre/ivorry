<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicService extends Model
{
    //
    protected $fillable = [
        'clinic_id',
        'service_id',
        'fee',
        'old_fee',
        'new_fee',
    ];
}
