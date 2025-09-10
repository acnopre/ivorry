<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    protected $fillable = ['member_id', 'dentist_id', 'procedure', 'status', 'approval_code'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function dentist()
    {
        return $this->belongsTo(Dentist::class);
    }
}
