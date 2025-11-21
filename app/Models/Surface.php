<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Surface extends Model
{
    protected $table = 'surfaces';
    protected $fillable = ['name'];
}
