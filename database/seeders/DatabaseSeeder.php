<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['name' => 'Default Company'],
            ['default_currency' => 'USD', 'email' => 'admin@example.com']
        );
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => bcrypt('password')]
        );
        if (!$user->companies()->where('companies.id', $company->id)->exists()) {
            $user->companies()->attach($company->id);
        }
        $this->call(CompanySeeder::class);
        $this->call(RolePresetsSeeder::class);
    }
}
