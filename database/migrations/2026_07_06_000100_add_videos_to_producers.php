<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producers', function (Blueprint $table) {
            // Tanıtım videoları: [{"id":"YT_ID","title":"..."}] biçiminde JSON
            $table->json('videos')->nullable()->after('story');
        });
    }

    public function down(): void
    {
        Schema::table('producers', function (Blueprint $table) {
            $table->dropColumn('videos');
        });
    }
};
