<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Seeder;

class UpdateMemberMblBalanceSeeder extends Seeder
{
    public function run(): void
    {
        Member::whereIn('id', [1, 2])->update(['mbl_balance' => 50000]);
    }
}
