<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\FcmToken;
use App\Models\Filiere;
use App\Models\Student;
use App\Models\TimetableEntry;
use App\Models\AuditLog;
use App\Notifications\ScheduleNotification;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ScheduleController — Gestion des emplois du temps
 *
 * ENDPOINTS :
 * GET    /api/universities/{uniId}/schedule              → liste des séances
 * POST   /api/universities/{uniId}/schedule              → créer une séance
 * PUT    /api/universities/{uniId}/schedule/{id}         → modifier
 * DELETE /api/universities/{uniId}/schedule/{id}         → supprimer
 */
class ScheduleController extends Controller
{
    // ──────────────────────────────────────────
    // GET /universities/{uniId}/schedule
    // ──────────────────────────────────────────
    public function index(Request $request, int $universityId): JsonResponse
    {
        $query = TimetableEntry::with(['course.teacher.user', 'classroom'])
            ->where('universite_id', $universityId);

        // Filtre par filière
        if ($request->filled('filiere_id')) {
            $courseIds = DB::table('filiere_cours')
                ->where('filiere_id', (int) $request->input('filiere_id'))
                ->pluck('cours_id');
            $query->whereIn('cours_id', $courseIds);
        }

        // Filtre par jour de la semaine
        if ($request->filled('day')) {
            $query->where('jour_semaine', $request->input('day'));
        }

        $entries = $query->orderBy('jour_semaine')->orderBy('heure_debut')->get();

        $data = $entries->map(fn($e) => $this->formatEntry($e));

        return response()->json(['data' => $data]);
    }

    // ──────────────────────────────────────────
    // POST /universities/{uniId}/schedule
    // ──────────────────────────────────────────
    public function store(Request $request, int $universityId): JsonResponse
    {
        $validated = $request->validate([
            'course_id'          => 'required|integer|exists:cours,id',
            'classroom_id'       => 'required|integer|exists:salles,id',
            'day_of_week'        => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'start_time'         => 'required|date_format:H:i',
            'end_time'           => 'required|date_format:H:i|after:start_time',
            'session_type'       => 'nullable|string|in:CM,TD,TP,Projet,lecture',
            'recurrence_pattern' => 'nullable|string|in:weekly,biweekly,once',
            'start_date'         => 'required|date',
            'end_date'           => 'nullable|date|after_or_equal:start_date',
            'filiere_ids'        => 'nullable|array',
            'filiere_ids.*'      => 'integer|exists:filieres,id',
        ]);

        // Vérifier que le cours appartient à l'université
        Course::where('id', $validated['course_id'])
              ->where('universite_id', $universityId)
              ->firstOrFail();

        $entry = TimetableEntry::create([
            'universite_id'  => $universityId,
            'cours_id'       => $validated['course_id'],
            'salle_id'       => $validated['classroom_id'],
            'jour_semaine'   => $validated['day_of_week'],
            'heure_debut'    => $validated['start_time'],
            'heure_fin'      => $validated['end_time'],
            'type_seance'    => $validated['session_type'] ?? 'CM',
            'recurrence'     => $validated['recurrence_pattern'] ?? 'weekly',
            'date_debut'     => $validated['start_date'],
            'date_fin'       => $validated['end_date'] ?? null,
        ]);

        // Associer le cours aux filières choisies
        if (!empty($validated['filiere_ids'])) {
            foreach ($validated['filiere_ids'] as $filiereId) {
                DB::table('filiere_cours')->insertOrIgnore([
                    'filiere_id' => $filiereId,
                    'cours_id'   => $validated['course_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $entry->load(['course.teacher.user', 'classroom']);

        AuditLog::create([
            'universite_id'     => $universityId,
            'utilisateur_id'    => auth()->id(),
            'type_ressource'    => 'TimetableEntry',
            'id_ressource'      => $entry->id,
            'action'            => 'create_schedule_entry',
            'adresse_ip'        => $request->ip(),
            'nouvelles_valeurs' => $validated,
        ]);

        // ── Notifications aux étudiants des filières concernées ──
        $filiereIds = $validated['filiere_ids'] ?? [];
        if (empty($filiereIds)) {
            // Récupérer les filières déjà liées au cours
            $filiereIds = DB::table('filiere_cours')
                ->where('cours_id', $validated['course_id'])
                ->pluck('filiere_id')
                ->toArray();
        }

        if (!empty($filiereIds)) {
            $this->notifyStudents(
                filiereIds: $filiereIds,
                type:       'schedule_created',
                title:      '📅 Nouvelle séance planifiée',
                body:       sprintf(
                    '%s — %s de %s à %s',
                    $entry->course?->nom ?? 'Cours',
                    __($entry->jour_semaine),
                    $entry->heure_debut,
                    $entry->heure_fin
                ),
                entry:      $entry,
            );
        }

        return response()->json([
            'message' => 'Séance créée avec succès.',
            'entry'   => $this->formatEntry($entry),
        ], 201);
    }

    // ──────────────────────────────────────────
    // PUT /universities/{uniId}/schedule/{id}
    // ──────────────────────────────────────────
    public function update(Request $request, int $universityId, int $entryId): JsonResponse
    {
        $entry = TimetableEntry::where('universite_id', $universityId)->findOrFail($entryId);

        $validated = $request->validate([
            'course_id'          => 'sometimes|integer|exists:cours,id',
            'classroom_id'       => 'sometimes|integer|exists:salles,id',
            'day_of_week'        => 'sometimes|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'start_time'         => 'sometimes|date_format:H:i',
            'end_time'           => 'sometimes|date_format:H:i',
            'session_type'       => 'nullable|string|in:CM,TD,TP,Projet,lecture',
            'recurrence_pattern' => 'nullable|string|in:weekly,biweekly,once',
            'start_date'         => 'sometimes|date',
            'end_date'           => 'nullable|date',
        ]);

        $entry->update($validated);
        $entry->load(['course.teacher.user', 'classroom']);

        AuditLog::create([
            'universite_id'     => $universityId,
            'utilisateur_id'    => auth()->id(),
            'type_ressource'    => 'TimetableEntry',
            'id_ressource'      => $entry->id,
            'action'            => 'update_schedule_entry',
            'adresse_ip'        => $request->ip(),
            'nouvelles_valeurs' => $validated,
        ]);

        // ── Notifications modification aux étudiants des filières du cours ──
        $filiereIds = DB::table('filiere_cours')
            ->where('cours_id', $entry->cours_id)
            ->pluck('filiere_id')
            ->toArray();

        if (!empty($filiereIds)) {
            $this->notifyStudents(
                filiereIds: $filiereIds,
                type:       'schedule_updated',
                title:      '✏️ Séance modifiée',
                body:       sprintf(
                    '%s — %s de %s à %s',
                    $entry->course?->nom ?? 'Cours',
                    __($entry->jour_semaine),
                    $entry->heure_debut,
                    $entry->heure_fin
                ),
                entry:      $entry,
            );
        }

        return response()->json([
            'message' => 'Séance mise à jour.',
            'entry'   => $this->formatEntry($entry),
        ]);
    }

    // ──────────────────────────────────────────
    // DELETE /universities/{uniId}/schedule/{id}
    // ──────────────────────────────────────────
    public function destroy(Request $request, int $universityId, int $entryId): JsonResponse
    {
        $entry = TimetableEntry::where('universite_id', $universityId)->findOrFail($entryId);
        $entry->delete();

        AuditLog::create([
            'universite_id'  => $universityId,
            'utilisateur_id' => auth()->id(),
            'type_ressource' => 'TimetableEntry',
            'id_ressource'   => $entryId,
            'action'         => 'delete_schedule_entry',
            'adresse_ip'     => $request->ip(),
        ]);

        return response()->json(['message' => 'Séance supprimée.']);
    }

    // ──────────────────────────────────────────
    // Helper : notifier les étudiants des filières
    // ──────────────────────────────────────────
    private function notifyStudents(
        array          $filiereIds,
        string         $type,
        string         $title,
        string         $body,
        TimetableEntry $entry,
    ): void {
        // Récupérer tous les étudiants actifs des filières concernées
        $students = Student::whereIn('filiere_id', $filiereIds)
            ->with('user')
            ->get();

        if ($students->isEmpty()) {
            return;
        }

        $payload = [
            'type'        => $type,
            'entry_id'    => $entry->id,
            'course_name' => $entry->course?->nom,
            'course_code' => $entry->course?->code,
            'day'         => $entry->jour_semaine,
            'start_time'  => $entry->heure_debut,
            'end_time'    => $entry->heure_fin,
            'room'        => $entry->classroom?->nom,
            'session_type'=> $entry->type_seance,
        ];

        $notification = new ScheduleNotification(
            title:   $title,
            body:    $body,
            type:    $type,
            payload: $payload,
        );

        // Collecter tous les tokens FCM des étudiants
        $userIds     = $students->pluck('utilisateur_id')->filter()->toArray();
        $fcmTokens   = FcmToken::whereIn('utilisateur_id', $userIds)->pluck('token')->toArray();

        foreach ($students as $student) {
            if ($student->user) {
                // Notification BDD (offline + online)
                $student->user->notify($notification);
            }
        }

        // Push FCM (appareils connectés)
        if (!empty($fcmTokens)) {
            app(FcmService::class)->send($fcmTokens, $title, $body, $payload);
        }
    }

    // ──────────────────────────────────────────
    // Helper : formater un TimetableEntry
    // ──────────────────────────────────────────
    private function formatEntry(TimetableEntry $e): array
    {
        $teacher     = $e->course?->teacher?->user;
        $teacherName = $teacher
            ? trim(($teacher->prenom ?? '') . ' ' . ($teacher->nom ?? ''))
            : null;

        return [
            'id'                 => $e->id,
            'courseId'           => $e->cours_id,
            'courseName'         => $e->course?->nom ?? '',
            'courseCode'         => $e->course?->code ?? '',
            'courseType'         => $e->type_seance,
            'teacherId'          => $e->course?->enseignant_id,
            'teacherName'        => $teacherName,
            'roomId'             => $e->salle_id,
            'roomName'           => $e->classroom?->nom ?? '',
            'dayOfWeek'          => $e->jour_semaine,
            'startTime'          => $e->heure_debut,
            'endTime'            => $e->heure_fin,
            'recurrencePattern'  => $e->recurrence,
            'startDate'          => $e->date_debut?->toDateString(),
            'endDate'            => $e->date_fin?->toDateString(),
        ];
    }
}
