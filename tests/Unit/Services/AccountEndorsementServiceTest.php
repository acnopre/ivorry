<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\AccountRenewal;
use App\Models\AccountAmendment;
use App\Models\Service;
use App\Models\User;
use App\Services\AccountEndorsementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountEndorsementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_pending_renewals_removes_only_pending_status(): void
    {
        $account = Account::create([
            'company_name' => 'Test',
            'policy_code' => 'POL123',
            'hip' => 'HIP001',
            'created_by' => User::factory()->create()->id,
        ]);

        $pendingRenewal = AccountRenewal::create([
            'account_id' => $account->id,
            'status' => 'PENDING',
            'effective_date' => now(),
            'expiration_date' => now()->addYear(),
            'requested_by' => User::factory()->create()->id,
        ]);

        $approvedRenewal = AccountRenewal::create([
            'account_id' => $account->id,
            'status' => 'APPROVED',
            'effective_date' => now(),
            'expiration_date' => now()->addYear(),
            'requested_by' => User::factory()->create()->id,
        ]);

        AccountEndorsementService::deletePendingRenewals($account->id);

        $this->assertSoftDeleted('account_renewals', ['id' => $pendingRenewal->id]);
        $this->assertDatabaseHas('account_renewals', ['id' => $approvedRenewal->id, 'deleted_at' => null]);
    }

    public function test_delete_pending_amendments_removes_only_pending_status(): void
    {
        $account = Account::create([
            'company_name' => 'Test',
            'policy_code' => 'POL123',
            'hip' => 'HIP001',
            'created_by' => User::factory()->create()->id,
        ]);

        $pendingAmendment = AccountAmendment::create([
            'account_id' => $account->id,
            'endorsement_status' => 'PENDING',
            'company_name' => 'Test',
            'policy_code' => 'POL123',
            'requested_by' => User::factory()->create()->id,
        ]);

        $approvedAmendment = AccountAmendment::create([
            'account_id' => $account->id,
            'endorsement_status' => 'APPROVED',
            'company_name' => 'Test',
            'policy_code' => 'POL123',
            'requested_by' => User::factory()->create()->id,
        ]);

        AccountEndorsementService::deletePendingAmendments($account->id);

        $this->assertSoftDeleted('account_amendments', ['id' => $pendingAmendment->id]);
        $this->assertDatabaseHas('account_amendments', ['id' => $approvedAmendment->id, 'deleted_at' => null]);
    }
}
