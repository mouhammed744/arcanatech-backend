<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table presences (Attendance model).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('universite_id')
                  ->constrained('universites')
                  ->onDelete('cascade');

            $table->foreignId('seance_id')
                  ->constrained('seances')
                  ->onDelete('cascade');

            $table->foreignId('etudiant_id')
                  ->constrained('etudiants')
                  ->onDelete('cascade');

            $table->string('statut')->default('absent');
            $table->timestamp('scanne_le')->nullable();
            $table->string('methode_verification')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['seance_id', 'etudiant_id']);
            $table->index(['universite_id', 'etudiant_id', 'statut']);
            $table->index(['seance_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};
