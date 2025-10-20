<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureUnit extends Model
{
    protected $fillable = [
        'procedure_id',
        'unit_id',
        'quantity',
    ];

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }

    public function unitType()
    {
        return $this->belongsTo(UnitType::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
