<?php

namespace Database\Seeders;

use App\Models\RfidCard;
use App\Models\Student;
use App\Models\University;
use Illuminate\Database\Seeder;

/**
 * RfidCardSeeder
 *
 * Attribue une carte RFID à chaque étudiant de l'université LCS.
 * Utilise les colonnes françaises de la table cartes_rfid.
 */
class RfidCardSeeder extends Seeder
{
    public function run(): void
    {
        $university = University::where('code', 'LCS')->first();

        $students = Student::where('universite_id', $university->id)->get();

        foreach ($students as $index => $student) {
            // Générer un numéro de carte RFID fictif (format hex)
            $cardNumber = strtoupper(dechex(0xF4A92B1C + $index));

            RfidCard::create([
                'universite_id' => $university->id,
                'etudiant_id'   => $student->id,
                'numero_carte'  => $cardNumber,
                'type_carte'    => 'student',
                'est_active'    => true,
                'assignee_le'   => now()->subMonths(3),
            ]);
        }
    }
}
