<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table journaux_audit (AuditLog model).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journaux_audit', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignId('universite_id')
                  ->nullable()
                  ->constrained('universites')
                  ->onDelete('cascade');

            $table->foreignId('utilisateur_id')
                  ->nullable()
                  ->constrained('utilisateurs')
                  ->onDelete('set null');

            $table->string('type_ressource');
            $table->string('id_ressource')->nullable(); // string pour supporter bigint ET uuid
            $table->string('action');

            $table->jsonb('anciennes_valeurs')->nullable();
            $table->jsonb('nouvelles_valeurs')->nullable();

            $table->ipAddress('adresse_ip')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['utilisateur_id', 'created_at']);
            $table->index(['type_ressource', 'id_ressource']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journaux_audit');
    }
};
