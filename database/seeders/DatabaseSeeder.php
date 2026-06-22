<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Yönetici (owner) hesabı
        $admin = User::updateOrCreate(
            ['email' => 'admin@organik.test'],
            [
                'name' => 'Site Sahibi',
                'password' => bcrypt('admin1234'),
                'role' => 'owner',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Süper admin rolü (Shield) — tüm yetkileri bypass eder.
        // İzinler `php artisan shield:generate --all` ile üretilir.
        if (class_exists(Role::class)) {
            $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
            $admin->assignRole($superAdmin);
        }

        $this->call([
            CatalogSeeder::class,
            ProductSeeder::class,
            PageSeeder::class,
            BlogSeeder::class,
            MenuSeeder::class,
            DemoMediaSeeder::class,
            ProducerSeeder::class,
            CertificateSeeder::class,
            BundleSeeder::class,
            LogisticsLoyaltySeeder::class,
            AnalyticsDemoSeeder::class,
        ]);
    }
}
