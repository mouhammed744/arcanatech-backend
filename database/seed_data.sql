-- ============================================================
-- ARCANATECH - SCRIPT DE DONNÉES DE TEST
-- Projet : Gestion des retards universitaires + RFID
-- Auteurs : ALOUGBIN Mouhamed Idjilal & CHIDJOU Abdoul Sorbour
-- ============================================================

-- Désactiver les contraintes FK temporairement pour l'insertion
SET session_replication_role = replica;

-- ============================================================
-- NETTOYAGE (ordre inverse des dépendances)
-- ============================================================
TRUNCATE TABLE access_logs          CASCADE;
TRUNCATE TABLE rfid_cards           CASCADE;
TRUNCATE TABLE attendances          CASCADE;
TRUNCATE TABLE course_enrollments   CASCADE;
TRUNCATE TABLE timetable_entries    CASCADE;
TRUNCATE TABLE courses              CASCADE;
TRUNCATE TABLE classrooms           CASCADE;
TRUNCATE TABLE students             CASCADE;
TRUNCATE TABLE teachers             CASCADE;
TRUNCATE TABLE audit_logs           CASCADE;
TRUNCATE TABLE jwt_tokens           CASCADE;
TRUNCATE TABLE sessions             CASCADE;
TRUNCATE TABLE users                CASCADE;
TRUNCATE TABLE universities         CASCADE;

-- Réactiver les contraintes FK
SET session_replication_role = DEFAULT;

-- ============================================================
-- 1. UNIVERSITÉS
-- ============================================================
INSERT INTO universities (id, name, timezone, address, phone) VALUES
(1, 'Institut Universitaire Les Cours Sonou (LCS)', 'Africa/Porto-Novo', 'Porto-Novo, République du Bénin', '+229 20 21 45 67'),
(2, 'Université d''Abomey-Calavi (UAC)',             'Africa/Porto-Novo', 'Abomey-Calavi, Bénin',            '+229 21 36 00 74'),
(3, 'Université Nationale des Sciences, Technologies, Ingénierie et Mathématiques (UNSTIM)', 'Africa/Porto-Novo', 'Abomey, Bénin', '+229 22 50 01 11');

-- ============================================================
-- 2. UTILISATEURS  (mot de passe = bcrypt de "Password123!")
-- ============================================================

-- ── Administrateurs ──────────────────────────────────────────
INSERT INTO users (id, university_id, name, email, password, first_name, last_name, phone, role) VALUES
(1,  1, 'Fabrice SONOU',          'f.sonou@lcs.edu.bj',          '$2y$12$dummyhashADMIN001xxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Fabrice',   'SONOU',          '+229 97 00 00 01', 'admin'),
(2,  1, 'Gérard AGBIDINOUKOUN',   'g.agbidinoukoun@lcs.edu.bj',  '$2y$12$dummyhashADMIN002xxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Gérard',    'AGBIDINOUKOUN',  '+229 97 00 00 02', 'admin'),
(3,  2, 'Directeur UAC',          'admin@uac.edu.bj',            '$2y$12$dummyhashADMIN003xxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Kofi',      'AMOUSSOU',       '+229 97 00 00 03', 'admin');

-- ── Enseignants ───────────────────────────────────────────────
INSERT INTO users (id, university_id, name, email, password, first_name, last_name, phone, role) VALUES
(4,  1, 'Angelo ADJAI',           'a.adjai@lcs.edu.bj',          '$2y$12$dummyhashTEACH001xxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Angelo',    'ADJAI',          '+229 97 11 11 01', 'teacher'),
(5,  1, 'Jean Eudes DOHOU',       'je.dohou@lcs.edu.bj',         '$2y$12$dummyhashTEACH002xxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Jean Eudes','DOHOU',          '+229 97 11 11 02', 'teacher'),
(6,  1, 'Hermione GBADAMASSI',    'h.gbadamassi@lcs.edu.bj',     '$2y$12$dummyhashTEACH003xxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Hermione',  'GBADAMASSI',     '+229 97 11 11 03', 'teacher'),
(7,  1, 'Paul KOUDOSSOU',         'p.koudossou@lcs.edu.bj',      '$2y$12$dummyhashTEACH004xxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Paul',      'KOUDOSSOU',      '+229 97 11 11 04', 'teacher'),
(8,  2, 'Marie AGOSSOU',          'm.agossou@uac.edu.bj',        '$2y$12$dummyhashTEACH005xxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Marie',     'AGOSSOU',        '+229 97 11 11 05', 'teacher');

-- ── Étudiants LCS (université 1) ─────────────────────────────
INSERT INTO users (id, university_id, name, email, password, first_name, last_name, phone, role) VALUES
(10, 1, 'Abdoul Sorbour CHIDJOU', 'chidjou.abdoul@etu.lcs.edu.bj','$2y$12$dummyhashSTU001xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Abdoul Sorbour','CHIDJOU',   '+229 96 20 00 01', 'student'),
(11, 1, 'Mouhamed Idjilal ALOUGBIN','alougbin.mouhamed@etu.lcs.edu.bj','$2y$12$dummyhashSTU002xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Mouhamed Idjilal','ALOUGBIN', '+229 96 20 00 02', 'student'),
(12, 1, 'Fatima BELLO',           'fatima.bello@etu.lcs.edu.bj', '$2y$12$dummyhashSTU003xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Fatima',    'BELLO',          '+229 96 20 00 03', 'student'),
(13, 1, 'Kolade AGBI',            'kolade.agbi@etu.lcs.edu.bj',  '$2y$12$dummyhashSTU004xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Kolade',    'AGBI',           '+229 96 20 00 04', 'student'),
(14, 1, 'Rachida MOUSSA',         'rachida.moussa@etu.lcs.edu.bj','$2y$12$dummyhashSTU005xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Rachida',   'MOUSSA',         '+229 96 20 00 05', 'student'),
(15, 1, 'Hervé DOSSOU',           'herve.dossou@etu.lcs.edu.bj', '$2y$12$dummyhashSTU006xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Hervé',     'DOSSOU',         '+229 96 20 00 06', 'student'),
(16, 1, 'Aïcha ZANNOU',           'aicha.zannou@etu.lcs.edu.bj', '$2y$12$dummyhashSTU007xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Aïcha',     'ZANNOU',         '+229 96 20 00 07', 'student'),
(17, 1, 'Romuald ASSOGBA',        'romuald.assogba@etu.lcs.edu.bj','$2y$12$dummyhashSTU008xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Romuald',  'ASSOGBA',        '+229 96 20 00 08', 'student'),
(18, 1, 'Nadia TCHEOU',           'nadia.tcheou@etu.lcs.edu.bj', '$2y$12$dummyhashSTU009xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Nadia',     'TCHEOU',         '+229 96 20 00 09', 'student'),
(19, 1, 'Brice HOUNTO',           'brice.hounto@etu.lcs.edu.bj', '$2y$12$dummyhashSTU010xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Brice',     'HOUNTO',         '+229 96 20 00 10', 'student'),
-- Étudiants UAC (université 2)
(20, 2, 'Séraphine CODJO',        'seraphine.codjo@etu.uac.edu.bj','$2y$12$dummyhashSTU011xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx','Séraphine','CODJO',          '+229 96 20 00 11', 'student'),
(21, 2, 'Edgard GNANCADJA',       'edgard.gnancadja@etu.uac.edu.bj','$2y$12$dummyhashSTU012xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx','Edgard',  'GNANCADJA',      '+229 96 20 00 12', 'student');

SELECT setval('users_id_seq', 22);

-- ============================================================
-- 3. ENSEIGNANTS (table teachers)
-- ============================================================
INSERT INTO teachers (id, university_id, user_id, specialty, grade, hire_date, status) VALUES
(1, 1, 4, 'Réseaux et Systèmes d''Information',  'Ingénieur',         '2018-09-01', 'active'),
(2, 1, 5, 'Développement Full Stack',             'Maître de stage',   '2020-03-15', 'active'),
(3, 1, 6, 'Développement Web & Mobile',           'Ingénieure',        '2021-01-10', 'active'),
(4, 1, 7, 'Base de Données & Administration SI',  'Docteur',           '2016-09-01', 'active'),
(5, 2, 8, 'Algorithmique et Programmation',       'Maître de conférences','2019-02-01','active');

SELECT setval('teachers_id_seq', 6);

-- ============================================================
-- 4. ÉTUDIANTS (table students)
-- ============================================================
INSERT INTO students (id, university_id, user_id, registration_number, birth_date, level, enrollment_year) VALUES
( 1, 1, 10, 'LCS-2023-001', '2001-04-15', 'Licence 3', 2023),
( 2, 1, 11, 'LCS-2023-002', '2001-09-22', 'Licence 3', 2023),
( 3, 1, 12, 'LCS-2023-003', '2002-01-07', 'Licence 3', 2023),
( 4, 1, 13, 'LCS-2023-004', '2001-11-30', 'Licence 3', 2023),
( 5, 1, 14, 'LCS-2023-005', '2002-06-18', 'Licence 3', 2023),
( 6, 1, 15, 'LCS-2022-006', '2000-03-25', 'Licence 3', 2022),
( 7, 1, 16, 'LCS-2022-007', '2001-08-10', 'Licence 3', 2022),
( 8, 1, 17, 'LCS-2022-008', '2000-12-05', 'Licence 2', 2022),
( 9, 1, 18, 'LCS-2024-009', '2003-02-14', 'Licence 1', 2024),
(10, 1, 19, 'LCS-2024-010', '2003-07-20', 'Licence 1', 2024),
(11, 2, 20, 'UAC-2023-001', '2001-05-12', 'Licence 3', 2023),
(12, 2, 21, 'UAC-2023-002', '2002-10-08', 'Licence 2', 2023);

SELECT setval('students_id_seq', 13);

-- ============================================================
-- 5. SALLES (classrooms)
-- ============================================================
INSERT INTO classrooms (id, university_id, name, building, room_number, capacity, equipment, type, is_active) VALUES
(1, 1, 'Amphi A',      'Bâtiment Principal', 'A01', 150, 'Projecteur, Climatisation, Tableau blanc',       'amphitheater', TRUE),
(2, 1, 'Amphi B',      'Bâtiment Principal', 'A02', 100, 'Projecteur, Climatisation',                      'amphitheater', TRUE),
(3, 1, 'Salle Info 1', 'Bâtiment Informatique', 'I01', 40, 'Ordinateurs x40, Projecteur, Réseau local',    'lab',          TRUE),
(4, 1, 'Salle Info 2', 'Bâtiment Informatique', 'I02', 30, 'Ordinateurs x30, Projecteur',                  'lab',          TRUE),
(5, 1, 'Salle 201',    'Bâtiment B',         'B201', 50, 'Tableau blanc, Projecteur',                      'classroom',    TRUE),
(6, 1, 'Salle 202',    'Bâtiment B',         'B202', 50, 'Tableau blanc',                                  'classroom',    TRUE),
(7, 2, 'Amphi UAC-1',  'Campus Central',     'U01',  200,'Micro, Projecteur, Climatisation',               'amphitheater', TRUE),
(8, 2, 'Salle UAC-10', 'Campus Central',     'U10',  60, 'Projecteur, Tableau blanc',                      'classroom',    TRUE);

SELECT setval('classrooms_id_seq', 9);

-- ============================================================
-- 6. COURS (courses)
-- ============================================================
INSERT INTO courses (id, university_id, teacher_id, code, name, description, credits, level, semester, max_lateness_minutes) VALUES
-- LCS - Licence 3 SIL
(1, 1, 1, 'INF301', 'Réseaux Informatiques',            'Protocoles TCP/IP, routage, sécurité réseau',                6, 'Licence 3', 'S5', 5),
(2, 1, 2, 'INF302', 'Développement Web Avancé',         'Laravel, Vue.js, API REST, architecture MVC',               6, 'Licence 3', 'S5', 5),
(3, 1, 3, 'INF303', 'Développement Mobile',             'Flutter / React Native, intégration API',                   4, 'Licence 3', 'S5', 10),
(4, 1, 4, 'INF304', 'Base de Données Avancées',         'PostgreSQL, optimisation, triggers, procédures stockées',   6, 'Licence 3', 'S5', 5),
(5, 1, 1, 'INF305', 'IoT et Systèmes Embarqués',        'ESP32, RFID, protocoles IoT, MQTT',                         4, 'Licence 3', 'S6', 10),
(6, 1, 2, 'INF306', 'Sécurité des Systèmes d''Information','Cryptographie, authentification JWT, audit',            4, 'Licence 3', 'S6', 5),
-- LCS - Licence 1
(7, 1, 3, 'INF101', 'Algorithmique et Programmation',   'Bases de l''algorithmique, Python',                         6, 'Licence 1', 'S1', 10),
(8, 1, 4, 'INF102', 'Introduction aux SGBD',            'Modélisation des données, SQL de base',                     4, 'Licence 1', 'S1', 5),
-- UAC
(9, 2, 5, 'UAC-INF201', 'Structures de Données',        'Listes, piles, files, arbres, graphes',                     6, 'Licence 2', 'S3', 5);

SELECT setval('courses_id_seq', 10);

-- ============================================================
-- 7. EMPLOI DU TEMPS (timetable_entries)
-- Semestre S5 - année 2025-2026
-- ============================================================
INSERT INTO timetable_entries (id, university_id, course_id, classroom_id, day_of_week, start_time, end_time, session_type, recurrence_pattern, start_date, end_date) VALUES
-- Lundi
(1,  1, 1, 1, 'monday',    '08:00', '10:00', 'lecture',  'weekly', '2025-10-06', '2026-01-30'),
(2,  1, 2, 3, 'monday',    '10:15', '12:15', 'lab',      'weekly', '2025-10-06', '2026-01-30'),
(3,  1, 7, 5, 'monday',    '14:00', '16:00', 'lecture',  'weekly', '2025-10-06', '2026-01-30'),
-- Mardi
(4,  1, 3, 4, 'tuesday',   '08:00', '10:00', 'lab',      'weekly', '2025-10-07', '2026-01-30'),
(5,  1, 4, 1, 'tuesday',   '10:15', '12:15', 'lecture',  'weekly', '2025-10-07', '2026-01-30'),
(6,  1, 8, 6, 'tuesday',   '14:00', '16:00', 'lecture',  'weekly', '2025-10-07', '2026-01-30'),
-- Mercredi
(7,  1, 5, 3, 'wednesday', '08:00', '10:00', 'lab',      'weekly', '2025-10-08', '2026-01-30'),
(8,  1, 6, 2, 'wednesday', '10:15', '12:15', 'lecture',  'weekly', '2025-10-08', '2026-01-30'),
-- Jeudi
(9,  1, 1, 1, 'thursday',  '08:00', '10:00', 'lab',      'weekly', '2025-10-09', '2026-01-30'),
(10, 1, 2, 3, 'thursday',  '10:15', '12:15', 'lecture',  'weekly', '2025-10-09', '2026-01-30'),
-- Vendredi
(11, 1, 4, 4, 'friday',    '08:00', '10:00', 'lab',      'weekly', '2025-10-10', '2026-01-30'),
(12, 1, 3, 5, 'friday',    '10:15', '12:15', 'lecture',  'weekly', '2025-10-10', '2026-01-30'),
-- UAC
(13, 2, 9, 7, 'monday',    '08:00', '10:00', 'lecture',  'weekly', '2025-10-06', '2026-01-30');

SELECT setval('timetable_entries_id_seq', 14);

-- ============================================================
-- 8. INSCRIPTIONS AUX COURS (course_enrollments)
-- ============================================================
-- Étudiants L3 LCS inscrits aux cours L3
INSERT INTO course_enrollments (university_id, student_id, course_id, status, enrolled_at) VALUES
-- Chidjou - inscrit partout en L3
(1, 1, 1, 'enrolled', '2025-10-01 08:00:00'),
(1, 1, 2, 'enrolled', '2025-10-01 08:00:00'),
(1, 1, 3, 'enrolled', '2025-10-01 08:00:00'),
(1, 1, 4, 'enrolled', '2025-10-01 08:00:00'),
(1, 1, 5, 'enrolled', '2025-10-01 08:00:00'),
(1, 1, 6, 'enrolled', '2025-10-01 08:00:00'),
-- Alougbin - inscrit partout en L3
(1, 2, 1, 'enrolled', '2025-10-01 08:00:00'),
(1, 2, 2, 'enrolled', '2025-10-01 08:00:00'),
(1, 2, 3, 'enrolled', '2025-10-01 08:00:00'),
(1, 2, 4, 'enrolled', '2025-10-01 08:00:00'),
(1, 2, 5, 'enrolled', '2025-10-01 08:00:00'),
(1, 2, 6, 'enrolled', '2025-10-01 08:00:00'),
-- Fatima Bello - L3
(1, 3, 1, 'enrolled', '2025-10-01 08:00:00'),
(1, 3, 2, 'enrolled', '2025-10-01 08:00:00'),
(1, 3, 3, 'enrolled', '2025-10-01 08:00:00'),
(1, 3, 4, 'enrolled', '2025-10-01 08:00:00'),
(1, 3, 5, 'enrolled', '2025-10-01 08:00:00'),
-- Kolade Agbi - L3
(1, 4, 1, 'enrolled', '2025-10-01 08:00:00'),
(1, 4, 2, 'enrolled', '2025-10-01 08:00:00'),
(1, 4, 4, 'enrolled', '2025-10-01 08:00:00'),
(1, 4, 6, 'enrolled', '2025-10-01 08:00:00'),
-- Rachida Moussa - L3
(1, 5, 1, 'enrolled', '2025-10-01 08:00:00'),
(1, 5, 2, 'enrolled', '2025-10-01 08:00:00'),
(1, 5, 3, 'enrolled', '2025-10-01 08:00:00'),
(1, 5, 4, 'enrolled', '2025-10-01 08:00:00'),
-- Hervé Dossou - L3
(1, 6, 1, 'enrolled', '2025-10-01 08:00:00'),
(1, 6, 2, 'enrolled', '2025-10-01 08:00:00'),
(1, 6, 5, 'enrolled', '2025-10-01 08:00:00'),
(1, 6, 6, 'enrolled', '2025-10-01 08:00:00'),
-- Aïcha Zannou - L3
(1, 7, 1, 'enrolled', '2025-10-01 08:00:00'),
(1, 7, 3, 'enrolled', '2025-10-01 08:00:00'),
(1, 7, 4, 'enrolled', '2025-10-01 08:00:00'),
-- Romuald Assogba - L2
(1, 8, 7, 'enrolled', '2025-10-01 08:00:00'),
(1, 8, 8, 'enrolled', '2025-10-01 08:00:00'),
-- Étudiants L1
(1, 9,  7, 'enrolled', '2025-10-01 08:00:00'),
(1, 9,  8, 'enrolled', '2025-10-01 08:00:00'),
(1, 10, 7, 'enrolled', '2025-10-01 08:00:00'),
(1, 10, 8, 'enrolled', '2025-10-01 08:00:00'),
-- UAC
(2, 11, 9, 'enrolled', '2025-10-01 08:00:00'),
(2, 12, 9, 'enrolled', '2025-10-01 08:00:00');

-- ============================================================
-- 9. CARTES RFID (rfid_cards)
-- ============================================================
INSERT INTO rfid_cards (id, university_id, student_id, card_number, is_active, assigned_at, last_scanned_at) VALUES
('a1b2c3d4-0001-0000-0000-000000000001', 1,  1, 'RFID-LCS-00001', TRUE, '2025-10-01 09:00:00', '2025-11-04 07:58:00'),
('a1b2c3d4-0002-0000-0000-000000000002', 1,  2, 'RFID-LCS-00002', TRUE, '2025-10-01 09:00:00', '2025-11-04 07:55:00'),
('a1b2c3d4-0003-0000-0000-000000000003', 1,  3, 'RFID-LCS-00003', TRUE, '2025-10-01 09:00:00', '2025-11-04 08:02:00'),
('a1b2c3d4-0004-0000-0000-000000000004', 1,  4, 'RFID-LCS-00004', TRUE, '2025-10-01 09:00:00', '2025-11-03 14:05:00'),
('a1b2c3d4-0005-0000-0000-000000000005', 1,  5, 'RFID-LCS-00005', TRUE, '2025-10-01 09:00:00', '2025-10-28 08:10:00'),
('a1b2c3d4-0006-0000-0000-000000000006', 1,  6, 'RFID-LCS-00006', TRUE, '2025-10-01 09:00:00', '2025-11-04 08:00:00'),
('a1b2c3d4-0007-0000-0000-000000000007', 1,  7, 'RFID-LCS-00007', TRUE, '2025-10-01 09:00:00', '2025-11-04 08:14:00'),
('a1b2c3d4-0008-0000-0000-000000000008', 1,  8, 'RFID-LCS-00008', TRUE, '2025-10-01 09:00:00', '2025-11-03 10:20:00'),
('a1b2c3d4-0009-0000-0000-000000000009', 1,  9, 'RFID-LCS-00009', TRUE, '2025-10-01 09:00:00', '2025-11-04 07:59:00'),
('a1b2c3d4-0010-0000-0000-000000000010', 1, 10, 'RFID-LCS-00010', FALSE,'2025-10-01 09:00:00', '2025-10-15 14:00:00'),
('a1b2c3d4-0011-0000-0000-000000000011', 2, 11, 'RFID-UAC-00001', TRUE, '2025-10-01 09:00:00', '2025-11-04 07:52:00'),
('a1b2c3d4-0012-0000-0000-000000000012', 2, 12, 'RFID-UAC-00002', TRUE, '2025-10-01 09:00:00', '2025-11-03 08:05:00');

-- Carte désactivée pour étudiant 10 (carte perdue)
UPDATE rfid_cards
SET    is_active = FALSE, deactivated_at = '2025-10-20 10:00:00', deactivation_reason = 'Carte perdue signalée par l''étudiant'
WHERE  id = 'a1b2c3d4-0010-0000-0000-000000000010';

-- ============================================================
-- 10. PRÉSENCES (attendances)
-- Simulation de 4 séances du cours INF301 (timetable_entry_id = 1)
-- ============================================================

-- Séance 1 - Lundi 06/10/2025 (INF301 - Réseaux - Amphi A)
INSERT INTO attendances (university_id, timetable_entry_id, student_id, status, scanned_at, verification_method, notes) VALUES
(1, 1, 1, 'present', '2025-10-06 07:58:00', 'rfid', NULL),
(1, 1, 2, 'present', '2025-10-06 07:55:00', 'rfid', NULL),
(1, 1, 3, 'late',    '2025-10-06 08:08:00', 'rfid', 'Arrivée 8 min après le début'),
(1, 1, 4, 'absent',  NULL,                  NULL,   NULL),
(1, 1, 5, 'present', '2025-10-06 07:59:00', 'rfid', NULL),
(1, 1, 6, 'present', '2025-10-06 08:00:00', 'rfid', NULL),
(1, 1, 7, 'absent',  NULL,                  NULL,   'Pas de scan RFID enregistré');

-- Séance 2 - Lundi 13/10/2025 (INF301)
INSERT INTO attendances (university_id, timetable_entry_id, student_id, status, scanned_at, verification_method, notes) VALUES
(1, 1, 1, 'present', '2025-10-13 07:57:00', 'rfid', NULL),
(1, 1, 2, 'late',    '2025-10-13 08:07:00', 'rfid', 'Retard de 7 minutes'),
(1, 1, 3, 'present', '2025-10-13 08:01:00', 'rfid', NULL),
(1, 1, 4, 'present', '2025-10-13 07:59:00', 'rfid', NULL),
(1, 1, 5, 'absent',  NULL,                  NULL,   'Justification: certificat médical reçu'),
(1, 1, 6, 'present', '2025-10-13 07:58:00', 'rfid', NULL),
(1, 1, 7, 'present', '2025-10-13 08:02:00', 'rfid', NULL);

-- Séance 3 - Jeudi 09/10/2025 (INF301 - TP - timetable_entry_id=9)
INSERT INTO attendances (university_id, timetable_entry_id, student_id, status, scanned_at, verification_method, notes) VALUES
(1, 9, 1, 'present', '2025-10-09 07:59:00', 'rfid', NULL),
(1, 9, 2, 'present', '2025-10-09 07:56:00', 'rfid', NULL),
(1, 9, 3, 'present', '2025-10-09 08:00:00', 'rfid', NULL),
(1, 9, 4, 'late',    '2025-10-09 08:10:00', 'rfid', 'Retard de 10 minutes - limite atteinte'),
(1, 9, 5, 'present', '2025-10-09 08:03:00', 'rfid', NULL),
(1, 9, 6, 'absent',  NULL,                  NULL,   NULL),
(1, 9, 7, 'justified','2025-10-09 NULL',     'manual','Absence justifiée: convocation administrative');

-- Séance INF302 (Dev Web - TP labo - timetable_entry_id=2)
INSERT INTO attendances (university_id, timetable_entry_id, student_id, status, scanned_at, verification_method, notes) VALUES
(1, 2, 1, 'present', '2025-10-06 10:14:00', 'rfid', NULL),
(1, 2, 2, 'present', '2025-10-06 10:10:00', 'rfid', NULL),
(1, 2, 3, 'late',    '2025-10-06 10:28:00', 'rfid', 'Retard de 13 minutes'),
(1, 2, 4, 'present', '2025-10-06 10:13:00', 'rfid', NULL),
(1, 2, 5, 'absent',  NULL,                  NULL,   NULL);

-- Séance UAC (timetable_entry_id=13)
INSERT INTO attendances (university_id, timetable_entry_id, student_id, status, scanned_at, verification_method, notes) VALUES
(2, 13, 11, 'present', '2025-10-06 07:50:00', 'rfid', NULL),
(2, 13, 12, 'late',    '2025-10-06 08:07:00', 'rfid', 'Retard de 7 minutes');

-- ============================================================
-- 11. LOGS D'ACCÈS RFID (access_logs)
-- Simule les scans réels du lecteur RFID à l'entrée des salles
-- ============================================================
INSERT INTO access_logs (id, university_id, rfid_card_id, classroom_id, scanned_at, status, refusal_reason) VALUES
-- Accès accordés - séance INF301 Lundi
('b0000000-0001-0000-0000-000000000001', 1, 'a1b2c3d4-0001-0000-0000-000000000001', 1, '2025-10-06 07:58:00', 'granted', NULL),
('b0000000-0002-0000-0000-000000000002', 1, 'a1b2c3d4-0002-0000-0000-000000000002', 1, '2025-10-06 07:55:00', 'granted', NULL),
('b0000000-0003-0000-0000-000000000003', 1, 'a1b2c3d4-0005-0000-0000-000000000005', 1, '2025-10-06 07:59:00', 'granted', NULL),
('b0000000-0004-0000-0000-000000000004', 1, 'a1b2c3d4-0006-0000-0000-000000000006', 1, '2025-10-06 08:00:00', 'granted', NULL),
-- Accès accordé mais en retard (scan après 08:05)
('b0000000-0005-0000-0000-000000000005', 1, 'a1b2c3d4-0003-0000-0000-000000000003', 1, '2025-10-06 08:08:00', 'granted', NULL),
-- Accès refusés
('b0000000-0006-0000-0000-000000000006', 1, 'a1b2c3d4-0007-0000-0000-000000000007', 1, '2025-10-06 08:14:00', 'refused', 'Retard supérieur à la limite autorisée (5 min) : 14 min'),
('b0000000-0007-0000-0000-000000000007', 1, 'a1b2c3d4-0010-0000-0000-000000000010', 1, '2025-10-06 08:00:00', 'refused', 'Carte RFID désactivée'),
-- Tentative d''accès à une mauvaise salle
('b0000000-0008-0000-0000-000000000008', 1, 'a1b2c3d4-0004-0000-0000-000000000004', 2, '2025-10-06 08:01:00', 'refused', 'Aucune session planifiée pour cet étudiant dans cette salle'),
-- Séance 13/10/2025 INF301
('b0000000-0009-0000-0000-000000000009', 1, 'a1b2c3d4-0001-0000-0000-000000000001', 1, '2025-10-13 07:57:00', 'granted', NULL),
('b0000000-0010-0000-0000-000000000010', 1, 'a1b2c3d4-0002-0000-0000-000000000002', 1, '2025-10-13 08:07:00', 'granted', NULL),
('b0000000-0011-0000-0000-000000000011', 1, 'a1b2c3d4-0003-0000-0000-000000000003', 1, '2025-10-13 08:01:00', 'granted', NULL),
('b0000000-0012-0000-0000-000000000012', 1, 'a1b2c3d4-0004-0000-0000-000000000004', 1, '2025-10-13 07:59:00', 'granted', NULL),
-- Accès refusé - retard excessif
('b0000000-0013-0000-0000-000000000013', 1, 'a1b2c3d4-0007-0000-0000-000000000007', 1, '2025-10-13 08:18:00', 'refused', 'Retard supérieur à la limite autorisée (5 min) : 18 min'),
-- Scan TP INF302
('b0000000-0014-0000-0000-000000000014', 1, 'a1b2c3d4-0001-0000-0000-000000000001', 3, '2025-10-06 10:14:00', 'granted', NULL),
('b0000000-0015-0000-0000-000000000015', 1, 'a1b2c3d4-0002-0000-0000-000000000002', 3, '2025-10-06 10:10:00', 'granted', NULL),
('b0000000-0016-0000-0000-000000000016', 1, 'a1b2c3d4-0003-0000-0000-000000000003', 3, '2025-10-06 10:28:00', 'refused', 'Retard supérieur à la limite autorisée (5 min) : 13 min'),
-- UAC
('b0000000-0017-0000-0000-000000000017', 2, 'a1b2c3d4-0011-0000-0000-000000000011', 7, '2025-10-06 07:50:00', 'granted', NULL),
('b0000000-0018-0000-0000-000000000018', 2, 'a1b2c3d4-0012-0000-0000-000000000012', 7, '2025-10-06 08:07:00', 'granted', NULL);

-- ============================================================
-- 12. AUDIT LOGS (traçabilité des actions)
-- ============================================================
INSERT INTO audit_logs (university_id, user_id, resource_type, resource_id, action, old_values, new_values, ip_address) VALUES
-- Création des cours
(1, 1, 'course',    1, 'create', NULL,                              '{"code":"INF301","name":"Réseaux Informatiques"}',             '192.168.1.1'),
(1, 1, 'course',    2, 'create', NULL,                              '{"code":"INF302","name":"Développement Web Avancé"}',           '192.168.1.1'),
-- Désactivation carte RFID
(1, 2, 'rfid_card', NULL, 'update','{"is_active":true}',           '{"is_active":false,"deactivation_reason":"Carte perdue"}',     '192.168.1.5'),
-- Connexion admin
(1, 1, 'user',      1,    'login', NULL,                           '{"last_login_at":"2025-10-06 07:30:00"}',                      '192.168.1.1'),
-- Scan RFID refusé
(1, NULL, 'rfid_card', NULL, 'rfid_scan', NULL,                    '{"card":"RFID-LCS-00007","status":"refused","reason":"Retard"}','192.168.10.5'),
-- Mise à jour présence (correction manuelle par enseignant)
(1, 4, 'attendance', NULL, 'update', '{"status":"absent"}',        '{"status":"justified","notes":"Convocation administrative"}',  '192.168.1.10'),
-- Inscription étudiant
(1, 1, 'enrollment', NULL, 'create', NULL,                         '{"student_id":1,"course_id":1,"status":"enrolled"}',           '192.168.1.1');

-- ============================================================
-- 13. TOKENS JWT (sessions actives simulées)
-- ============================================================
INSERT INTO jwt_tokens (id, user_id, university_id, type, token, expires_at, ip_address, user_agent) VALUES
(gen_random_uuid(), 1, 1, 'access',  'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.admin001.access',  NOW() + INTERVAL '15 minutes', '192.168.1.1', 'Mozilla/5.0 Chrome/120'),
(gen_random_uuid(), 1, 1, 'refresh', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.admin001.refresh', NOW() + INTERVAL '7 days',     '192.168.1.1', 'Mozilla/5.0 Chrome/120'),
(gen_random_uuid(), 4, 1, 'access',  'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.teach001.access',  NOW() + INTERVAL '15 minutes', '192.168.1.10','Mozilla/5.0 Firefox/120'),
(gen_random_uuid(), 4, 1, 'refresh', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.teach001.refresh', NOW() + INTERVAL '7 days',     '192.168.1.10','Mozilla/5.0 Firefox/120'),
(gen_random_uuid(), 10, 1, 'access', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.stu001.access',    NOW() + INTERVAL '15 minutes', '10.0.0.15',   'Arcanatech Mobile App v1.0 Android'),
(gen_random_uuid(), 11, 1, 'access', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.stu002.access',    NOW() + INTERVAL '15 minutes', '10.0.0.16',   'Arcanatech Mobile App v1.0 iOS');

-- ============================================================
-- VÉRIFICATION RAPIDE
-- ============================================================
SELECT 'universities'    AS table_name, COUNT(*) AS rows FROM universities
UNION ALL SELECT 'users',           COUNT(*) FROM users
UNION ALL SELECT 'teachers',        COUNT(*) FROM teachers
UNION ALL SELECT 'students',        COUNT(*) FROM students
UNION ALL SELECT 'courses',         COUNT(*) FROM courses
UNION ALL SELECT 'classrooms',      COUNT(*) FROM classrooms
UNION ALL SELECT 'timetable_entries', COUNT(*) FROM timetable_entries
UNION ALL SELECT 'course_enrollments', COUNT(*) FROM course_enrollments
UNION ALL SELECT 'rfid_cards',      COUNT(*) FROM rfid_cards
UNION ALL SELECT 'attendances',     COUNT(*) FROM attendances
UNION ALL SELECT 'access_logs',     COUNT(*) FROM access_logs
UNION ALL SELECT 'audit_logs',      COUNT(*) FROM audit_logs
UNION ALL SELECT 'jwt_tokens',      COUNT(*) FROM jwt_tokens
ORDER BY table_name;

-- ============================================================
-- REQUÊTES DE CONTRÔLE RECOMMANDÉES
-- ============================================================

-- 1. Taux de présence par étudiant (cours INF301)
-- SELECT u.first_name, u.last_name,
--        COUNT(CASE WHEN a.status = 'present' THEN 1 END) AS presences,
--        COUNT(CASE WHEN a.status = 'late'    THEN 1 END) AS retards,
--        COUNT(CASE WHEN a.status = 'absent'  THEN 1 END) AS absences,
--        COUNT(*) AS total_seances
-- FROM attendances a
-- JOIN students s   ON a.student_id   = s.id
-- JOIN users u      ON s.user_id      = u.id
-- JOIN timetable_entries te ON a.timetable_entry_id = te.id
-- WHERE te.course_id = 1
-- GROUP BY u.first_name, u.last_name;

-- 2. Tous les refus d'accès RFID avec raison
-- SELECT al.scanned_at, rc.card_number, u.first_name, u.last_name, al.refusal_reason, c.name AS salle
-- FROM access_logs al
-- JOIN rfid_cards rc ON al.rfid_card_id = rc.id
-- JOIN students s    ON rc.student_id   = s.id
-- JOIN users u       ON s.user_id       = u.id
-- JOIN classrooms c  ON al.classroom_id = c.id
-- WHERE al.status = 'refused'
-- ORDER BY al.scanned_at DESC;

-- 3. Emploi du temps de la semaine (LCS)
-- SELECT te.day_of_week, te.start_time, te.end_time, c.name AS cours,
--        cr.name AS salle, u.first_name || ' ' || u.last_name AS enseignant
-- FROM timetable_entries te
-- JOIN courses    c  ON te.course_id    = c.id
-- JOIN classrooms cr ON te.classroom_id = cr.id
-- JOIN teachers   t  ON c.teacher_id    = t.id
-- JOIN users      u  ON t.user_id       = u.id
-- WHERE te.university_id = 1
-- ORDER BY te.day_of_week, te.start_time;
