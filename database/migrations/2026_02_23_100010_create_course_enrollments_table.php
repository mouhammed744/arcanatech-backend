<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table inscriptions (CourseEnrollment model).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('universite_id')
                  ->constrained('universites')
                  ->onDelete('cascade');

            $table->foreignId('etudiant_id')
                  ->constrained('etudiants')
                  ->onDelete('cascade');

            $table->foreignId('cours_id')
                  ->constrained('cours')
                  ->onDelete('cascade');

            $table->string('statut')->default('inscrit');
            $table->timestamp('inscrit_le')->useCurrent();
            $table->timestamp('complete_le')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['etudiant_id', 'cours_id']);
            $table->index(['universite_id', 'etudiant_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};
