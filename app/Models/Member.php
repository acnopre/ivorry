<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = [
        'account_id',
        'name',
        'member_type',
        'card_number',
        'birthdate',
        'gender',
        'email',
        'phone',
        'address',
        'user_id',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->middle_initial} {$this->last_name}";
    }

    public function procedures()
    {
        return $this->hasMany(Procedure::class);
    }

}
