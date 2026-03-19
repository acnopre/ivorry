<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'account_id',
        'first_name',
        'last_name',
        'middle_name',
        'suffix',
        'member_type',
        'card_number',
        'coc_number',
        'birthdate',
        'gender',
        'email',
        'phone',
        'address',
        'user_id',
        'effective_date',
        'expiration_date',
        'status',
        'inactive_date',
        'import_source',
        'mbl_balance',
        'import_id',
    ];


    protected $with = ['account'];

    protected $casts = [
        'effective_date' => 'date',
        'expiration_date' => 'date',
    ];

    public static function generateCocNumber(): string
    {
        $prefix = 'COC' . now()->format('mdY'); // e.g. COC03182026
        $last = static::where('coc_number', 'like', $prefix . '%')
            ->orderByDesc('coc_number')
            ->value('coc_number');

        $sequence = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($sequence, 2, '0', STR_PAD_LEFT);
    }

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
