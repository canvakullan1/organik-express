<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('loyalty.enabled', true);
        $this->migrator->add('loyalty.earn_rate', 2.0);            // %2 puan kazanımı
        $this->migrator->add('loyalty.max_redeem_percent', 30.0); // sepetin en fazla %30'u
        $this->migrator->add('loyalty.min_balance_to_redeem', 50.0);
        $this->migrator->add('loyalty.min_order_to_earn', 0.0);
    }
};
