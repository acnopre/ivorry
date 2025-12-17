<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratedSoa extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'from_date',
        'to_date',
        'total_amount',
        'file_path',
        'duplicate_file_path',
        'generated_by',
        'status',
    ];

    public function procedures()
    {
        return $this->belongsToMany(Procedure::class, 'generated_soa_procedure')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
