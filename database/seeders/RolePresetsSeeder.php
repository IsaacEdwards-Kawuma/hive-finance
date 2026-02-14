<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use App\Services\Permissions;
use Illuminate\Database\Seeder;

class RolePresetsSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();
        if ($companies->isEmpty()) {
            return;
        }

        $presets = $this->presets();

        foreach ($companies as $company) {
            foreach ($presets as $name => $data) {
                Role::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'name' => $name,
                    ],
                    [
                        'display_name' => $data['display_name'],
                        'permissions' => $data['permissions'],
                    ]
                );
            }
        }
    }

    private function presets(): array
    {
        $all = Permissions::ALL;
        $ceo = array_values(array_filter($all, fn ($p) => $p !== Permissions::ADMIN_FULL));

        $finance = [
            'reports.view',
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete',
            'bills.view', 'bills.create', 'bills.edit', 'bills.delete',
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            'vendors.view', 'vendors.create', 'vendors.edit', 'vendors.delete',
            'accounts.view', 'accounts.create', 'accounts.edit', 'accounts.delete',
            'journal-entries.view', 'journal-entries.create', 'journal-entries.edit', 'journal-entries.delete', 'journal-entries.post',
            'banking.view', 'banking.create', 'banking.edit', 'banking.delete',
            'items.view', 'items.create', 'items.edit', 'items.delete',
            'tax-rates.view', 'tax-rates.create', 'tax-rates.edit', 'tax-rates.delete',
            'investments.view', 'investments.create', 'investments.edit', 'investments.delete',
            'communications.view', 'communications.create', 'communications.edit', 'communications.delete',
        ];

        $operations = [
            'shifts.manage',
            'shifts.view',
            'employee.dashboard',
            'communications.view', 'communications.create', 'communications.edit', 'communications.delete',
        ];

        $secretary = [
            'secretary.dashboard',
            'shifts.view',
            'employee.dashboard',
            'communications.view', 'communications.create', 'communications.edit', 'communications.delete',
            'meetings.view', 'meetings.create', 'meetings.edit', 'meetings.delete',
        ];

        $employee = [
            'employee.dashboard',
        ];

        return [
            'admin' => [
                'display_name' => 'Admin / IT',
                'permissions' => $all,
            ],
            'ceo' => [
                'display_name' => 'CEO / Director',
                'permissions' => $ceo,
            ],
            'finance-manager' => [
                'display_name' => 'Finance Manager',
                'permissions' => $finance,
            ],
            'operations-manager' => [
                'display_name' => 'Operations Manager',
                'permissions' => $operations,
            ],
            'secretary' => [
                'display_name' => 'Secretary',
                'permissions' => $secretary,
            ],
            'employee' => [
                'display_name' => 'Employee',
                'permissions' => $employee,
            ],
        ];
    }
}
