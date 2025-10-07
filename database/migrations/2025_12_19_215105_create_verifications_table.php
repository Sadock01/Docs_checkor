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
       Schema::create('verifications', function (Blueprint $table) {
    $table->id();

   

    // IP publique de l'utilisateur — utile pour la géolocalisation approximative ou la détection d'abus
    $table->string('ip_address')->nullable();

    // Informations du navigateur (User-Agent complet) — permet d’identifier le type d'appareil ou de détecter des patterns suspects
    $table->text('user_agent')->nullable();

    // Informations simplifiées dérivées du User-Agent (ex: Chrome, Windows, etc.)
    $table->string('browser')->nullable();      // ex: Chrome
    $table->string('device_type')->nullable();  // ex: desktop / mobile / tablet / bot
    $table->string('platform')->nullable();     // ex: Windows / iOS / Android

    // La méthode utilisée : via fichier ou via formulaire
    $table->boolean('via_file')->default(false); // true = fichier uploadé, false = manuel

    // Données entrées par l’utilisateur
    $table->json('entered_data')->nullable();

    // Données extraites du fichier PDF si fichier fourni
    $table->json('extracted_data')->nullable();

    // Est-ce qu’un document a été trouvé dans la base ?
    $table->boolean('document_found')->default(false);

    // Est-ce que les données correspondent à 100 % ?
    $table->boolean('is_matching')->default(false);

    // Les champs qui ne correspondent pas (ex: nom, date, etc.)
    $table->json('mismatches')->nullable();

    // Pour audit
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};
