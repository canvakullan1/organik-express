<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'accepts_marketing_email')) {
                $table->boolean('accepts_marketing_email')->default(true)->after('is_active');
            }
            if (! Schema::hasColumn('users', 'accepts_sms')) {
                $table->boolean('accepts_sms')->default(false)->after('accepts_marketing_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['accepts_marketing_email', 'accepts_sms']);
        });
    }
};
