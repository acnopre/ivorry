<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Procedure extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'basic_dental_service_id',
        'plan_enhancement_id',
        'tooth_number',
        'extraction_number',
        'procedure_date',
        'notes',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'procedure_service')
                    ->withPivot('tooth_number', 'extract_number')
                    ->withTimestamps();
    }


}
