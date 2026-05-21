<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table filieres (Filiere model) avec colonnes françaises.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filieres', function (Blueprint $table) {
            $table->id();

            $table->foreignId('universite_id')
                  ->constrained('universites')
                  ->onDelete('cascade');

            $table->string('code', 20);
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('niveau', 10)->default('L1');
            $table->string('departement')->nullable();
            $table->boolean('est_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['universite_id', 'code']);
            $table->index(['universite_id', 'est_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filieres');
    }
};
