<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedSoaProcedure extends Model
{
    protected $fillable = [
        'generated_soa_id',
        'procedure_id',
    ];

    public function soa()
    {
        return $this->belongsTo(GeneratedSoa::class, 'generated_soa_id');
    }

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }
}
