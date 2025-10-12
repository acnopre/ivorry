<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitType extends Model
{
    protected $fillable = ['name'];

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
}
