<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin Toko',
            'email' => 'admin@testing.com',
        ]);
        $admin->assignRole('admin');

        $kasir = User::factory()->create([
            'name' => 'Kasir Toko',
            'email' => 'kasir@testing.com',
        ]);
        $kasir->assignRole('kasir');

        $superadmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@testing.com',
            'is_anonymized' => true]);
        $superadmin->assignRole('superadmin');

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            TransactionSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
