<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dentist extends Model
{
    protected $fillable = ['clinic_id', 'name', 'specialty', 'status'];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault();
    }
}

