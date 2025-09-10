<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Soa extends Model
{
    protected $fillable = ['clinic_id', 'claim_id', 'total_amount', 'status'];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function claim()
    {
        return $this->belongsTo(Claim::class);
    }
}
