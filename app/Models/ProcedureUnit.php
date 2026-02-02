<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProcedureUnit extends Pivot
{
    protected $table = 'procedure_units';
    protected $fillable = [
        'procedure_id',
        'unit_id',
        'quantity',
        'surface_id',
        'input_quantity',
    ];

    public $incrementing = true;
    protected $with = ['unitType', 'surface'];

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }

    public function unitType()
    {
        return $this->belongsTo(UnitType::class);
    }
    public function surface()
    {
        return $this->belongsTo(Unit::class, 'surface_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
