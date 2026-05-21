<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table seances (TimetableEntry model).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('universite_id')
                  ->constrained('universites')
                  ->onDelete('cascade');

            $table->foreignId('cours_id')
                  ->constrained('cours')
                  ->onDelete('cascade');

            $table->foreignId('salle_id')
                  ->nullable()
                  ->constrained('salles')
                  ->onDelete('cascade');

            $table->string('jour_semaine');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->string('type_seance')->default('cours');

            $table->string('recurrence')->nullable();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['universite_id', 'jour_semaine', 'heure_debut']);
            $table->index(['salle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seances');
    }
};
