<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table universites (University model).
 * Ajoute ensuite la FK universite_id sur la table utilisateurs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universites', function (Blueprint $table) {
            $table->id();

            // Identité
            $table->string('nom');
            $table->string('code', 20)->unique()->nullable();
            $table->string('fuseau_horaire')->default('Africa/Porto-Novo');

            // Coordonnées
            $table->text('adresse')->nullable();
            $table->string('ville', 100)->nullable();
            $table->string('telephone', 20)->nullable();

            // Personnalisation
            $table->string('couleur_principale', 7)->default('#1565C0');
            $table->string('couleur_secondaire', 7)->default('#FFFFFF');
            $table->string('preset_accent', 20)->default('blue');
            $table->unsignedTinyInteger('nb_chiffres_matricule')->default(8);
            $table->string('prefixe_matricule', 10)->nullable();
            $table->string('url_logo')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // Ajouter la FK universite_id maintenant que la table universites existe
        Schema::table('utilisateurs', function (Blueprint $table) {
            $table->foreign('universite_id')
                  ->references('id')
                  ->on('universites')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('utilisateurs', function (Blueprint $table) {
            $table->dropForeign(['universite_id']);
        });
        Schema::dropIfExists('universites');
    }
};
