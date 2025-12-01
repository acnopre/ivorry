<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountRenewal extends Model
{
    protected $fillable = [
        'account_id',
        'effective_date',
        'expiration_date',
        'status',
        'requested_by',
        'approved_by'
    ];

    protected $with = ['services'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(AccountRenewalService::class, 'renewal_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
