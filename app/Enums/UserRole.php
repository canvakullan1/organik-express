<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Editor = 'editor';
    case Operations = 'operations';

    public function getLabel(): string
    {
        return match ($this) {
            self::Owner => 'Sahip',
            self::Admin => 'Yönetici',
            self::Editor => 'Editör',
            self::Operations => 'Depo / Operasyon',
        };
    }

    /**
     * Roles that are allowed to access the admin panel.
     *
     * @return array<int, string>
     */
    public static function panelRoles(): array
    {
        return array_map(fn (self $r) => $r->value, self::cases());
    }
}
