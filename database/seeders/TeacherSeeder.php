<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\University;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * TeacherSeeder — Enseignants LCS avec noms béninois
 */
class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $university = University::where('code', 'LCS')->first();

        $teachers = [
            [
                'prenom'     => 'Rodrigue',
                'nom'        => 'AGOSSOU',
                'email'      => 'r.agossou@lcs.edu',
                'telephone'  => '+229 96 11 22 33',
                'specialite' => 'Droit civil et droit des affaires',
                'grade'      => 'Professeur Titulaire',
            ],
            [
                'prenom'     => 'Eugénie',
                'nom'        => 'HOUÉNOU',
                'email'      => 'e.houenou@lcs.edu',
                'telephone'  => '+229 97 44 55 66',
                'specialite' => 'Sciences Économiques et Gestion',
                'grade'      => 'Maître de Conférences',
            ],
            [
                'prenom'     => 'Simplice',
                'nom'        => 'GNANCADJA',
                'email'      => 's.gnancadja@lcs.edu',
                'telephone'  => '+229 95 77 88 99',
                'specialite' => 'Sociologie et Développement',
                'grade'      => 'Maître-Assistant',
            ],
            [
                'prenom'     => 'Clarisse',
                'nom'        => 'AHOUANSOU',
                'email'      => 'c.ahouansou@lcs.edu',
                'telephone'  => '+229 96 22 33 44',
                'specialite' => 'Linguistique anglaise et Traduction',
                'grade'      => 'Professeur Titulaire',
            ],
            [
                'prenom'     => 'Modeste',
                'nom'        => 'DJOSSOU',
                'email'      => 'm.djossou@lcs.edu',
                'telephone'  => '+229 97 55 66 77',
                'specialite' => 'Géographie et Aménagement du Territoire',
                'grade'      => 'Maître de Conférences',
            ],
            [
                'prenom'     => 'Lauriane',
                'nom'        => 'KPOSSOU',
                'email'      => 'l.kpossou@lcs.edu',
                'telephone'  => '+229 95 88 99 00',
                'specialite' => 'Communication et Sciences de l\'Information',
                'grade'      => 'Maître-Assistant',
            ],
        ];

        foreach ($teachers as $data) {
            $user = User::create([
                'universite_id' => $university->id,
                'prenom'        => $data['prenom'],
                'nom'           => $data['nom'],
                'email'         => $data['email'],
                'telephone'     => $data['telephone'],
                'password'      => 'Teacher@2024!',
                'role'          => 'teacher',
                'est_actif'     => true,
            ]);

            Teacher::create([
                'universite_id'  => $university->id,
                'utilisateur_id' => $user->id,
                'specialite'     => $data['specialite'],
                'grade'          => $data['grade'],
                'date_embauche'  => now()->subYears(rand(1, 8)),
                'statut'         => 'actif',
            ]);
        }
    }
}
