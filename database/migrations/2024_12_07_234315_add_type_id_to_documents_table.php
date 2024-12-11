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
        Schema::table('documents', function (Blueprint $table) {
            // Ajout de la colonne type_id
            $table->unsignedBigInteger('type_id');

            // Définir la clé étrangère
            $table->foreign('type_id')->references('id')->on('types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Supprimer la clé étrangère si elle existe
            $table->dropForeign(['type_id']);

            // Supprimer la colonne type_id
            $table->dropColumn('type_id');
        });
    }
};
