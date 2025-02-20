<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('document_user', function (Blueprint $table) {
            Schema::table('document_user', function (Blueprint $table) {
                $table->json('old_values')->nullable()->after('user_id'); // Anciennes valeurs avant modification
                $table->json('new_values')->nullable()->after('old_values'); // Nouvelles valeurs après modification

            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_user', function (Blueprint $table) {
            $table->dropColumn(['old_values', 'new_values']);
        });
    }
};
