<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicService extends Model
{
    //
    protected $fillable = [
        'clinic_id',
        'service_id',
        'fee', //fee to be used
        'old_fee', //for history
        'new_fee', //if ever approved
    ];
}
