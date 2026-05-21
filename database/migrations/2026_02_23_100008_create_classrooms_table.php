<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table salles (Classroom model).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('universite_id')
                  ->constrained('universites')
                  ->onDelete('cascade');

            $table->string('nom');
            $table->unique(['universite_id', 'nom']);

            $table->string('batiment')->nullable();
            $table->string('numero_salle')->nullable();
            $table->unsignedInteger('capacite')->nullable();
            $table->text('equipement')->nullable();
            $table->string('type')->default('classroom');
            $table->boolean('est_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['universite_id', 'est_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salles');
    }
};
