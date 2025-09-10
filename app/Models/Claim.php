<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    protected $fillable = [
        'member_id',
        'clinic_id',
        'dentist_id',
        'procedure',
        'status',
        'approval_code',
        'payable_amount'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function dentist()
    {
        return $this->belongsTo(Dentist::class);
    }

    public function soa()
    {
        return $this->hasOne(Soa::class);
    }
}
