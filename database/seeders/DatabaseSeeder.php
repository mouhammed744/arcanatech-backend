<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder
 * 
 * JUSTIFICATION :
 * - Seeder principal qui appelle tous les autres seeders
 * - Ordre IMPORTANT : entités sans dépendances d'abord
 * - Ordre : Universities → Users → Teachers/Students → Courses → TimetableEntries → Attendances
 * 
 * UTILISATION :
 * php artisan migrate:fresh --seed
 * 
 * RÉSULTAT :
 * - 1 université (Management_University)
 * - 1 admin, 2 enseignants, 5 étudiants
 * - 3 cours avec 2 sessions chacun
 * - Données de test complètes
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Ordre d'exécution : les dépendances d'abord
        $this->call([
            UniversitySeeder::class,
            UserSeeder::class,
            TeacherSeeder::class,
            StudentSeeder::class,
            CourseSeeder::class,
            ClassroomSeeder::class,
            TimetableEntrySeeder::class,
            CourseEnrollmentSeeder::class,
            // RfidCardSeeder et AttendanceSeeder exclus → simulés via API
        ]);
    }
}
