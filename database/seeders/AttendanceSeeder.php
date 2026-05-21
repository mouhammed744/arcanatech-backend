<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\TimetableEntry;
use App\Models\University;
use Illuminate\Database\Seeder;

/**
 * AttendanceSeeder
 *
 * Crée des enregistrements de présence pour chaque séance et étudiant.
 * Utilise les colonnes françaises de la table presences.
 */
class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $university = University::where('code', 'LCS')->first();

        $seances  = TimetableEntry::where('universite_id', $university->id)->get();
        $students = Student::where('universite_id', $university->id)->get();

        // Rotation des statuts pour avoir des données variées
        $statuts = ['present', 'present', 'present', 'late', 'absent'];

        foreach ($seances as $seance) {
            $heureDebut = (int) substr($seance->heure_debut, 0, 2);

            foreach ($students as $index => $student) {
                $statut    = $statuts[$index % count($statuts)];
                $scannedAt = now()
                    ->subDays(rand(1, 30))
                    ->setHour($heureDebut)
                    ->setMinute(rand(0, 10))
                    ->setSecond(0);

                Attendance::create([
                    'universite_id'        => $university->id,
                    'seance_id'            => $seance->id,
                    'etudiant_id'          => $student->id,
                    'statut'               => $statut,
                    'scanne_le'            => $scannedAt,
                    'methode_verification' => 'rfid',
                ]);
            }
        }
    }
}
