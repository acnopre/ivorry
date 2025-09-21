<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DentalService extends Model
{
    protected $fillable = [
        'dentist_id',
        'fee'
    ];

    public function dentist()
    {
        return $this->belongsTo(Dentist::class);
    }
}
