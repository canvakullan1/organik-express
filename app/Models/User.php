<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'is_active',
        'accepts_marketing_email',
        'accepts_sms',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'accepts_marketing_email' => 'boolean',
            'accepts_sms' => 'boolean',
        ];
    }

    /**
     * Admin paneline erişim yetkisi: aktif ve tanımlı bir role sahip kullanıcılar.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->role instanceof UserRole;
    }

    /** Basit (enum) rol kontrolü — Shield'in spatie hasRole() metoduyla çakışmaması için ayrı ad. */
    public function isRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    public function addresses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Address::class)->latest('is_default');
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    public function defaultAddress(): ?Address
    {
        return $this->addresses()->where('is_default', true)->first() ?? $this->addresses()->first();
    }

    public function loyaltyTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class)->latest('created_at');
    }

    /** Güncel para puan bakiyesi. */
    public function loyaltyBalance(): float
    {
        return (float) $this->loyaltyTransactions()->sum('points');
    }

    /** Müşteri mi? (Personel/yönetici rolü olmayan kayıtlar müşteridir.) */
    public function isCustomer(): bool
    {
        return ! ($this->role instanceof UserRole);
    }

    public function scopeCustomers($query)
    {
        return $query->whereNull('role');
    }
}
