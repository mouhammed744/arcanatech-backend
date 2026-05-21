<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table cartes_rfid (RfidCard model, UUID PK).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cartes_rfid', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignId('universite_id')
                  ->constrained('universites')
                  ->onDelete('cascade');

            $table->foreignId('etudiant_id')
                  ->nullable()
                  ->constrained('etudiants')
                  ->onDelete('cascade');

            $table->foreignId('utilisateur_id')
                  ->nullable()
                  ->constrained('utilisateurs')
                  ->onDelete('set null');

            $table->string('numero_carte')->unique();
            $table->string('type_carte')->default('student');
            $table->boolean('est_active')->default(true);

            $table->timestamp('assignee_le')->useCurrent();
            $table->timestamp('desactivee_le')->nullable();
            $table->string('raison_desactivation')->nullable();
            $table->timestamp('dernier_scan_le')->nullable();

            $table->timestamps();

            $table->index(['universite_id', 'est_active']);
            $table->index(['etudiant_id', 'est_active']);
            $table->index(['type_carte', 'est_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartes_rfid');
    }
};
