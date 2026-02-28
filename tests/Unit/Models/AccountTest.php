<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_expire_sets_status_to_expired_when_date_passed(): void
    {
        $account = Account::create([
            'company_name' => 'Test Company',
            'policy_code' => 'POL123',
            'hip' => 'HIP001',
            'expiration_date' => now()->subDay(),
            'account_status' => 'active',
            'created_by' => User::factory()->create()->id,
        ]);

        $account->autoExpire();

        $this->assertEquals('expired', $account->account_status);
    }

    public function test_auto_expire_does_not_change_status_when_not_expired(): void
    {
        $account = Account::create([
            'company_name' => 'Test Company',
            'policy_code' => 'POL123',
            'hip' => 'HIP001',
            'expiration_date' => now()->addDay(),
            'account_status' => 'active',
            'created_by' => User::factory()->create()->id,
        ]);

        $account->autoExpire();

        $this->assertEquals('active', $account->account_status);
    }
}
