<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table jetons_jwt (JwtToken model).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jetons_jwt', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignId('utilisateur_id')
                  ->constrained('utilisateurs')
                  ->onDelete('cascade');

            $table->foreignId('universite_id')
                  ->nullable()
                  ->constrained('universites')
                  ->onDelete('cascade');

            $table->enum('type', ['access', 'refresh']);

            $table->text('jeton');
            $table->text('jeton_jwt')->nullable();

            $table->timestamp('expire_le');
            $table->timestamp('revoque_le')->nullable();

            $table->ipAddress('adresse_ip')->nullable();
            $table->text('agent_utilisateur')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['utilisateur_id', 'type']);
            $table->index(['utilisateur_id', 'revoque_le']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jetons_jwt');
    }
};
