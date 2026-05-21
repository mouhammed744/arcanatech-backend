<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Teacher;
use App\Models\University;
use Illuminate\Database\Seeder;

/**
 * CourseSeeder — Cours LCS correspondant aux filières réelles
 *
 * Enseignants (index) :
 *  0 → Rodrigue AGOSSOU      (Droit)
 *  1 → Eugénie HOUÉNOU       (Économie)
 *  2 → Simplice GNANCADJA    (Sociologie)
 *  3 → Clarisse AHOUANSOU    (Anglais)
 *  4 → Modeste DJOSSOU       (Géographie)
 *  5 → Lauriane KPOSSOU      (Communication)
 */
class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $university = University::where('code', 'LCS')->first();
        $teachers   = Teacher::where('universite_id', $university->id)->get();

        $courses = [
            // ── Droit ──────────────────────────────────────────────────
            [
                'code'               => 'SJ101',
                'nom'                => 'Introduction au Droit',
                'description'        => 'Notions fondamentales de droit : sources, règles, institutions.',
                'credits'            => 4,
                'niveau'             => 'L1',
                'semestre'           => 1,
                'minutes_retard_max' => 10,
                'teacher_index'      => 0,
            ],
            [
                'code'               => 'SJ201',
                'nom'                => 'Droit des Obligations',
                'description'        => 'Contrats, responsabilité civile, régimes des obligations.',
                'credits'            => 4,
                'niveau'             => 'L2',
                'semestre'           => 1,
                'minutes_retard_max' => 10,
                'teacher_index'      => 0,
            ],
            [
                'code'               => 'SJ301',
                'nom'                => 'Droit des Affaires',
                'description'        => 'Droit commercial, sociétés, instruments de paiement.',
                'credits'            => 4,
                'niveau'             => 'L3',
                'semestre'           => 1,
                'minutes_retard_max' => 10,
                'teacher_index'      => 0,
            ],

            // ── Économie & Gestion ─────────────────────────────────────
            [
                'code'               => 'SEG101',
                'nom'                => 'Principes d\'Économie',
                'description'        => 'Microéconomie, macroéconomie, théories de base.',
                'credits'            => 4,
                'niveau'             => 'L1',
                'semestre'           => 1,
                'minutes_retard_max' => 10,
                'teacher_index'      => 1,
            ],
            [
                'code'               => 'SEG102',
                'nom'                => 'Comptabilité Générale',
                'description'        => 'Plan comptable OHADA, enregistrements comptables, états financiers.',
                'credits'            => 3,
                'niveau'             => 'L1',
                'semestre'           => 2,
                'minutes_retard_max' => 5,
                'teacher_index'      => 1,
            ],
            [
                'code'               => 'SEG201',
                'nom'                => 'Gestion des Entreprises',
                'description'        => 'Organisation, stratégie, management des ressources.',
                'credits'            => 4,
                'niveau'             => 'L2',
                'semestre'           => 1,
                'minutes_retard_max' => 10,
                'teacher_index'      => 1,
            ],

            // ── Sociologie ─────────────────────────────────────────────
            [
                'code'               => 'SA101',
                'nom'                => 'Introduction à la Sociologie',
                'description'        => 'Concepts fondamentaux, grands courants sociologiques.',
                'credits'            => 3,
                'niveau'             => 'L1',
                'semestre'           => 1,
                'minutes_retard_max' => 10,
                'teacher_index'      => 2,
            ],
            [
                'code'               => 'SA201',
                'nom'                => 'Anthropologie Culturelle',
                'description'        => 'Cultures, identités, rites et représentations sociales.',
                'credits'            => 3,
                'niveau'             => 'L2',
                'semestre'           => 1,
                'minutes_retard_max' => 10,
                'teacher_index'      => 2,
            ],

            // ── Anglais ────────────────────────────────────────────────
            [
                'code'               => 'ANG101',
                'nom'                => 'Anglais Général I',
                'description'        => 'Grammaire, compréhension orale et écrite, niveau débutant-intermédiaire.',
                'credits'            => 3,
                'niveau'             => 'L1',
                'semestre'           => 1,
                'minutes_retard_max' => 5,
                'teacher_index'      => 3,
            ],
            [
                'code'               => 'ANG201',
                'nom'                => 'Anglais des Affaires',
                'description'        => 'Correspondance professionnelle, négociation, terminologie commerciale.',
                'credits'            => 3,
                'niveau'             => 'L2',
                'semestre'           => 1,
                'minutes_retard_max' => 5,
                'teacher_index'      => 3,
            ],

            // ── Géographie ─────────────────────────────────────────────
            [
                'code'               => 'GAT101',
                'nom'                => 'Géographie Physique',
                'description'        => 'Relief, climat, hydrographie et biogéographie de l\'Afrique de l\'Ouest.',
                'credits'            => 3,
                'niveau'             => 'L1',
                'semestre'           => 1,
                'minutes_retard_max' => 10,
                'teacher_index'      => 4,
            ],
            [
                'code'               => 'GAT201',
                'nom'                => 'Aménagement du Territoire',
                'description'        => 'Planification urbaine, développement régional, gestion de l\'espace.',
                'credits'            => 4,
                'niveau'             => 'L2',
                'semestre'           => 1,
                'minutes_retard_max' => 10,
                'teacher_index'      => 4,
            ],

            // ── Communication ──────────────────────────────────────────
            [
                'code'               => 'CJ101',
                'nom'                => 'Théories de la Communication',
                'description'        => 'Modèles communicationnels, médias, opinion publique.',
                'credits'            => 3,
                'niveau'             => 'L1',
                'semestre'           => 1,
                'minutes_retard_max' => 10,
                'teacher_index'      => 5,
            ],
            [
                'code'               => 'CJ102',
                'nom'                => 'Techniques du Journalisme',
                'description'        => 'Écriture journalistique, reportage, déontologie de la presse.',
                'credits'            => 3,
                'niveau'             => 'L1',
                'semestre'           => 2,
                'minutes_retard_max' => 10,
                'teacher_index'      => 5,
            ],
            [
                'code'               => 'CJ201',
                'nom'                => 'Communication Digitale',
                'description'        => 'Réseaux sociaux, stratégie de contenu, community management.',
                'credits'            => 3,
                'niveau'             => 'L2',
                'semestre'           => 1,
                'minutes_retard_max' => 5,
                'teacher_index'      => 5,
            ],
        ];

        foreach ($courses as $courseData) {
            $teacherIndex = $courseData['teacher_index'];
            unset($courseData['teacher_index']);

            Course::create([
                'universite_id' => $university->id,
                'enseignant_id' => $teachers[$teacherIndex]->id,
                ...$courseData,
            ]);
        }
    }
}
