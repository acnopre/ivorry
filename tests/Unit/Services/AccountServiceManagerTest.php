<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\Service;
use App\Services\AccountServiceManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountServiceManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_renewal_defaults_sets_quantity_and_unlimited_false(): void
    {
        $services = [
            ['id' => 1, 'quantity' => null, 'default_quantity' => 5],
            ['id' => 2, 'quantity' => 10, 'default_quantity' => 3],
        ];

        AccountServiceManager::applyRenewalDefaults($services);

        $this->assertEquals(5, $services[0]['quantity']);
        $this->assertFalse($services[0]['is_unlimited']);
        $this->assertEquals(10, $services[1]['quantity']);
        $this->assertFalse($services[1]['is_unlimited']);
    }
}
