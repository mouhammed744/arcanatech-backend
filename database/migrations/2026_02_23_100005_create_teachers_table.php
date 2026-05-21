<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table enseignants (Teacher model).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enseignants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('universite_id')
                  ->constrained('universites')
                  ->onDelete('cascade');

            $table->foreignId('utilisateur_id')
                  ->unique()
                  ->constrained('utilisateurs')
                  ->onDelete('cascade');

            $table->string('specialite')->nullable();
            $table->string('grade')->nullable();
            $table->date('date_embauche')->nullable();
            $table->string('statut')->default('actif');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['universite_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enseignants');
    }
};
