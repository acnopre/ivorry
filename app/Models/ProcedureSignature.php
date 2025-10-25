<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureSignature extends Model
{
    protected $fillable = [
        'procedure_id',
        'signer_name',
        'signer_type',
        'signature_path',
    ];

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }
}
