<?php

namespace Database\Seeders;

use App\Models\ConnectedAccount;
use App\Models\MetaCredential;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Initial Users
        $this->call(UserSeeder::class);

        // 1. Initial Meta Credentials
        MetaCredential::getActive();

        // 2. Initial Sample Connected Accounts from existing business portfolios
        $sampleAccounts = [
            [
                'page_id' => '102938475610',
                'page_name' => 'Sevencols Apparel',
                'page_category' => 'Clothing Brand',
                'ig_user_id' => '178414000000001',
                'ig_username' => 'sevencols',
                'ig_name' => 'Sevencols Official',
                'page_access_token' => 'sample_token_sevencols',
                'is_active' => true,
                'ig_publishing_quota_usage' => 12,
                'ig_publishing_quota_total' => 100,
                'last_synced_at' => Carbon::now(),
                'last_verified_at' => Carbon::now(),
            ],
            [
                'page_id' => '203948576120',
                'page_name' => 'Arema Style',
                'page_category' => 'Apparel & Clothing',
                'ig_user_id' => '178414000000002',
                'ig_username' => 'arema_style',
                'ig_name' => 'Arema Style Store',
                'page_access_token' => 'sample_token_aremastyle',
                'is_active' => true,
                'ig_publishing_quota_usage' => 4,
                'ig_publishing_quota_total' => 100,
                'last_synced_at' => Carbon::now(),
                'last_verified_at' => Carbon::now(),
            ],
            [
                'page_id' => '304958671230',
                'page_name' => 'Jasa Bordir Malang',
                'page_category' => 'Embroidery Service',
                'ig_user_id' => null,
                'ig_username' => null,
                'ig_name' => null,
                'page_access_token' => 'sample_token_bordir',
                'is_active' => true,
                'ig_publishing_quota_usage' => 0,
                'ig_publishing_quota_total' => 100,
                'last_synced_at' => Carbon::now(),
                'last_verified_at' => Carbon::now(),
            ],
        ];

        foreach ($sampleAccounts as $acc) {
            ConnectedAccount::firstOrCreate(['page_id' => $acc['page_id']], $acc);
        }
    }
}
