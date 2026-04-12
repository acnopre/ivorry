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
     * Initialize family service quantities from account services for a given card_number.
     */
    public static function initializeForFamily(string $cardNumber, int $accountId): void
    {
        $account = Account::find($accountId);
        if (!$account || strtoupper($account->plan_type) !== 'SHARED') return;

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
