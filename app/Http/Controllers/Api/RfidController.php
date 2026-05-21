<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RfidCard;
use App\Models\AccessLog;
use App\Models\Attendance;
use App\Models\TimetableEntry;
use App\Models\AuditLog;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * RfidController - Gestion du système RFID et contrôle d'accès aux salles
 *
 * ARCHITECTURE DU CONTRÔLE D'ACCÈS :
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │  CARTE SCANNÉE                                                  │
 * │    │                                                            │
 * │    ├─► Type = ADMIN ?                                           │
 * │    │     OUI → Accès universel à toutes les salles ✅           │
 * │    │     NON → Type = ÉTUDIANT                                  │
 * │    │              │                                             │
 * │    │              ├─► Vérifier identité (carte active)          │
 * │    │              ├─► Vérifier date (cours prévu ce jour)       │
 * │    │              ├─► Vérifier heure (dans la plage horaire)    │
 * │    │              ├─► Vérifier matière/salle (cours correct)    │
 * │    │              │     Si matière ≠ salle → REFUSÉ ❌          │
 * │    │              └─► Vérifier retard                           │
 * │    │                    ≤ max → PRÉSENT ✅                      │
 * │    │                    ≤ max+15 → EN RETARD ⚠️                │
 * │    │                    > max+15 → REFUSÉ ❌                    │
 * │    │                                                            │
 * │    └─► ADMIN OVERRIDE (forcer accès étudiant en retard)         │
 * │          L'admin peut autoriser un étudiant refusé ✅           │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * RÈGLES MÉTIER :
 * 1. L'administrateur FOURNIT les accès (il les crée/active)
 * 2. L'admin est le SEUL à pouvoir ouvrir toutes les salles
 * 3. L'admin peut ÉTENDRE l'accès d'un étudiant en retard
 * 4. Le système vérifie : identité + heure + date + matière
 * 5. Si la matière ne correspond pas à la salle → REFUS
 */
class RfidController extends Controller
{
    /**
     * POST /api/universities/{universityId}/rfid/scan
     *
     * Endpoint principal appelé par le lecteur RFID à chaque scan de carte
     *
     * REQUEST :
     * {
     *   "card_number": "F4A92B1C00",
     *   "classroom_id": 1,                    // OBLIGATOIRE - la salle où se trouve le lecteur
     *   "timestamp": "2026-03-01T09:15:00Z"   // Optional (utilise now() par défaut)
     * }
     */
    public function scan(Request $request)
    {
        $validated = $request->validate([
            'card_number' => 'required|string',
            'classroom_id' => 'required|integer|exists:salles,id',
            'timestamp' => 'nullable|date_format:Y-m-d\TH:i:s\Z',
        ]);

        $cardNumber = $validated['card_number'];
        $classroomId = $validated['classroom_id'];
        $scanTime = $validated['timestamp']
            ? Carbon::parse($validated['timestamp'])
            : now();

        // ========== ÉTAPE 1 : Trouver la carte RFID ==========
        $rfidCard = RfidCard::where('numero_carte', $cardNumber)->first();

        if (!$rfidCard) {
            return $this->accessRefused(
                null,
                'card_not_found',
                'Carte RFID introuvable dans le système',
                $classroomId,
                $scanTime
            );
        }

        // ========== ÉTAPE 2 : Vérifier que la carte est active ==========
        if (!$rfidCard->est_active) {
            return $this->accessRefused(
                $rfidCard,
                'card_inactive',
                'Carte RFID désactivée depuis ' . ($rfidCard->desactivee_le ? $rfidCard->desactivee_le->format('Y-m-d') : 'date inconnue'),
                $classroomId,
                $scanTime
            );
        }

        // ========== ÉTAPE 3 : CARTE ADMIN = ACCÈS UNIVERSEL ==========
        if ($rfidCard->isAdminCard()) {
            return $this->handleAdminScan($rfidCard, $classroomId, $scanTime);
        }

        // ========== ÉTAPE 4 : CARTE ÉTUDIANT - Vérification complète ==========
        return $this->handleStudentScan($rfidCard, $classroomId, $scanTime);
    }

    /**
     * Gérer le scan d'une carte ADMIN
     *
     * L'admin est le SEUL qui peut ouvrir toutes les salles avec sa carte.
     * Aucune vérification d'emploi du temps n'est nécessaire.
     */
    private function handleAdminScan(RfidCard $rfidCard, int $classroomId, Carbon $scanTime)
    {
        $user = $rfidCard->user;
        $classroom = Classroom::find($classroomId);

        // Enregistrer l'accès admin
        $accessLog = AccessLog::create([
            'universite_id' => $rfidCard->universite_id,
            'carte_rfid_id' => $rfidCard->id,
            'salle_id'      => $classroomId,
            'scanne_le'     => $scanTime,
            'statut'        => 'granted',
            'type_acces'    => 'admin_scan',
        ]);

        // Audit log
        AuditLog::create([
            'universite_id'     => $rfidCard->universite_id,
            'utilisateur_id'    => $user->id ?? null,
            'type_ressource'    => 'AccessLog',
            'id_ressource'      => $accessLog->id,
            'action'            => 'admin_rfid_scan',
            'nouvelles_valeurs' => [
                'classroom' => $classroom->nom ?? 'Salle #' . $classroomId,
                'scanned_at' => $scanTime->toIso8601String(),
            ],
            'adresse_ip'        => request()->ip(),
        ]);

        // Mettre à jour dernier_scan_le
        $rfidCard->update(['dernier_scan_le' => $scanTime]);

        return response()->json([
            'status' => 'granted',
            'card_type' => 'admin',
            'message' => 'Accès admin autorisé - Ouverture de la salle',
            'admin' => [
                'id' => $user->id ?? null,
                'name' => $user->full_name ?? 'Administrateur',
            ],
            'classroom' => [
                'id' => $classroomId,
                'name' => $classroom->nom ?? null,
                'building' => $classroom->batiment ?? null,
            ],
            'scanned_at' => $scanTime->toIso8601String(),
            'access_log_id' => $accessLog->id,
        ], 200);
    }

    /**
     * Gérer le scan d'une carte ÉTUDIANT
     *
     * Vérification complète en 5 points :
     * 1. Identité (carte → étudiant)
     * 2. Date (cours prévu aujourd'hui)
     * 3. Heure (dans la plage horaire)
     * 4. Matière/Salle (le cours de l'étudiant correspond à la salle)
     * 5. Retard (dans la marge autorisée)
     */
    private function handleStudentScan(RfidCard $rfidCard, int $classroomId, Carbon $scanTime)
    {
        $student = $rfidCard->student;

        if (!$student) {
            return $this->accessRefused(
                $rfidCard,
                'no_student_linked',
                'Carte RFID non liée à un étudiant',
                $classroomId,
                $scanTime
            );
        }

        // Convertir le jour en français (correspondance BDD)
        $joursFr = [
            'Monday'    => 'lundi',
            'Tuesday'   => 'mardi',
            'Wednesday' => 'mercredi',
            'Thursday'  => 'jeudi',
            'Friday'    => 'vendredi',
            'Saturday'  => 'samedi',
            'Sunday'    => 'dimanche',
        ];
        $currentDayOfWeek = $joursFr[$scanTime->format('l')] ?? $scanTime->format('l');

        // ====== VÉRIFICATION 1 : Cours prévus ce jour pour l'étudiant ======
        $todayTimetables = TimetableEntry::whereHas('course.courseEnrollments', function ($q) use ($student) {
                $q->where('etudiant_id', $student->id)
                  ->whereIn('statut', ['inscrit', 'enrolled']); // supporte les deux conventions
            })
            ->where('jour_semaine', $currentDayOfWeek)
            ->where('universite_id', $rfidCard->universite_id)
            ->active() // Vérifie start_date/end_date
            ->with(['course', 'classroom'])
            ->get();

        if ($todayTimetables->isEmpty()) {
            return $this->accessRefused(
                $rfidCard,
                'no_course_today',
                'Aucun cours programmé pour vous aujourd\'hui (' . ucfirst($currentDayOfWeek) . ')',
                $classroomId,
                $scanTime
            );
        }

        // ====== VÉRIFICATION 2 : Trouver le cours correspondant à l'HEURE du scan ======
        $matchedTimetable = $todayTimetables->first(function ($timetable) use ($scanTime) {
            $startTime = Carbon::createFromFormat('H:i:s', $timetable->heure_debut);
            $endTime = Carbon::createFromFormat('H:i:s', $timetable->heure_fin);

            $scanHour = $scanTime->copy()->setDate(2000, 1, 1);
            $startHour = $startTime->copy()->setDate(2000, 1, 1);
            $endHour = $endTime->copy()->setDate(2000, 1, 1);

            // Plage de scan : [start - 1h, end + 30min]
            return $scanHour->isBetween(
                $startHour->copy()->subHour(),
                $endHour->copy()->addMinutes(30)
            );
        });

        if (!$matchedTimetable) {
            return $this->accessRefused(
                $rfidCard,
                'outside_course_hours',
                'Scan effectué en dehors des heures de cours',
                $classroomId,
                $scanTime
            );
        }

        // ====== VÉRIFICATION 3 : La MATIÈRE correspond à la SALLE ======
        // La salle où l'étudiant scanne DOIT être celle où son cours a lieu
        if ($matchedTimetable->salle_id !== $classroomId) {
            $expectedClassroom = $matchedTimetable->classroom;
            $scannedClassroom = Classroom::find($classroomId);

            return $this->accessRefused(
                $rfidCard,
                'wrong_classroom',
                sprintf(
                    'Mauvaise salle. Votre cours "%s" a lieu en salle %s (bâtiment %s), pas en salle %s',
                    $matchedTimetable->course->nom,
                    $expectedClassroom->nom ?? '?',
                    $expectedClassroom->batiment ?? '?',
                    $scannedClassroom->nom ?? '#' . $classroomId
                ),
                $classroomId,
                $scanTime
            );
        }

        // ====== VÉRIFICATION 4 : LOGIQUE DES RETARDS ======
        $course = $matchedTimetable->course;
        $startTimeCarbon = $scanTime->copy()
            ->setTimeFromTimeString($matchedTimetable->heure_debut);

        // positif = en retard, négatif = en avance
        // $startTimeCarbon->diffInMinutes($scanTime) = scanTime - startTime
        $minutesLate = (int) $startTimeCarbon->diffInMinutes($scanTime, false);
        $maxLatenessMinutes = $course->minutes_retard_max;

        // Déterminer le statut
        if ($minutesLate <= 0) {
            // Arrivé à l'heure ou en avance
            $status = 'present';
            $isAccessGranted = true;
        } elseif ($minutesLate <= $maxLatenessMinutes) {
            // Retard acceptable → présent
            $status = 'present';
            $isAccessGranted = true;
        } elseif ($minutesLate <= $maxLatenessMinutes + 15) {
            // Retard important → late mais accès accordé
            $status = 'late';
            $isAccessGranted = true;
        } else {
            // Trop tard → refus d'accès (seul l'admin peut override)
            return $this->accessRefused(
                $rfidCard,
                'max_lateness_exceeded',
                sprintf(
                    'Retard excessif : %d minutes (limite : %d min pour %s). Contactez l\'administrateur pour une autorisation.',
                    $minutesLate,
                    $maxLatenessMinutes,
                    $course->nom
                ),
                $classroomId,
                $scanTime
            );
        }

        // ====== ÉTAPE 5 : Enregistrer la présence ======
        $attendance = Attendance::updateOrCreate(
            [
                'seance_id'   => $matchedTimetable->id,
                'etudiant_id' => $student->id,
            ],
            [
                'universite_id'        => $rfidCard->universite_id,
                'statut'               => $status,
                'scanne_le'            => $scanTime,
                'methode_verification' => 'rfid',
                'notes'                => $minutesLate > 0
                    ? "Scanned {$minutesLate} minutes late"
                    : null,
            ]
        );

        // ====== ÉTAPE 6 : Enregistrer l'accès (granted) ======
        $accessLog = AccessLog::create([
            'universite_id' => $rfidCard->universite_id,
            'carte_rfid_id' => $rfidCard->id,
            'salle_id'      => $classroomId,
            'scanne_le'     => $scanTime,
            'statut'        => 'granted',
            'type_acces'    => 'normal',
        ]);

        // ====== ÉTAPE 7 : Audit log ======
        AuditLog::create([
            'universite_id'     => $rfidCard->universite_id,
            'utilisateur_id'    => $student->utilisateur_id,
            'type_ressource'    => 'Attendance',
            'id_ressource'      => $attendance->id,
            'action'            => 'rfid_scan',
            'nouvelles_valeurs' => [
                'status' => $status,
                'scanned_at' => $scanTime->toIso8601String(),
                'minutes_late' => max(0, $minutesLate),
                'course' => $course->nom,
                'classroom' => $matchedTimetable->classroom->nom ?? null,
            ],
            'adresse_ip'        => request()->ip(),
        ]);

        // Mettre à jour dernier_scan_le
        $rfidCard->update(['dernier_scan_le' => $scanTime]);

        // ====== ÉTAPE 8 : Réponse au lecteur RFID ======
        return response()->json([
            'status' => $status,
            'card_type' => 'student',
            'message' => $status === 'present'
                ? 'Présence enregistrée - Accès autorisé'
                : 'Retard enregistré - Accès autorisé',
            'student' => [
                'id' => $student->id,
                'name' => $student->user->full_name,
                'registration_number' => $student->numero_matricule,
                'filiere' => $student->filiere->nom ?? null,
            ],
            'course' => [
                'id' => $course->id,
                'name' => $course->nom,
                'code' => $course->code,
                'teacher_name' => $course->teacher->user->full_name ?? null,
            ],
            'classroom' => [
                'id' => $matchedTimetable->salle_id,
                'name' => $matchedTimetable->classroom->nom ?? null,
            ],
            'timetable' => [
                'start_time' => $matchedTimetable->heure_debut,
                'end_time' => $matchedTimetable->heure_fin,
                'max_lateness_minutes' => $maxLatenessMinutes,
            ],
            'attendance' => [
                'id' => $attendance->id,
                'scanned_at' => $attendance->scanne_le,
                'late_minutes' => max(0, $minutesLate),
            ],
            'access_log_id' => $accessLog->id,
        ], 200);
    }

    /**
     * POST /api/universities/{universityId}/rfid/admin-override
     *
     * L'admin force l'accès pour un étudiant en retard.
     * SEUL l'admin peut utiliser cet endpoint.
     *
     * SCÉNARIO :
     * 1. L'étudiant se présente en retard → système refuse
     * 2. L'étudiant demande à l'admin
     * 3. L'admin utilise son interface → POST admin-override
     * 4. Le système crée Attendance + AccessLog avec is_admin_override=true
     *
     * REQUEST :
     * {
     *   "student_id": 42,
     *   "classroom_id": 1,
     *   "timetable_entry_id": 15,
     *   "reason": "Étudiant autorisé malgré retard - transport en grève"
     * }
     */
    public function adminOverride(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|integer|exists:etudiants,id',
            'classroom_id' => 'required|integer|exists:salles,id',
            'timetable_entry_id' => 'required|integer|exists:seances,id',
            'reason' => 'required|string|max:500',
        ]);

        // Vérifier que l'utilisateur est admin
        $adminUser = $request->user();
        if (!$adminUser || $adminUser->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Seul un administrateur peut forcer l\'accès',
            ], 403);
        }

        $timetableEntry = TimetableEntry::with(['course', 'classroom'])->find($validated['timetable_entry_id']);
        $student = \App\Models\Student::with('user')->find($validated['student_id']);

        if (!$timetableEntry || !$student) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session ou étudiant introuvable',
            ], 404);
        }

        $now = now();

        // Créer la présence avec override admin
        $attendance = Attendance::updateOrCreate(
            [
                'seance_id'   => $timetableEntry->id,
                'etudiant_id' => $student->id,
            ],
            [
                'universite_id'        => $adminUser->universite_id,
                'statut'               => 'late', // Marqué en retard mais autorisé
                'scanne_le'            => $now,
                'methode_verification' => 'manual',
                'notes'                => 'Admin override: ' . $validated['reason'],
            ]
        );

        // Créer l'access log avec marqueur override
        $accessLog = AccessLog::create([
            'universite_id'              => $adminUser->universite_id,
            'carte_rfid_id'              => $student->rfidCard->id ?? null,
            'salle_id'                   => $validated['classroom_id'],
            'scanne_le'                  => $now,
            'statut'                     => 'granted',
            'est_override_admin'         => true,
            'override_par_utilisateur_id' => $adminUser->id,
            'raison_override'            => $validated['reason'],
            'type_acces'                 => 'admin_override',
        ]);

        // Audit log
        AuditLog::create([
            'universite_id'     => $adminUser->universite_id,
            'utilisateur_id'    => $adminUser->id,
            'type_ressource'    => 'Attendance',
            'id_ressource'      => $attendance->id,
            'action'            => 'admin_override_access',
            'nouvelles_valeurs' => [
                'student_id' => $student->id,
                'student_name' => $student->user->full_name,
                'course' => $timetableEntry->course->nom,
                'reason' => $validated['reason'],
                'overridden_at' => $now->toIso8601String(),
            ],
            'adresse_ip'        => request()->ip(),
        ]);

        return response()->json([
            'status' => 'granted',
            'message' => 'Accès autorisé par l\'administrateur',
            'override' => [
                'admin_name' => $adminUser->full_name,
                'reason' => $validated['reason'],
                'student_name' => $student->user->full_name,
                'course' => $timetableEntry->course->nom,
                'classroom' => $timetableEntry->classroom->nom ?? null,
            ],
            'attendance_id' => $attendance->id,
            'access_log_id' => $accessLog->id,
        ], 200);
    }

    /**
     * Helper : Refuser l'accès et enregistrer le log
     */
    private function accessRefused(
        ?RfidCard $rfidCard,
        string $reason,
        string $details,
        ?int $classroomId,
        Carbon $scanTime
    ) {
        $accessLogId = null;

        if ($rfidCard) {
            $accessLog = AccessLog::create([
                'universite_id' => $rfidCard->universite_id,
                'carte_rfid_id' => $rfidCard->id,
                'salle_id'      => $classroomId,
                'scanne_le'     => $scanTime,
                'statut'        => 'refused',
                'raison_refus'  => $reason,
                'type_acces'    => 'normal',
            ]);
            $accessLogId = $accessLog->id;
        }

        return response()->json([
            'status' => 'refused',
            'message' => 'Accès refusé',
            'reason' => $reason,
            'details' => $details,
            'access_log_id' => $accessLogId,
        ], 403);
    }

    /**
     * POST /api/universities/{universityId}/rfid/assign
     *
     * Attribuer une carte RFID à un étudiant (admin uniquement).
     * Si l'étudiant possède déjà une carte, l'ancienne est désactivée.
     *
     * REQUEST :
     * {
     *   "student_id": 42,
     *   "card_number": "F4A92B1C00"
     * }
     */
    public function assign(Request $request, int $universityId)
    {
        $adminUser = $request->user();
        if (!$adminUser || $adminUser->role !== 'admin') {
            return response()->json(['message' => 'Accès réservé aux administrateurs.'], 403);
        }

        // Pré-charger la carte existante de cet étudiant pour l'exclure du contrôle d'unicité
        $studentId   = $request->input('student_id');
        $existingId  = RfidCard::where('etudiant_id', $studentId)->value('id');

        $validated = $request->validate([
            'student_id'  => "required|integer|exists:etudiants,id",
            'card_number' => [
                'required', 'string', 'max:50',
                // Ignorer la propre carte de l'étudiant lors du remplacement
                \Illuminate\Validation\Rule::unique('cartes_rfid', 'numero_carte')
                    ->ignore($existingId),
            ],
        ], [
            'card_number.unique' => 'Ce numéro de carte est déjà attribué à un autre étudiant.',
        ]);

        $student = \App\Models\Student::where('id', $validated['student_id'])
            ->where('universite_id', $universityId)
            ->firstOrFail();

        $newCardNumber = strtoupper(trim($validated['card_number']));
        $isReplacement = false;

        // Un étudiant = une seule carte. Si elle existe déjà → on la met à jour
        // (évite la violation de contrainte UNIQUE sur etudiant_id)
        $existing = RfidCard::where('etudiant_id', $student->id)->first();

        if ($existing) {
            $isReplacement = true;
            $existing->update([
                'numero_carte'         => $newCardNumber,
                'type_carte'           => 'student',
                'est_active'           => true,
                'assignee_le'          => now(),
                'desactivee_le'        => null,
                'raison_desactivation' => null,
            ]);
            $card = $existing->fresh();
        } else {
            $card = RfidCard::create([
                'universite_id' => $universityId,
                'etudiant_id'   => $student->id,
                'numero_carte'  => $newCardNumber,
                'type_carte'    => 'student',
                'est_active'    => true,
                'assignee_le'   => now(),
            ]);
        }

        AuditLog::create([
            'universite_id'     => $universityId,
            'utilisateur_id'    => $adminUser->id,
            'type_ressource'    => 'RfidCard',
            'id_ressource'      => $card->id,
            'action'            => 'assign_rfid',
            'nouvelles_valeurs' => [
                'student_id'   => $student->id,
                'card_number'  => $card->numero_carte,
                'replaced_old' => $isReplacement,
            ],
            'adresse_ip'        => $request->ip(),
        ]);

        return response()->json([
            'message' => $isReplacement
                ? 'Carte RFID remplacée avec succès.'
                : 'Carte RFID attribuée avec succès.',
            'card'    => [
                'id'          => $card->id,
                'card_number' => $card->numero_carte,
                'is_active'   => $card->est_active,
                'assigned_at' => $card->assignee_le,
                'student'     => [
                    'id'                  => $student->id,
                    'name'                => $student->user?->full_name ?? $student->numero_matricule,
                    'registration_number' => $student->numero_matricule,
                ],
            ],
        ], 201);
    }

    /**
     * PATCH /api/universities/{universityId}/rfid/{cardId}/toggle
     *
     * Activer ou désactiver une carte RFID.
     *
     * REQUEST (optionnel) :
     * { "reason": "Carte signalée perdue" }
     */
    public function toggleCard(Request $request, int $universityId, string $cardId)
    {
        $adminUser = $request->user();
        if (!$adminUser || $adminUser->role !== 'admin') {
            return response()->json(['message' => 'Accès réservé aux administrateurs.'], 403);
        }

        $card = RfidCard::where('id', $cardId)
            ->where('universite_id', $universityId)
            ->firstOrFail();

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        if ($card->est_active) {
            $card->deactivate($validated['reason'] ?? 'Désactivée par l\'administrateur');
            $message = 'Carte RFID désactivée.';
        } else {
            $card->update([
                'est_active'           => true,
                'desactivee_le'        => null,
                'raison_desactivation' => null,
            ]);
            $message = 'Carte RFID réactivée.';
        }

        AuditLog::create([
            'universite_id'     => $universityId,
            'utilisateur_id'    => $adminUser->id,
            'type_ressource'    => 'RfidCard',
            'id_ressource'      => $card->id,
            'action'            => $card->est_active ? 'reactivate_rfid' : 'deactivate_rfid',
            'nouvelles_valeurs' => [
                'is_active' => $card->fresh()->est_active,
                'reason'    => $validated['reason'] ?? null,
            ],
            'adresse_ip'        => $request->ip(),
        ]);

        return response()->json([
            'message' => $message,
            'card'    => [
                'id'          => $card->id,
                'card_number' => $card->numero_carte,
                'is_active'   => $card->fresh()->est_active,
            ],
        ]);
    }

    /**
     * DELETE /api/universities/{universityId}/rfid/{cardId}
     *
     * Supprimer définitivement une carte (perte/vol).
     */
    public function deleteCard(Request $request, int $universityId, string $cardId)
    {
        $adminUser = $request->user();
        if (!$adminUser || $adminUser->role !== 'admin') {
            return response()->json(['message' => 'Accès réservé aux administrateurs.'], 403);
        }

        $card = RfidCard::where('id', $cardId)
            ->where('universite_id', $universityId)
            ->firstOrFail();

        AuditLog::create([
            'universite_id'     => $universityId,
            'utilisateur_id'    => $adminUser->id,
            'type_ressource'    => 'RfidCard',
            'id_ressource'      => $card->id,
            'action'            => 'delete_rfid',
            'anciennes_valeurs' => [
                'card_number' => $card->numero_carte,
                'student_id'  => $card->etudiant_id,
            ],
            'adresse_ip'        => $request->ip(),
        ]);

        $card->delete();

        return response()->json(['message' => 'Carte RFID supprimée.']);
    }

    /**
     * GET /api/universities/{universityId}/rfid/logs
     *
     * Historique des accès RFID (admin uniquement)
     *
     * QUERY PARAMS :
     * - status=granted|refused
     * - access_type=normal|admin_scan|admin_override
     * - date_from=2026-01-01
     * - date_to=2026-03-01
     * - classroom_id=1
     * - student_id=42
     * - limit=100
     * - offset=0
     */
    public function logs(Request $request)
    {
        $universityId = $request->route('universityId');
        $limit = min($request->query('limit', 100), 500);
        $offset = $request->query('offset', 0);

        $query = AccessLog::where('universite_id', $universityId);

        // Filtres
        if ($status = $request->query('status')) {
            $query->where('statut', $status);
        }
        if ($accessType = $request->query('access_type')) {
            $query->where('type_acces', $accessType);
        }
        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('scanne_le', '>=', $dateFrom);
        }
        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('scanne_le', '<=', $dateTo);
        }
        if ($classroomId = $request->query('classroom_id')) {
            $query->where('salle_id', $classroomId);
        }
        if ($studentId = $request->query('student_id')) {
            $query->whereHas('rfidCard', fn($q) => $q->where('etudiant_id', $studentId));
        }

        $total = $query->count();
        $logs = $query->with(['rfidCard.student.user', 'rfidCard.user', 'classroom'])
                      ->orderBy('scanne_le', 'desc')
                      ->limit($limit)
                      ->offset($offset)
                      ->get()
                      ->map(function ($log) {
                          $isAdmin = $log->rfidCard && $log->rfidCard->isAdminCard();
                          return [
                              'id' => $log->id,
                              'scanned_at' => $log->scanne_le,
                              'status' => $log->statut,
                              'access_type' => $log->type_acces ?? 'normal',
                              'reason' => $log->raison_refus,
                              'is_admin_override' => $log->est_override_admin ?? false,
                              'override_reason' => $log->raison_override,
                              'person' => $isAdmin
                                  ? ($log->rfidCard->user->full_name ?? 'Admin')
                                  : ($log->rfidCard->student->user->full_name ?? 'Inconnu'),
                              'person_type' => $isAdmin ? 'admin' : 'student',
                              'classroom' => $log->classroom->nom ?? null,
                          ];
                      });

        // Statistiques
        $baseQuery = AccessLog::where('universite_id', $universityId);
        $grantedCount = (clone $baseQuery)->where('statut', 'granted')->count();
        $refusedCount = (clone $baseQuery)->where('statut', 'refused')->count();
        $adminOverrideCount = (clone $baseQuery)->where('est_override_admin', true)->count();
        $totalAll = $grantedCount + $refusedCount;

        return response()->json([
            'logs' => $logs,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
            'statistics' => [
                'granted_count' => $grantedCount,
                'refused_count' => $refusedCount,
                'admin_override_count' => $adminOverrideCount,
                'success_rate' => $totalAll > 0
                    ? round(($grantedCount / $totalAll) * 100, 2) . '%'
                    : '0%',
            ],
        ]);
    }
}
