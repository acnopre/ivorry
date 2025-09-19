<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanEnhancement extends Model
{
    use HasFactory;

    protected $table = 'plan_enhancements';

    protected $fillable = [
        'name',
    ];
    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'account_plan_enhancement');
    }
}
