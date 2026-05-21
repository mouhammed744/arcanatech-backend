<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\University;
use Illuminate\Database\Seeder;

/**
 * ClassroomSeeder
 *
 * Crée les salles de cours pour l'université LCS.
 * Utilise les colonnes françaises de la table salles.
 */
class ClassroomSeeder extends Seeder
{
    public function run(): void
    {
        $university = University::where('code', 'LCS')->first();

        $classrooms = [
            [
                'nom'          => 'Salle 101',
                'batiment'     => 'Bâtiment A',
                'numero_salle' => '101',
                'capacite'     => 30,
                'equipement'   => 'Projecteur, Tableau blanc, WiFi',
                'type'         => 'classroom',
            ],
            [
                'nom'          => 'Salle 102',
                'batiment'     => 'Bâtiment A',
                'numero_salle' => '102',
                'capacite'     => 50,
                'equipement'   => 'Projecteur, Écran, Tableau blanc',
                'type'         => 'classroom',
            ],
            [
                'nom'          => 'Lab 201',
                'batiment'     => 'Bâtiment B',
                'numero_salle' => '201',
                'capacite'     => 20,
                'equipement'   => 'Ordinateurs, Matériel de laboratoire, WiFi',
                'type'         => 'lab',
            ],
            [
                'nom'          => 'Amphithéâtre',
                'batiment'     => 'Bâtiment C',
                'numero_salle' => 'A1',
                'capacite'     => 200,
                'equipement'   => 'Projecteur, Microphone, Système audio',
                'type'         => 'amphitheatre',
            ],
        ];

        foreach ($classrooms as $roomData) {
            Classroom::create([
                'universite_id' => $university->id,
                'est_active'    => true,
                ...$roomData,
            ]);
        }
    }
}
