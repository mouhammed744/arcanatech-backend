<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\TimetableEntry;
use App\Models\University;
use Illuminate\Database\Seeder;

/**
 * TimetableEntrySeeder — Emploi du temps LCS (filières réelles)
 */
class TimetableEntrySeeder extends Seeder
{
    public function run(): void
    {
        $university = University::where('code', 'LCS')->first();
        $courses    = Course::where('universite_id', $university->id)->get();
        $classrooms = Classroom::where('universite_id', $university->id)->get();

        $schedules = [
            // Droit
            ['course' => 'SJ101', 'classroom' => 'Salle 101',    'day' => 'lundi',    'start' => '08:00', 'end' => '10:00'],
            ['course' => 'SJ101', 'classroom' => 'Salle 101',    'day' => 'jeudi',    'start' => '08:00', 'end' => '10:00'],
            ['course' => 'SJ201', 'classroom' => 'Salle 101',    'day' => 'lundi',    'start' => '10:00', 'end' => '12:00'],
            ['course' => 'SJ301', 'classroom' => 'Amphithéâtre', 'day' => 'vendredi', 'start' => '08:00', 'end' => '10:00'],

            // Économie & Gestion
            ['course' => 'SEG101', 'classroom' => 'Salle 102',    'day' => 'mardi',    'start' => '08:00', 'end' => '10:00'],
            ['course' => 'SEG101', 'classroom' => 'Salle 102',    'day' => 'vendredi', 'start' => '10:00', 'end' => '12:00'],
            ['course' => 'SEG102', 'classroom' => 'Lab 201',      'day' => 'mercredi', 'start' => '08:00', 'end' => '10:00'],
            ['course' => 'SEG201', 'classroom' => 'Salle 102',    'day' => 'mardi',    'start' => '10:00', 'end' => '12:00'],

            // Sociologie
            ['course' => 'SA101', 'classroom' => 'Salle 101',    'day' => 'mercredi', 'start' => '10:00', 'end' => '12:00'],
            ['course' => 'SA201', 'classroom' => 'Salle 101',    'day' => 'jeudi',    'start' => '10:00', 'end' => '12:00'],

            // Anglais
            ['course' => 'ANG101', 'classroom' => 'Lab 201',      'day' => 'lundi',    'start' => '14:00', 'end' => '16:00'],
            ['course' => 'ANG201', 'classroom' => 'Lab 201',      'day' => 'mercredi', 'start' => '14:00', 'end' => '16:00'],

            // Géographie
            ['course' => 'GAT101', 'classroom' => 'Salle 102',    'day' => 'mardi',    'start' => '14:00', 'end' => '16:00'],
            ['course' => 'GAT201', 'classroom' => 'Amphithéâtre', 'day' => 'jeudi',    'start' => '14:00', 'end' => '16:00'],

            // Communication
            ['course' => 'CJ101', 'classroom' => 'Salle 102',    'day' => 'lundi',    'start' => '16:00', 'end' => '18:00'],
            ['course' => 'CJ102', 'classroom' => 'Lab 201',      'day' => 'vendredi', 'start' => '14:00', 'end' => '16:00'],
            ['course' => 'CJ201', 'classroom' => 'Salle 102',    'day' => 'samedi',   'start' => '08:00', 'end' => '10:00'],
        ];

        foreach ($schedules as $schedule) {
            $course    = $courses->firstWhere('code', $schedule['course']);
            $classroom = $classrooms->firstWhere('nom', $schedule['classroom']);

            if (!$course || !$classroom) continue;

            TimetableEntry::create([
                'universite_id' => $university->id,
                'cours_id'      => $course->id,
                'salle_id'      => $classroom->id,
                'jour_semaine'  => $schedule['day'],
                'heure_debut'   => $schedule['start'],
                'heure_fin'     => $schedule['end'],
                'type_seance'   => 'cours',
                'recurrence'    => 'weekly',
                'date_debut'    => now()->startOfYear(),
                'date_fin'      => now()->endOfYear(),
            ]);
        }
    }
}
