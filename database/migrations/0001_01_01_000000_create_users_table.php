<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table utilisateurs (User model) et les tables de session.
 * NOTE : universite_id est ajouté sans FK ici car universites n'existe pas encore.
 *        La contrainte FK est ajoutée dans create_universities_table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilisateurs', function (Blueprint $table) {
            $table->id();

            // Lien université (FK ajoutée après création de universites)
            $table->unsignedBigInteger('universite_id')->nullable();

            // Identité
            $table->string('prenom')->nullable();
            $table->string('nom')->nullable();
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // 2FA
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->string('two_factor_method', 10)->nullable();
            $table->string('two_factor_email_code', 6)->nullable();
            $table->timestamp('two_factor_email_code_expires_at')->nullable();

            // Coordonnées
            $table->string('telephone', 20)->nullable();
            $table->string('genre', 10)->nullable();
            $table->text('adresse')->nullable();
            $table->string('chemin_avatar')->nullable();

            // Accès
            $table->string('role')->default('student');
            $table->boolean('est_actif')->default(true);
            $table->timestamp('derniere_connexion_le')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Email unique par université
            $table->unique(['universite_id', 'email'], 'utilisateurs_universite_email_unique');
            $table->index(['universite_id', 'role']);
            $table->index(['universite_id', 'est_actif']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('utilisateurs');
    }
};
