<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table pivot filiere_cours (relation Filiere <-> Course).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filiere_cours', function (Blueprint $table) {
            $table->id();

            $table->foreignId('filiere_id')
                  ->constrained('filieres')
                  ->onDelete('cascade');

            $table->foreignId('cours_id')
                  ->constrained('cours')
                  ->onDelete('cascade');

            $table->timestamps();

            $table->unique(['filiere_id', 'cours_id']);
            $table->index('filiere_id');
            $table->index('cours_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filiere_cours');
    }
};
