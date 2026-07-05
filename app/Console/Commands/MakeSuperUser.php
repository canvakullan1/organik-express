<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Süper admin (tüm erişim) kullanıcısı oluşturur/günceller.
 *
 *   php artisan user:make-super {email} {password} {--name=}
 */
class MakeSuperUser extends Command
{
    protected $signature = 'user:make-super {email} {password} {--name=}';

    protected $description = 'Süper admin (tüm erişimli) kullanıcı oluşturur veya günceller';

    public function handle(): int
    {
        $email = trim($this->argument('email'));
        $password = (string) $this->argument('password');
        $name = $this->option('name') ?: ucwords(str_replace(['.', '_', '-'], ' ', explode('@', $email)[0]));

        // super_admin spatie rolü mevcut değilse oluştur (Shield tam erişim rolü)
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => UserRole::Owner->value,   // panel erişimi için enum rolü
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles([$role]); // tüm izinler super_admin ile gelir

        $this->info("Süper admin hazır: {$user->email} (ad: {$user->name}) — rol: Owner + super_admin");

        return self::SUCCESS;
    }
}
