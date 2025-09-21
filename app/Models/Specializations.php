<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Specializations extends Model
{
    protected $table = 'specializations';
    protected $fillable = ['name'];

    public function dentists()
    {
        return $this->belongsToMany(
            Dentist::class,
            'dentist_specializations',
            'specialization_id', // FK on pivot
            'dentist_id'         // related key on pivot
        );
    }
}

