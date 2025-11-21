<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureSurface extends Model
{
    protected $fillable = ['procedure_unit_id', 'surface_id'];

    public function surface()
    {
        return $this->belongsTo(Surface::class);
    }

    public function procedureUnit()
    {
        return $this->belongsTo(ProcedureUnit::class);
    }
}
