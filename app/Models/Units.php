<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Units extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // Relationships
    public function quadrants()
    {
        return $this->hasMany(UnitsQuadrant::class);
    }

    public function teeth()
    {
        return $this->hasMany(UnitsTooth::class);
    }

    public function arches()
    {
        return $this->hasMany(UnitsArch::class);
    }

    public function surfaces()
    {
        return $this->hasMany(UnitsSurface::class);
    }
}
