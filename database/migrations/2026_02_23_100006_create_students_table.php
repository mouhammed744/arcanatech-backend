<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table etudiants (Student model).
 * NOTE : filiere_id est ajouté sans FK ici car filieres n'existe pas encore.
 *        La contrainte FK est ajoutée dans add_filiere_id_to_students_table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etudiants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('universite_id')
                  ->constrained('universites')
                  ->onDelete('cascade');

            $table->foreignId('utilisateur_id')
                  ->unique()
                  ->constrained('utilisateurs')
                  ->onDelete('cascade');

            $table->string('numero_matricule');
            $table->unique(['universite_id', 'numero_matricule']);

            $table->date('date_naissance')->nullable();
            $table->string('niveau', 10)->nullable();
            $table->unsignedInteger('annee_inscription')->nullable();

            // filiere_id sans FK pour l'instant (filieres créée plus tard)
            $table->unsignedBigInteger('filiere_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['universite_id', 'niveau']);
            $table->index(['filiere_id', 'universite_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etudiants');
    }
};
