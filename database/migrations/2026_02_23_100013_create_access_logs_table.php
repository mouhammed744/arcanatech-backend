<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table journaux_acces (AccessLog model, UUID PK).
 * Inclut aussi les colonnes admin override (add_admin_override_to_access_logs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journaux_acces', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignId('universite_id')
                  ->constrained('universites')
                  ->onDelete('cascade');

            $table->foreignUuid('carte_rfid_id')
                  ->nullable()
                  ->constrained('cartes_rfid')
                  ->onDelete('cascade');

            $table->foreignId('salle_id')
                  ->nullable()
                  ->constrained('salles')
                  ->onDelete('cascade');

            $table->string('statut');
            $table->timestamp('scanne_le')->useCurrent();
            $table->string('raison_refus')->nullable();

            // Admin override
            $table->boolean('est_override_admin')->default(false);
            $table->foreignId('override_par_utilisateur_id')
                  ->nullable()
                  ->constrained('utilisateurs')
                  ->onDelete('set null');
            $table->text('raison_override')->nullable();
            $table->string('type_acces')->default('normal');

            $table->timestamp('created_at')->useCurrent();

            $table->index(['carte_rfid_id', 'scanne_le']);
            $table->index(['salle_id', 'scanne_le']);
            $table->index(['statut', 'scanne_le']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journaux_acces');
    }
};
