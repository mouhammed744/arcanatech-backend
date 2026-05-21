<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table cours (Course model).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cours', function (Blueprint $table) {
            $table->id();

            $table->foreignId('universite_id')
                  ->constrained('universites')
                  ->onDelete('cascade');

            $table->foreignId('enseignant_id')
                  ->nullable()
                  ->constrained('enseignants')
                  ->onDelete('set null');

            $table->string('code');
            $table->unique(['universite_id', 'code']);

            $table->string('nom');
            $table->text('description')->nullable();
            $table->unsignedInteger('credits')->nullable();
            $table->string('niveau', 10)->nullable();
            $table->unsignedInteger('semestre')->nullable();
            $table->unsignedInteger('minutes_retard_max')->default(5);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['universite_id', 'enseignant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cours');
    }
};
