<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberService extends Model
{
    protected $table = 'member_service';

    protected $fillable = [
        'card_number',
        'account_id',
        'service_id',
        'quantity',
        'default_quantity',
        'is_unlimited',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Initialize member service quantities from account services for a given card_number.
     * Works for both SHARED and INDIVIDUAL accounts.
     */
    public static function initializeForCard(string $cardNumber, int $accountId): void
    {
        $account = Account::find($accountId);
        if (!$account) return;

        foreach ($account->services as $service) {
            static::firstOrCreate(
                [
                    'card_number' => $cardNumber,
                    'account_id' => $accountId,
                    'service_id' => $service->id,
                ],
                [
                    'quantity' => $service->pivot->default_quantity ?? $service->pivot->quantity ?? 0,
                    'default_quantity' => $service->pivot->default_quantity ?? $service->pivot->quantity ?? 0,
                    'is_unlimited' => $service->pivot->is_unlimited,
                ]
            );
        }
    }
}
