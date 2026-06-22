<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title')->default('Adresim');     // ör. Ev, İş
            $table->string('type')->default('both');          // shipping | billing | both
            $table->boolean('is_corporate')->default(false);  // bireysel/kurumsal

            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('tax_office')->nullable();
            $table->string('tax_number')->nullable();

            $table->string('phone');
            $table->string('city');
            $table->string('district');
            $table->string('neighborhood')->nullable();
            $table->text('address');
            $table->string('postal_code')->nullable();

            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
