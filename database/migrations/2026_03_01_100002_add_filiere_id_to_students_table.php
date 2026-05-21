<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la contrainte FK filiere_id sur la table etudiants.
 * La colonne filiere_id existe déjà (créée sans FK dans create_students_table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etudiants', function (Blueprint $table) {
            $table->foreign('filiere_id')
                  ->references('id')
                  ->on('filieres')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('etudiants', function (Blueprint $table) {
            $table->dropForeign(['filiere_id']);
        });
    }
};
