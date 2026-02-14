<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\TaxRate;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) {
            return;
        }
        $defaultAccounts = [
            ['code' => '1000', 'name' => 'Cash and Bank', 'type' => 'asset'],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset'],
            ['code' => '1500', 'name' => 'Inventory', 'type' => 'asset'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability'],
            ['code' => '2500', 'name' => 'Sales Tax Payable', 'type' => 'liability'],
            ['code' => '3000', 'name' => 'Equity', 'type' => 'equity'],
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'income'],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense'],
            ['code' => '6000', 'name' => 'Operating Expenses', 'type' => 'expense'],
        ];
        foreach ($defaultAccounts as $acc) {
            Account::withoutGlobalScope(\App\Scopes\CompanyScope::class)->firstOrCreate(
                ['company_id' => $company->id, 'code' => $acc['code']],
                array_merge($acc, ['company_id' => $company->id, 'is_system' => true])
            );
        }
        TaxRate::withoutGlobalScope(\App\Scopes\CompanyScope::class)->firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Sales Tax 0%'],
            ['company_id' => $company->id, 'rate' => 0, 'type' => 'normal', 'enabled' => true]
        );
    }
}
