<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table codes_acces_temporaires (codes temporaires pour présence manuelle).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codes_acces_temporaires', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignId('universite_id')
                  ->constrained('universites')
                  ->onDelete('cascade');

            $table->foreignId('etudiant_id')
                  ->constrained('etudiants')
                  ->onDelete('cascade');

            $table->foreignId('genere_par')
                  ->constrained('utilisateurs')
                  ->onDelete('cascade');

            $table->foreignId('salle_id')
                  ->nullable()
                  ->constrained('salles')
                  ->onDelete('set null');

            $table->foreignId('seance_id')
                  ->nullable()
                  ->constrained('seances')
                  ->onDelete('set null');

            $table->string('code', 8)->unique();
            $table->string('raison')->nullable();
            $table->timestamp('expire_le');
            $table->timestamp('utilise_le')->nullable();
            $table->boolean('est_utilise')->default(false);

            $table->timestamps();

            $table->index(['code', 'est_utilise', 'expire_le']);
            $table->index(['etudiant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codes_acces_temporaires');
    }
};
