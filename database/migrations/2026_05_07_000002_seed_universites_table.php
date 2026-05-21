<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Insère les universités de base dans la table universites (schéma français).
 * Utilisé quand le UniversitySeeder ne peut pas être exécuté directement.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $universities = [
            ['nom' => 'Les Cours Sonou University',                     'code' => 'LCS',    'ville' => 'Cotonou',        'couleur_principale' => '#0D47A1', 'couleur_secondaire' => '#FFC107', 'preset_accent' => 'blue',    'nb_chiffres_matricule' => 8, 'prefixe_matricule' => 'LCS'],
            ["nom" => "Université d'Abomey-Calavi (UAC)",               'code' => 'UAC',    'ville' => 'Abomey-Calavi',  'couleur_principale' => '#1B3A6B', 'couleur_secondaire' => '#FFFFFF', 'preset_accent' => 'indigo',  'nb_chiffres_matricule' => 8, 'prefixe_matricule' => 'UAC'],
            ['nom' => 'Université de Parakou (UP)',                      'code' => 'UP',     'ville' => 'Parakou',        'couleur_principale' => '#1A6B3A', 'couleur_secondaire' => '#2563EB', 'preset_accent' => 'emerald', 'nb_chiffres_matricule' => 8, 'prefixe_matricule' => 'UP'],
            ['nom' => 'UNSTIM',                                          'code' => 'UNSTIM', 'ville' => 'Abomey',         'couleur_principale' => '#E65100', 'couleur_secondaire' => '#1565C0', 'preset_accent' => 'amber',   'nb_chiffres_matricule' => 8, 'prefixe_matricule' => 'UST'],
            ['nom' => "Université Nationale d'Agriculture (UNA)",        'code' => 'UNA',    'ville' => 'Kétou',          'couleur_principale' => '#2E7D32', 'couleur_secondaire' => '#FDD835', 'preset_accent' => 'emerald', 'nb_chiffres_matricule' => 8, 'prefixe_matricule' => 'UNA'],
            ['nom' => 'Institut National Médico-Sanitaire (INMeS)',      'code' => 'INMeS',  'ville' => 'Cotonou',        'couleur_principale' => '#C62828', 'couleur_secondaire' => '#FFFFFF', 'preset_accent' => 'rose',    'nb_chiffres_matricule' => 8, 'prefixe_matricule' => 'INM'],
            ['nom' => "ESGIS",                                           'code' => 'ESGIS',  'ville' => 'Cotonou',        'couleur_principale' => '#1565C0', 'couleur_secondaire' => '#FF6D00', 'preset_accent' => 'blue',    'nb_chiffres_matricule' => 8, 'prefixe_matricule' => 'ESG'],
            ['nom' => 'Institut IFRI',                                   'code' => 'IFRI',   'ville' => 'Abomey-Calavi',  'couleur_principale' => '#1565C0', 'couleur_secondaire' => '#00C853', 'preset_accent' => 'blue',    'nb_chiffres_matricule' => 8, 'prefixe_matricule' => 'IFR'],
            ['nom' => 'EPAC',                                            'code' => 'EPAC',   'ville' => 'Abomey-Calavi',  'couleur_principale' => '#1B3A6B', 'couleur_secondaire' => '#FFD600', 'preset_accent' => 'blue',    'nb_chiffres_matricule' => 8, 'prefixe_matricule' => 'EPA'],
            ['nom' => 'Institut CERCO',                                  'code' => 'CERCO',  'ville' => 'Cotonou',        'couleur_principale' => '#B71C1C', 'couleur_secondaire' => '#1565C0', 'preset_accent' => 'rose',    'nb_chiffres_matricule' => 8, 'prefixe_matricule' => 'CRC'],
            ['nom' => "Houdegbe North American University (HNAUB)",      'code' => 'HNAUB',  'ville' => 'Cotonou',        'couleur_principale' => '#1A237E', 'couleur_secondaire' => '#C62828', 'preset_accent' => 'indigo',  'nb_chiffres_matricule' => 8, 'prefixe_matricule' => 'HNA'],
        ];

        foreach ($universities as $uni) {
            $exists = DB::table('universites')->where('code', $uni['code'])->exists();
            if (!$exists) {
                DB::table('universites')->insert(array_merge($uni, [
                    'fuseau_horaire' => 'Africa/Porto-Novo',
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]));
            }
        }

        // Mettre à jour les utilisateurs sans université → leur affecter LCS par défaut
        $lcs = DB::table('universites')->where('code', 'LCS')->first();
        if ($lcs) {
            DB::table('utilisateurs')
                ->whereNull('universite_id')
                ->update(['universite_id' => $lcs->id]);
        }
    }

    public function down(): void
    {
        DB::table('universites')->whereIn('code', [
            'LCS','UAC','UP','UNSTIM','UNA','INMeS','ESGIS','IFRI','EPAC','CERCO','HNAUB'
        ])->delete();
    }
};
