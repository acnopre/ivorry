<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BasicDentalService extends Model
{
    use HasFactory;

    protected $table = 'basic_dental_services';

    protected $fillable = [
        'name',
    ];

    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'account_basic_dental_service')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function dentists()
    {
        return $this->belongsToMany(Dentist::class, 'dentist_basic_dental_service');
    }
}
