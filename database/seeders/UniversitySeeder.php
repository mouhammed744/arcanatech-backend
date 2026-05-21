<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * UniversitySeeder
 *
 * Peuple la table universities avec les 32 universités du Bénin
 * + leurs filières par défaut. Utilise upsert par code.
 */
class UniversitySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $universities = [
            [
                'name' => "Université d'Abomey-Calavi (UAC)",
                'code' => 'UAC',
                'city' => 'Abomey-Calavi',
                'primary_color' => '#1B3A6B',
                'secondary_color' => '#FFFFFF',
                'accent_preset' => 'indigo',
                'student_id_digits' => 8,
                'student_id_prefix' => 'UAC',
                'filieres' => [
                    ['name' => 'Droit et Sciences Politiques', 'code' => 'FDSP', 'level' => 'L1', 'department' => 'Droit'],
                    ['name' => 'Sciences Économiques et de Gestion', 'code' => 'FASEG', 'level' => 'L1', 'department' => 'Économie'],
                    ['name' => 'Lettres, Arts et Sciences Humaines', 'code' => 'FLASH', 'level' => 'L1', 'department' => 'Lettres'],
                    ['name' => 'Sciences et Techniques', 'code' => 'FAST', 'level' => 'L1', 'department' => 'Sciences'],
                    ['name' => 'Sciences Agronomiques', 'code' => 'FSA', 'level' => 'L1', 'department' => 'Agronomie'],
                    ['name' => 'Sciences de la Santé', 'code' => 'FSS', 'level' => 'L1', 'department' => 'Santé'],
                    ['name' => 'Génie Civil', 'code' => 'EPAC-GC', 'level' => 'L1', 'department' => 'Polytechnique'],
                    ['name' => 'Génie Informatique', 'code' => 'EPAC-GI', 'level' => 'L1', 'department' => 'Polytechnique'],
                    ['name' => 'Génie Électrique', 'code' => 'EPAC-GE', 'level' => 'L1', 'department' => 'Polytechnique'],
                    ['name' => 'Génie Mécanique', 'code' => 'EPAC-GM', 'level' => 'L1', 'department' => 'Polytechnique'],
                    ['name' => 'Informatique', 'code' => 'IFRI', 'level' => 'L1', 'department' => 'Informatique'],
                    ['name' => 'Administration et Magistrature', 'code' => 'ENAM', 'level' => 'L1', 'department' => 'Administration'],
                    ['name' => 'Économie Appliquée et Management', 'code' => 'ENEAM', 'level' => 'L1', 'department' => 'Management'],
                ],
            ],
            [
                'name' => 'Université de Parakou (UP)',
                'code' => 'UP',
                'city' => 'Parakou',
                'primary_color' => '#1A6B3A',
                'secondary_color' => '#2563EB',
                'accent_preset' => 'emerald',
                'student_id_digits' => 8,
                'student_id_prefix' => 'UP',
                'filieres' => [
                    ['name' => 'Agronomie', 'code' => 'FA', 'level' => 'L1', 'department' => 'Agronomie'],
                    ['name' => 'Médecine', 'code' => 'FM', 'level' => 'L1', 'department' => 'Santé'],
                    ['name' => 'Lettres, Arts et Sciences Humaines', 'code' => 'FLASH', 'level' => 'L1', 'department' => 'Lettres'],
                    ['name' => 'Sciences Économiques et de Gestion', 'code' => 'FASEG', 'level' => 'L1', 'department' => 'Économie'],
                    ['name' => 'Droit et Sciences Politiques', 'code' => 'FDSP', 'level' => 'L1', 'department' => 'Droit'],
                    ['name' => 'Sciences et Techniques', 'code' => 'FAST', 'level' => 'L1', 'department' => 'Sciences'],
                    ['name' => 'Technologie (IUT)', 'code' => 'IUT', 'level' => 'L1', 'department' => 'Technologie'],
                ],
            ],
            [
                'name' => 'Université Nationale des Sciences, Technologies, Ingénierie et Mathématiques (UNSTIM)',
                'code' => 'UNSTIM',
                'city' => 'Abomey',
                'primary_color' => '#E65100',
                'secondary_color' => '#1565C0',
                'accent_preset' => 'amber',
                'student_id_digits' => 8,
                'student_id_prefix' => 'UST',
                'filieres' => [
                    ['name' => 'Génie Civil et Travaux Publics', 'code' => 'ENSTP', 'level' => 'L1', 'department' => 'Génie Civil'],
                    ['name' => 'Génie Mathématique et Modélisation', 'code' => 'ENSGMM', 'level' => 'L1', 'department' => 'Mathématiques'],
                    ['name' => 'Génie Énergétique et Procédés', 'code' => 'ENSGEP', 'level' => 'L1', 'department' => 'Énergie'],
                    ['name' => 'Topographie et Cadastre', 'code' => 'TOPO', 'level' => 'L1', 'department' => 'Génie Civil'],
                    ['name' => 'Architecture et Urbanisme', 'code' => 'ARCHI', 'level' => 'L1', 'department' => 'Architecture'],
                ],
            ],
            [
                'name' => "Université Nationale d'Agriculture (UNA)",
                'code' => 'UNA',
                'city' => 'Kétou',
                'primary_color' => '#2E7D32',
                'secondary_color' => '#FDD835',
                'accent_preset' => 'emerald',
                'student_id_digits' => 8,
                'student_id_prefix' => 'UNA',
                'filieres' => [
                    ['name' => 'Productions Végétales', 'code' => 'PV', 'level' => 'L1', 'department' => 'Agriculture'],
                    ['name' => 'Productions Animales', 'code' => 'PA', 'level' => 'L1', 'department' => 'Élevage'],
                    ['name' => 'Économie et Sociologie Rurales', 'code' => 'ESR', 'level' => 'L1', 'department' => 'Économie'],
                    ['name' => 'Aménagement et Gestion des Ressources Naturelles', 'code' => 'AGRN', 'level' => 'L1', 'department' => 'Environnement'],
                ],
            ],
            [
                'name' => 'Institut National Médico-Sanitaire (INMeS)',
                'code' => 'INMeS',
                'city' => 'Cotonou',
                'primary_color' => '#C62828',
                'secondary_color' => '#FFFFFF',
                'accent_preset' => 'rose',
                'student_id_digits' => 8,
                'student_id_prefix' => 'INM',
                'filieres' => [
                    ['name' => 'Soins Infirmiers', 'code' => 'SI', 'level' => 'L1', 'department' => 'Santé'],
                    ['name' => 'Sage-Femme', 'code' => 'SF', 'level' => 'L1', 'department' => 'Santé'],
                    ['name' => 'Kinésithérapie', 'code' => 'KINE', 'level' => 'L1', 'department' => 'Santé'],
                    ['name' => 'Imagerie Médicale', 'code' => 'IM', 'level' => 'L1', 'department' => 'Santé'],
                ],
            ],
            [
                'name' => 'École Normale Supérieure (ENS) de Natitingou',
                'code' => 'ENS-N',
                'city' => 'Natitingou',
                'primary_color' => '#1565C0',
                'secondary_color' => '#E8EAF6',
                'accent_preset' => 'blue',
                'student_id_digits' => 8,
                'student_id_prefix' => 'ENS',
                'filieres' => [
                    ['name' => 'Sciences de l\'Éducation', 'code' => 'SE', 'level' => 'L1', 'department' => 'Éducation'],
                    ['name' => 'Formation des Enseignants', 'code' => 'FE', 'level' => 'L1', 'department' => 'Éducation'],
                    ['name' => 'Mathématiques', 'code' => 'MATH', 'level' => 'L1', 'department' => 'Sciences'],
                    ['name' => 'Physique-Chimie', 'code' => 'PC', 'level' => 'L1', 'department' => 'Sciences'],
                ],
            ],
            [
                'name' => 'Université Protestante Sandra Brindley',
                'code' => 'UPSB',
                'city' => 'Porto-Novo',
                'primary_color' => '#4A148C',
                'secondary_color' => '#FFFFFF',
                'accent_preset' => 'indigo',
                'student_id_digits' => 8,
                'student_id_prefix' => 'UPS',
                'filieres' => [
                    ['name' => 'Théologie', 'code' => 'THEO', 'level' => 'L1', 'department' => 'Théologie'],
                    ['name' => 'Sciences Sociales', 'code' => 'SS', 'level' => 'L1', 'department' => 'Sciences Sociales'],
                ],
            ],
            [
                'name' => "Université Catholique de l'Afrique de l'Ouest (UCAO-UUC)",
                'code' => 'UCAO',
                'city' => 'Cotonou',
                'primary_color' => '#1565C0',
                'secondary_color' => '#FFD600',
                'accent_preset' => 'blue',
                'student_id_digits' => 8,
                'student_id_prefix' => 'UCA',
                'filieres' => [
                    ['name' => 'Agronomie et Environnement', 'code' => 'AGR', 'level' => 'L1', 'department' => 'Agronomie'],
                    ['name' => 'Génie Électrique et Informatique', 'code' => 'GEI', 'level' => 'L1', 'department' => 'Ingénierie'],
                    ['name' => 'Droit', 'code' => 'DR', 'level' => 'L1', 'department' => 'Droit'],
                    ['name' => 'Économie et Gestion', 'code' => 'ECO', 'level' => 'L1', 'department' => 'Économie'],
                ],
            ],
            [
                'name' => 'Institut Universitaire du Bénin (IUB)',
                'code' => 'IUB',
                'city' => 'Cotonou',
                'primary_color' => '#00838F',
                'secondary_color' => '#FFFFFF',
                'accent_preset' => 'teal',
                'student_id_digits' => 8,
                'student_id_prefix' => 'IUB',
                'filieres' => [
                    ['name' => 'Informatique de Gestion', 'code' => 'IG', 'level' => 'L1', 'department' => 'Informatique'],
                    ['name' => 'Comptabilité et Gestion', 'code' => 'CG', 'level' => 'L1', 'department' => 'Gestion'],
                    ['name' => 'Marketing et Commerce', 'code' => 'MC', 'level' => 'L1', 'department' => 'Commerce'],
                ],
            ],
            [
                'name' => 'Université Africaine de Technologie et de Management (UATM/GASA Formation)',
                'code' => 'UATM',
                'city' => 'Cotonou',
                'primary_color' => '#E65100',
                'secondary_color' => '#1565C0',
                'accent_preset' => 'amber',
                'student_id_digits' => 8,
                'student_id_prefix' => 'UAT',
                'filieres' => [
                    ['name' => 'Réseaux Informatique et Télécommunication', 'code' => 'RIT', 'level' => 'L1', 'department' => 'Informatique'],
                    ['name' => 'Finance Comptabilité et Audit', 'code' => 'FCA', 'level' => 'L1', 'department' => 'Finance'],
                    ['name' => 'Transport et Logistique', 'code' => 'TL', 'level' => 'L1', 'department' => 'Logistique'],
                    ['name' => 'Management des Ressources Humaines', 'code' => 'MRH', 'level' => 'L1', 'department' => 'Management'],
                    ['name' => 'Système Informatique et Logiciel', 'code' => 'SIL', 'level' => 'L1', 'department' => 'Informatique'],
                    ['name' => 'Communication et Relations Internationales', 'code' => 'CRI', 'level' => 'L1', 'department' => 'Communication'],
                    ['name' => 'Banque Finance Assurance', 'code' => 'BFA', 'level' => 'L1', 'department' => 'Finance'],
                ],
            ],
            [
                'name' => 'Haute École de Commerce et de Management (HECM)',
                'code' => 'HECM',
                'city' => 'Cotonou',
                'primary_color' => '#1565C0',
                'secondary_color' => '#FFD600',
                'accent_preset' => 'blue',
                'student_id_digits' => 8,
                'student_id_prefix' => 'HEC',
                'filieres' => [
                    ['name' => 'Finance Comptabilité et Audit', 'code' => 'FCA', 'level' => 'L1', 'department' => 'Finance'],
                    ['name' => 'Banque Finance et Assurance', 'code' => 'BFA', 'level' => 'L1', 'department' => 'Finance'],
                    ['name' => 'Marketing Communication et Commerce', 'code' => 'MCC', 'level' => 'L1', 'department' => 'Marketing'],
                    ['name' => 'Gestion des Ressources Humaines', 'code' => 'GRH', 'level' => 'L1', 'department' => 'Management'],
                    ['name' => 'Transport et Logistique', 'code' => 'TL', 'level' => 'L1', 'department' => 'Logistique'],
                    ['name' => 'Génie Informatique', 'code' => 'GI', 'level' => 'L1', 'department' => 'Informatique'],
                    ['name' => 'Analyses Biomédicales', 'code' => 'ABM', 'level' => 'L1', 'department' => 'Santé'],
                    ['name' => 'Tourisme et Hôtellerie', 'code' => 'TH', 'level' => 'L1', 'department' => 'Tourisme'],
                    ['name' => 'Sciences Juridiques et Politiques', 'code' => 'SJP', 'level' => 'L1', 'department' => 'Droit'],
                ],
            ],
            [
                'name' => "École Supérieure de Gestion d'Informatique et des Sciences (ESGIS)",
                'code' => 'ESGIS',
                'city' => 'Cotonou',
                'primary_color' => '#1565C0',
                'secondary_color' => '#FF6D00',
                'accent_preset' => 'blue',
                'student_id_digits' => 8,
                'student_id_prefix' => 'ESG',
                'filieres' => [
                    ['name' => 'Informatique de Gestion', 'code' => 'IG', 'level' => 'BTS', 'department' => 'Informatique'],
                    ['name' => 'Comptabilité et Gestion', 'code' => 'CG', 'level' => 'BTS', 'department' => 'Gestion'],
                    ['name' => 'Commerce International', 'code' => 'CI', 'level' => 'BTS', 'department' => 'Commerce'],
                    ['name' => 'Marketing et Action Commerciale', 'code' => 'MAC', 'level' => 'BTS', 'department' => 'Marketing'],
                    ['name' => 'Finance Banque', 'code' => 'FB', 'level' => 'BTS', 'department' => 'Finance'],
                    ['name' => 'Réseaux et Télécommunication', 'code' => 'RT', 'level' => 'L1', 'department' => 'Informatique'],
                    ['name' => 'Management International', 'code' => 'MI', 'level' => 'L1', 'department' => 'Management'],
                ],
            ],
            [
                'name' => 'Institut CERCO',
                'code' => 'CERCO',
                'city' => 'Cotonou',
                'primary_color' => '#B71C1C',
                'secondary_color' => '#1565C0',
                'accent_preset' => 'rose',
                'student_id_digits' => 8,
                'student_id_prefix' => 'CRC',
                'filieres' => [
                    ['name' => 'Informatique et Réseaux', 'code' => 'IR', 'level' => 'L1', 'department' => 'Informatique'],
                    ['name' => 'Comptabilité et Gestion', 'code' => 'CG', 'level' => 'BTS', 'department' => 'Gestion'],
                    ['name' => 'Génie Civil', 'code' => 'GC', 'level' => 'L1', 'department' => 'Génie Civil'],
                    ['name' => 'Communication', 'code' => 'COM', 'level' => 'L1', 'department' => 'Communication'],
                    ['name' => 'Logistique et Transport', 'code' => 'LT', 'level' => 'L1', 'department' => 'Logistique'],
                ],
            ],
            [
                'name' => 'Université Pigier Bénin',
                'code' => 'PIGIER',
                'city' => 'Cotonou',
                'primary_color' => '#D32F2F',
                'secondary_color' => '#FFFFFF',
                'accent_preset' => 'rose',
                'student_id_digits' => 8,
                'student_id_prefix' => 'PIG',
                'filieres' => [
                    ['name' => 'Audit et Contrôle de Gestion', 'code' => 'ACG', 'level' => 'L1', 'department' => 'Finance'],
                    ['name' => 'Banque et Finance', 'code' => 'BF', 'level' => 'L1', 'department' => 'Finance'],
                    ['name' => 'Communication Digitale et Webmarketing', 'code' => 'CDW', 'level' => 'L1', 'department' => 'Communication'],
                    ['name' => 'Commerce International et E-Business', 'code' => 'CIE', 'level' => 'L1', 'department' => 'Commerce'],
                    ['name' => 'Management des Transports et Logistique', 'code' => 'MTL', 'level' => 'L1', 'department' => 'Logistique'],
                ],
            ],
            [
                'name' => 'Institut Supérieur de Management Adonaï (ISM Adonaï)',
                'code' => 'ISMA',
                'city' => 'Cotonou',
                'primary_color' => '#1B5E20',
                'secondary_color' => '#FFFFFF',
                'accent_preset' => 'emerald',
                'student_id_digits' => 8,
                'student_id_prefix' => 'ISM',
                'filieres' => [
                    ['name' => 'Management et Gestion', 'code' => 'MG', 'level' => 'L1', 'department' => 'Management'],
                    ['name' => 'Comptabilité', 'code' => 'CPT', 'level' => 'L1', 'department' => 'Finance'],
                ],
            ],
            [
                'name' => 'Centre de Formation Professionnelle Promo-Sèmè (CFPPS)',
                'code' => 'CFPPS',
                'city' => 'Cotonou',
                'primary_color' => '#0277BD',
                'secondary_color' => '#FFFFFF',
                'accent_preset' => 'sky',
                'student_id_digits' => 8,
                'student_id_prefix' => 'CFP',
                'filieres' => [
                    ['name' => 'Formation Professionnelle', 'code' => 'FP', 'level' => 'L1', 'department' => 'Formation'],
                ],
            ],
            [
                'name' => 'École Supérieure de Génie Civil Verechaguine A.K.',
                'code' => 'ESGC',
                'city' => 'Cotonou',
                'primary_color' => '#E65100',
                'secondary_color' => '#37474F',
                'accent_preset' => 'amber',
                'student_id_digits' => 8,
                'student_id_prefix' => 'VER',
                'filieres' => [
                    ['name' => 'Génie Civil', 'code' => 'GC', 'level' => 'L1', 'department' => 'Génie Civil'],
                    ['name' => 'Architecture', 'code' => 'ARCH', 'level' => 'L1', 'department' => 'Architecture'],
                    ['name' => 'Topographie', 'code' => 'TOPO', 'level' => 'L1', 'department' => 'Génie Civil'],
                ],
            ],
            [
                'name' => 'Houdegbe North American University Benin (HNAUB)',
                'code' => 'HNAUB',
                'city' => 'Cotonou',
                'primary_color' => '#1A237E',
                'secondary_color' => '#C62828',
                'accent_preset' => 'indigo',
                'student_id_digits' => 8,
                'student_id_prefix' => 'HNA',
                'filieres' => [
                    ['name' => 'Business Administration', 'code' => 'BA', 'level' => 'L1', 'department' => 'Business'],
                    ['name' => 'Computer Science', 'code' => 'CS', 'level' => 'L1', 'department' => 'Informatique'],
                    ['name' => 'International Relations', 'code' => 'IR', 'level' => 'L1', 'department' => 'Relations Internationales'],
                ],
            ],
            [
                'name' => 'Institut IRGIB Africa',
                'code' => 'IRGIB',
                'city' => 'Cotonou',
                'primary_color' => '#00695C',
                'secondary_color' => '#FFD600',
                'accent_preset' => 'teal',
                'student_id_digits' => 8,
                'student_id_prefix' => 'IRG',
                'filieres' => [
                    ['name' => 'Sciences de la Santé', 'code' => 'SS', 'level' => 'L1', 'department' => 'Santé'],
                    ['name' => 'Sciences et Technologies', 'code' => 'ST', 'level' => 'L1', 'department' => 'Sciences'],
                    ['name' => 'Management', 'code' => 'MGT', 'level' => 'L1', 'department' => 'Management'],
                ],
            ],
            [
                'name' => 'Université des Sciences et Technologies du Bénin (USTB)',
                'code' => 'USTB',
                'city' => 'Cotonou',
                'primary_color' => '#1565C0',
                'secondary_color' => '#FF6D00',
                'accent_preset' => 'blue',
                'student_id_digits' => 8,
                'student_id_prefix' => 'USB',
                'filieres' => [
                    ['name' => 'Sciences et Technologies', 'code' => 'ST', 'level' => 'L1', 'department' => 'Sciences'],
                    ['name' => 'Informatique', 'code' => 'INFO', 'level' => 'L1', 'department' => 'Informatique'],
                ],
            ],
            [
                'name' => 'Institut Universitaire de Technologie (IUT) de Lokossa',
                'code' => 'IUT-L',
                'city' => 'Lokossa',
                'primary_color' => '#1B3A6B',
                'secondary_color' => '#E65100',
                'accent_preset' => 'blue',
                'student_id_digits' => 8,
                'student_id_prefix' => 'IUT',
                'filieres' => [
                    ['name' => 'Génie Industriel et Maintenance', 'code' => 'GIM', 'level' => 'L1', 'department' => 'Industrie'],
                    ['name' => 'Génie Informatique', 'code' => 'GI', 'level' => 'L1', 'department' => 'Informatique'],
                    ['name' => 'Génie Électrique', 'code' => 'GE', 'level' => 'L1', 'department' => 'Électrique'],
                ],
            ],
            [
                'name' => "École Polytechnique d'Abomey-Calavi (EPAC)",
                'code' => 'EPAC',
                'city' => 'Abomey-Calavi',
                'primary_color' => '#1B3A6B',
                'secondary_color' => '#FFD600',
                'accent_preset' => 'blue',
                'student_id_digits' => 8,
                'student_id_prefix' => 'EPA',
                'filieres' => [
                    ['name' => 'Génie Civil', 'code' => 'GC', 'level' => 'L1', 'department' => 'Génie Civil'],
                    ['name' => 'Génie Informatique et Télécommunication', 'code' => 'GIT', 'level' => 'L1', 'department' => 'Informatique'],
                    ['name' => 'Génie Électrique', 'code' => 'GE', 'level' => 'L1', 'department' => 'Électrique'],
                    ['name' => 'Génie Mécanique et Énergétique', 'code' => 'GME', 'level' => 'L1', 'department' => 'Mécanique'],
                    ['name' => 'Génie Bio-Médical', 'code' => 'GBM', 'level' => 'L1', 'department' => 'Santé'],
                ],
            ],
            [
                'name' => 'Faculté des Sciences de la Santé (FSS)',
                'code' => 'FSS',
                'city' => 'Cotonou',
                'primary_color' => '#C62828',
                'secondary_color' => '#FFFFFF',
                'accent_preset' => 'rose',
                'student_id_digits' => 8,
                'student_id_prefix' => 'FSS',
                'filieres' => [
                    ['name' => 'Médecine Générale', 'code' => 'MG', 'level' => 'L1', 'department' => 'Médecine'],
                    ['name' => 'Pharmacie', 'code' => 'PH', 'level' => 'L1', 'department' => 'Pharmacie'],
                    ['name' => 'Chirurgie Dentaire', 'code' => 'CD', 'level' => 'L1', 'department' => 'Dentaire'],
                ],
            ],
            [
                'name' => "Institut de Formation et de Recherche en Informatique (IFRI)",
                'code' => 'IFRI',
                'city' => 'Abomey-Calavi',
                'primary_color' => '#1565C0',
                'secondary_color' => '#00C853',
                'accent_preset' => 'blue',
                'student_id_digits' => 8,
                'student_id_prefix' => 'IFR',
                'filieres' => [
                    ['name' => 'Génie Logiciel', 'code' => 'GL', 'level' => 'L1', 'department' => 'Informatique'],
                    ['name' => 'Réseaux et Sécurité Informatique', 'code' => 'RSI', 'level' => 'L1', 'department' => 'Informatique'],
                    ['name' => 'Systèmes d\'Information', 'code' => 'SI', 'level' => 'L1', 'department' => 'Informatique'],
                    ['name' => 'Intelligence Artificielle', 'code' => 'IA', 'level' => 'M1', 'department' => 'Informatique'],
                ],
            ],
            [
                'name' => "École Nationale d'Administration et de Magistrature (ENAM)",
                'code' => 'ENAM',
                'city' => 'Abomey-Calavi',
                'primary_color' => '#1B3A6B',
                'secondary_color' => '#B71C1C',
                'accent_preset' => 'indigo',
                'student_id_digits' => 8,
                'student_id_prefix' => 'ENA',
                'filieres' => [
                    ['name' => 'Administration Générale', 'code' => 'AG', 'level' => 'L1', 'department' => 'Administration'],
                    ['name' => 'Magistrature', 'code' => 'MAG', 'level' => 'M1', 'department' => 'Droit'],
                    ['name' => 'Diplomatie', 'code' => 'DIP', 'level' => 'L1', 'department' => 'Relations Internationales'],
                ],
            ],
            [
                'name' => "École Nationale d'Économie Appliquée et de Management (ENEAM)",
                'code' => 'ENEAM',
                'city' => 'Cotonou',
                'primary_color' => '#1B3A6B',
                'secondary_color' => '#FFD600',
                'accent_preset' => 'blue',
                'student_id_digits' => 8,
                'student_id_prefix' => 'ENE',
                'filieres' => [
                    ['name' => 'Statistique et Planification', 'code' => 'SP', 'level' => 'L1', 'department' => 'Statistique'],
                    ['name' => 'Économie Appliquée', 'code' => 'EA', 'level' => 'L1', 'department' => 'Économie'],
                    ['name' => 'Management des Organisations', 'code' => 'MO', 'level' => 'L1', 'department' => 'Management'],
                    ['name' => 'Finance et Comptabilité', 'code' => 'FC', 'level' => 'L1', 'department' => 'Finance'],
                ],
            ],
            [
                'name' => "Sup'Management Bénin",
                'code' => 'SUPMA',
                'city' => 'Cotonou',
                'primary_color' => '#4A148C',
                'secondary_color' => '#FFD600',
                'accent_preset' => 'indigo',
                'student_id_digits' => 8,
                'student_id_prefix' => 'SUP',
                'filieres' => [
                    ['name' => 'Management et Stratégie', 'code' => 'MS', 'level' => 'L1', 'department' => 'Management'],
                    ['name' => 'Marketing Digital', 'code' => 'MD', 'level' => 'L1', 'department' => 'Marketing'],
                ],
            ],
            [
                'name' => 'Institut de Mathématiques et de Sciences Physiques (IMSP)',
                'code' => 'IMSP',
                'city' => 'Porto-Novo',
                'primary_color' => '#1565C0',
                'secondary_color' => '#FFFFFF',
                'accent_preset' => 'blue',
                'student_id_digits' => 8,
                'student_id_prefix' => 'IMS',
                'filieres' => [
                    ['name' => 'Mathématiques Fondamentales', 'code' => 'MF', 'level' => 'M1', 'department' => 'Mathématiques'],
                    ['name' => 'Physique Théorique', 'code' => 'PT', 'level' => 'M1', 'department' => 'Physique'],
                    ['name' => 'Mathématiques Appliquées', 'code' => 'MA', 'level' => 'M1', 'department' => 'Mathématiques'],
                ],
            ],
            [
                'name' => 'Université de Lokossa',
                'code' => 'UL',
                'city' => 'Lokossa',
                'primary_color' => '#2E7D32',
                'secondary_color' => '#1565C0',
                'accent_preset' => 'emerald',
                'student_id_digits' => 8,
                'student_id_prefix' => 'ULK',
                'filieres' => [
                    ['name' => 'Sciences Agronomiques', 'code' => 'SA', 'level' => 'L1', 'department' => 'Agronomie'],
                    ['name' => 'Technologie', 'code' => 'TECH', 'level' => 'L1', 'department' => 'Technologie'],
                ],
            ],
            [
                'name' => 'Centre Béninois de la Recherche Scientifique et Technique (CBRST)',
                'code' => 'CBRST',
                'city' => 'Cotonou',
                'primary_color' => '#0277BD',
                'secondary_color' => '#FFFFFF',
                'accent_preset' => 'sky',
                'student_id_digits' => 8,
                'student_id_prefix' => 'CBR',
                'filieres' => [
                    ['name' => 'Recherche Scientifique', 'code' => 'RS', 'level' => 'M1', 'department' => 'Recherche'],
                ],
            ],
            [
                'name' => 'Institut Supérieur de Technologie Industrielle (ISTI)',
                'code' => 'ISTI',
                'city' => 'Cotonou',
                'primary_color' => '#E65100',
                'secondary_color' => '#37474F',
                'accent_preset' => 'amber',
                'student_id_digits' => 8,
                'student_id_prefix' => 'IST',
                'filieres' => [
                    ['name' => 'Électronique et Automatisme', 'code' => 'EA', 'level' => 'L1', 'department' => 'Industrie'],
                    ['name' => 'Maintenance Industrielle', 'code' => 'MI', 'level' => 'L1', 'department' => 'Industrie'],
                    ['name' => 'Informatique Industrielle', 'code' => 'II', 'level' => 'L1', 'department' => 'Informatique'],
                ],
            ],
            [
                'name' => 'École Supérieure des Techniques Biologiques et Alimentaires (ESTBA)',
                'code' => 'ESTBA',
                'city' => 'Cotonou',
                'primary_color' => '#2E7D32',
                'secondary_color' => '#FFFFFF',
                'accent_preset' => 'emerald',
                'student_id_digits' => 8,
                'student_id_prefix' => 'EST',
                'filieres' => [
                    ['name' => 'Biotechnologie Alimentaire', 'code' => 'BA', 'level' => 'L1', 'department' => 'Biotechnologie'],
                    ['name' => 'Contrôle Qualité', 'code' => 'CQ', 'level' => 'L1', 'department' => 'Qualité'],
                    ['name' => 'Nutrition et Diététique', 'code' => 'ND', 'level' => 'L1', 'department' => 'Nutrition'],
                ],
            ],
            [
                'name' => 'Les Cours Sonou University',
                'code' => 'LCS',
                'city' => 'Cotonou',
                'primary_color' => '#0D47A1',
                'secondary_color' => '#FFC107',
                'accent_preset' => 'blue',
                'student_id_digits' => 8,
                'student_id_prefix' => 'SON',
                'filieres' => [
                    // Sciences Juridiques — 3 niveaux
                    ['name' => 'Sciences Juridiques - Licence 1',  'code' => 'SJ-L1', 'level' => 'L1', 'department' => 'Droit'],
                    ['name' => 'Sciences Juridiques - Licence 2',  'code' => 'SJ-L2', 'level' => 'L2', 'department' => 'Droit'],
                    ['name' => 'Sciences Juridiques - Licence 3',  'code' => 'SJ-L3', 'level' => 'L3', 'department' => 'Droit'],
                    // Sciences Économiques et de Gestion — 3 niveaux
                    ['name' => 'Sciences Économiques et de Gestion - Licence 1', 'code' => 'SEG-L1', 'level' => 'L1', 'department' => 'Économie'],
                    ['name' => 'Sciences Économiques et de Gestion - Licence 2', 'code' => 'SEG-L2', 'level' => 'L2', 'department' => 'Économie'],
                    ['name' => 'Sciences Économiques et de Gestion - Licence 3', 'code' => 'SEG-L3', 'level' => 'L3', 'department' => 'Économie'],
                    // Sociologie et Anthropologie — 3 niveaux
                    ['name' => 'Sociologie et Anthropologie - Licence 1', 'code' => 'SA-L1', 'level' => 'L1', 'department' => 'Sciences Humaines'],
                    ['name' => 'Sociologie et Anthropologie - Licence 2', 'code' => 'SA-L2', 'level' => 'L2', 'department' => 'Sciences Humaines'],
                    ['name' => 'Sociologie et Anthropologie - Licence 3', 'code' => 'SA-L3', 'level' => 'L3', 'department' => 'Sciences Humaines'],
                    // Anglais — 3 niveaux
                    ['name' => 'Anglais - Licence 1', 'code' => 'ANG-L1', 'level' => 'L1', 'department' => 'Langues'],
                    ['name' => 'Anglais - Licence 2', 'code' => 'ANG-L2', 'level' => 'L2', 'department' => 'Langues'],
                    ['name' => 'Anglais - Licence 3', 'code' => 'ANG-L3', 'level' => 'L3', 'department' => 'Langues'],
                    // Géographie et Aménagement du Territoire — 3 niveaux
                    ['name' => 'Géographie et Aménagement du Territoire - Licence 1', 'code' => 'GAT-L1', 'level' => 'L1', 'department' => 'Géographie'],
                    ['name' => 'Géographie et Aménagement du Territoire - Licence 2', 'code' => 'GAT-L2', 'level' => 'L2', 'department' => 'Géographie'],
                    ['name' => 'Géographie et Aménagement du Territoire - Licence 3', 'code' => 'GAT-L3', 'level' => 'L3', 'department' => 'Géographie'],
                    // Communication et Journalisme — 3 niveaux
                    ['name' => 'Communication et Journalisme - Licence 1', 'code' => 'CJ-L1', 'level' => 'L1', 'department' => 'Communication'],
                    ['name' => 'Communication et Journalisme - Licence 2', 'code' => 'CJ-L2', 'level' => 'L2', 'department' => 'Communication'],
                    ['name' => 'Communication et Journalisme - Licence 3', 'code' => 'CJ-L3', 'level' => 'L3', 'department' => 'Communication'],
                ],
            ],
        ];

        foreach ($universities as $uniData) {
            $filieres = $uniData['filieres'];
            unset($uniData['filieres']);

            // Mapper les colonnes anglaises → colonnes françaises (table universites)
            $row = [
                'nom'                  => $uniData['name'],
                'code'                 => $uniData['code'],
                'ville'                => $uniData['city'],
                'couleur_principale'   => $uniData['primary_color'],
                'couleur_secondaire'   => $uniData['secondary_color'],
                'preset_accent'        => $uniData['accent_preset'],
                'nb_chiffres_matricule'=> $uniData['student_id_digits'],
                'prefixe_matricule'    => $uniData['student_id_prefix'],
                'fuseau_horaire'       => 'Africa/Porto-Novo',
            ];

            // Upsert dans la table universites (schéma français utilisé par les modèles)
            $university = DB::table('universites')
                ->where('code', $row['code'])
                ->first();

            if ($university) {
                DB::table('universites')
                    ->where('id', $university->id)
                    ->update(array_merge($row, ['updated_at' => $now]));
                $universityId = $university->id;
            } else {
                $universityId = DB::table('universites')->insertGetId(
                    array_merge($row, [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                );
            }

            // Seed filieres (skip si code déjà présent pour cette université)
            // Colonnes selon le modèle Filiere : universite_id, nom, niveau, departement, est_active
            foreach ($filieres as $filiere) {
                $exists = DB::table('filieres')
                    ->where('universite_id', $universityId)
                    ->where('code', $filiere['code'])
                    ->exists();

                if (!$exists) {
                    DB::table('filieres')->insert([
                        'universite_id' => $universityId,
                        'nom'           => $filiere['name'],
                        'code'          => $filiere['code'],
                        'niveau'        => $filiere['level'],
                        'departement'   => $filiere['department'],
                        'est_active'    => true,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
            }
        }
    }
}
