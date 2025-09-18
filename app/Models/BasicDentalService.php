<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BasicDentalService extends Model
{
    use HasFactory;

    protected $table = 'basic_dental_services';

    protected $fillable = [
        'name',
    ];
}
