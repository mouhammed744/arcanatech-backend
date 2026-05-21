<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Student;
use App\Models\University;
use Illuminate\Database\Seeder;

/**
 * CourseEnrollmentSeeder
 *
 * Inscrit tous les étudiants à tous les cours de l'université LCS.
 * Utilise les colonnes françaises de la table inscriptions.
 */
class CourseEnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $university = University::where('code', 'LCS')->first();

        $students = Student::where('universite_id', $university->id)->get();
        $courses  = Course::where('universite_id', $university->id)->get();

        foreach ($students as $student) {
            foreach ($courses as $course) {
                CourseEnrollment::create([
                    'universite_id' => $university->id,
                    'etudiant_id'   => $student->id,
                    'cours_id'      => $course->id,
                    'statut'        => 'inscrit',
                    'inscrit_le'    => now(),
                ]);
            }
        }
    }
}
