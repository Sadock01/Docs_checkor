<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Ajouter la colonne role_id
            $table->unsignedBigInteger('role_id')->nullable();

            // Ajouter la clé étrangère vers la table roles
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Supprimer la clé étrangère
            $table->dropForeign(['role_id']);
            
            // Supprimer la colonne role_id
            $table->dropColumn('role_id');
        });
    }
};
