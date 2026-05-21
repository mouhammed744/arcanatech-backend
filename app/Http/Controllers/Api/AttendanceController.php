<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * AttendanceController - Gestion des présences/absences/retards
 * 
 * VISIBILITÉ DES DONNÉES (Policy-based) :
 * - Étudiant : voir uniquement ses propres présences
 * - Enseignant : voir présences des étudiants inscrits à ses cours
 * - Admin : voir toutes les présences de l'université
 */
class AttendanceController extends Controller
{
    /**
     * GET /api/universities/{universityId}/attendances
     * 
     * Lister les présences avec filtres
     * 
     * QUERY PARAMS :
     * - student_id=1                     (filter)
     * - status=present|late|absent       (filter)
     * - course_id=1                      (filter)
     * - date_from=2026-02-20             (date range)
     * - date_to=2026-02-27
     * - limit=50
     * - offset=0
     * - sort_by=scanned_at|status        (default: scanned_at desc)
     * 
     * RESPONSE :
     * {
     *   "data": [
     *     {
     *       "id": 1,
     *       "student_id": 1,
     *       "student_name": "Jean Dupont",
     *       "course_id": 1,
     *       "course_name": "Calculus I",
     *       "status": "present",
     *       "scanned_at": "2026-02-23T09:03:00Z",
     *       "late_minutes": 0,
     *       "notes": null,
     *       "verification_method": "rfid"
     *     }
     *   ],
     *   "pagination": {...},
     *   "statistics": {
     *     "total_count": 150,
     *     "present_count": 130,
     *     "late_count": 15,
     *     "absent_count": 5,
     *     "presence_rate": 96.67
     *   }
     * }
     */
    public function index(Request $request)
    {
        $universityId = $request->route('universityId');
        $user = auth()->user();

        // Récupérer l'utilisateur depuis BD pour accéder aux relations
        $authenticatedUser = \App\Models\User::find($user->id);

        // ===== Filtres =====
        $query = Attendance::where('universite_id', $universityId);

        // Filter par statut
        if ($request->has('status')) {
            $query->where('statut', $request->query('status'));
        }

        // Filter par étudiant
        if ($request->has('student_id')) {
            $studentId = $request->query('student_id');
            $query->where('etudiant_id', $studentId);
        }

        // Filter par cours
        if ($request->has('course_id')) {
            $courseId = $request->query('course_id');
            $query->whereHas('timetableEntry', function ($q) use ($courseId) {
                $q->where('cours_id', $courseId);
            });
        }

        // Filter par date
        if ($request->has('date_from')) {
            $dateFrom = Carbon::parse($request->query('date_from'))->startOfDay();
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($request->has('date_to')) {
            $dateTo = Carbon::parse($request->query('date_to'))->endOfDay();
            $query->where('created_at', '<=', $dateTo);
        }

        // ===== AUTORISATION =====
        // - Enseignant : voir uniquement les présences de ses étudiants
        if ($authenticatedUser->teacher) {
            $teacherCourseIds = $authenticatedUser->teacher->courses()->pluck('cours.id');
            $query->whereHas('timetableEntry', function ($q) use ($teacherCourseIds) {
                $q->whereIn('cours_id', $teacherCourseIds);
            });
        }

        // - Étudiant : voir uniquement ses propres présences
        if ($authenticatedUser->student) {
            $query->where('etudiant_id', $authenticatedUser->student->id);
        }

        // ===== Pagination =====
        $limit = min($request->query('limit', 50), 100);
        $offset = $request->query('offset', 0);
        $total = $query->count();

        // ===== Tri =====
        $sortBy = $request->query('sort_by', 'scanne_le');
        $sortDirection = $request->query('sort_direction', 'desc');

        if (in_array($sortBy, ['scanne_le', 'statut', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // ===== Récupérer les données =====
        $attendances = $query->with([
            'student.user',
            'timetableEntry.course',
        ])
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function ($attendance) {
                return [
                    'id'                    => $attendance->id,
                    'student_id'            => $attendance->etudiant_id,
                    'student_name'          => $attendance->student?->user?->full_name ?? 'Étudiant inconnu',
                    'student_registration'  => $attendance->student?->numero_matricule ?? '—',
                    'course_id'             => $attendance->timetableEntry?->cours_id,
                    'course_name'           => $attendance->timetableEntry?->course?->nom ?? 'Cours inconnu',
                    'course_code'           => $attendance->timetableEntry?->course?->code ?? '—',
                    'status'                => $attendance->statut,
                    'scanned_at'            => $attendance->scanne_le,
                    'late_minutes'          => $attendance->notes ? $this->extractLateMinutes($attendance->notes) : 0,
                    'notes'                 => $attendance->notes,
                    'verification_method'   => $attendance->methode_verification,
                ];
            });

        // ===== Statistiques — toujours basées sur l'ensemble de l'université =====
        // Utiliser une requête de base indépendante des filtres pour que
        // present_count + late_count + absent_count == total_count en permanence.
        $statsBase  = Attendance::where('universite_id', $universityId);
        $statsTotal = (clone $statsBase)->count();

        $stats = [
            'total_count'   => $statsTotal,
            'present_count' => (clone $statsBase)->where('statut', 'present')->count(),
            'late_count'    => (clone $statsBase)->where('statut', 'late')->count(),
            'absent_count'  => (clone $statsBase)->where('statut', 'absent')->count(),
            'filtered_count'=> $total,   // Nombre de lignes après filtres (pour la pagination)
        ];

        $stats['presence_rate'] = $statsTotal > 0
            ? round((($stats['present_count'] + $stats['late_count']) / $statsTotal) * 100, 2)
            : 0;

        return response()->json([
            'data' => $attendances,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'pages' => ceil($total / $limit),
            ],
            'statistics' => $stats,
        ]);
    }

    /**
     * GET /api/universities/{universityId}/attendances/{id}
     * 
     * Détail d'une présence
     */
    public function show(Request $request, $universityId, $attendanceId)
    {
        $attendance = Attendance::where('universite_id', $universityId)
            ->where('id', $attendanceId)
            ->with([
                'student.user',
                'timetableEntry.course.teacher.user',
                'timetableEntry.classroom',
            ])
            ->firstOrFail();

        // Vérifier les autorisations
        $user = auth()->user();
        if ($user->student && $user->student->id !== $attendance->etudiant_id) {
            abort(403, 'Not authorized to view this attendance');
        }

        return response()->json([
            'id' => $attendance->id,
            'student' => [
                'id'                  => $attendance->student?->id,
                'name'                => $attendance->student?->user?->full_name ?? 'Étudiant inconnu',
                'registration_number' => $attendance->student?->numero_matricule ?? '—',
            ],
            'course' => [
                'id'      => $attendance->timetableEntry?->course?->id,
                'name'    => $attendance->timetableEntry?->course?->nom ?? 'Cours inconnu',
                'code'    => $attendance->timetableEntry?->course?->code ?? '—',
                'teacher' => $attendance->timetableEntry?->course?->teacher?->user?->full_name ?? 'Enseignant inconnu',
            ],
            'timetable' => [
                'day'        => $attendance->timetableEntry?->jour_semaine,
                'start_time' => $attendance->timetableEntry?->heure_debut,
                'end_time'   => $attendance->timetableEntry?->heure_fin,
                'classroom'  => $attendance->timetableEntry?->classroom?->nom ?? '—',
            ],
            'status'              => $attendance->statut,
            'scanned_at'          => $attendance->scanne_le,
            'notes'               => $attendance->notes,
            'verification_method' => $attendance->methode_verification,
            'created_at'          => $attendance->created_at,
        ]);
    }

    /**
     * POST /api/universities/{universityId}/attendances
     * 
     * Créer une présence manuellement (admin only)
     * 
     * REQUEST :
     * {
     *   "student_id": 1,
     *   "timetable_entry_id": 5,
     *   "status": "present",
     *   "notes": "Manuel entry by admin"
     * }
     */
    public function store(Request $request, $universityId)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Only admins can manually create attendances');
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:etudiants,id',
            'timetable_entry_id' => 'required|exists:seances,id',
            'status' => 'required|in:present,late,absent',
            'notes' => 'nullable|string|max:255',
        ]);

        $attendance = Attendance::create([
            'universite_id'       => $universityId,
            'etudiant_id'         => $validated['student_id'],
            'seance_id'           => $validated['timetable_entry_id'],
            'statut'              => $validated['status'],
            'methode_verification' => 'manual',
            'notes'               => $validated['notes'],
            'scanne_le'           => now(),
        ]);

        // Audit log
        AuditLog::create([
            'universite_id'     => $universityId,
            'utilisateur_id'    => $user->id,
            'type_ressource'    => 'Attendance',
            'id_ressource'      => $attendance->id,
            'action'            => 'create',
            'nouvelles_valeurs' => $attendance->toArray(),
            'adresse_ip'        => request()->ip(),
        ]);

        return response()->json([
            'message' => 'Attendance created',
            'attendance' => $attendance,
        ], 201);
    }

    /**
     * PUT /api/universities/{universityId}/attendances/{id}
     * 
     * Modifier une présence (admin only)
     * 
     * REQUEST :
     * {
     *   "status": "absent",
     *   "notes": "Modified justification"
     * }
     */
    public function update(Request $request, $universityId, $attendanceId)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Only admins can modify attendances');
        }

        $attendance = Attendance::where('universite_id', $universityId)
            ->where('id', $attendanceId)
            ->firstOrFail();

        $oldValues = $attendance->toArray();

        $validated = $request->validate([
            'status' => 'nullable|in:present,late,absent',
            'notes' => 'nullable|string|max:255',
        ]);

        $attendance->update(array_filter($validated));

        // Audit log
        AuditLog::create([
            'universite_id'     => $universityId,
            'utilisateur_id'    => $user->id,
            'type_ressource'    => 'Attendance',
            'id_ressource'      => $attendance->id,
            'action'            => 'update',
            'anciennes_valeurs' => $oldValues,
            'nouvelles_valeurs' => $attendance->toArray(),
            'adresse_ip'        => request()->ip(),
        ]);

        return response()->json([
            'message' => 'Attendance updated',
            'attendance' => $attendance,
        ]);
    }

    /**
     * DELETE /api/universities/{universityId}/attendances/{id}
     * 
     * Supprimer une présence (admin only - rare)
     */
    public function destroy(Request $request, $universityId, $attendanceId)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Only admins can delete attendances');
        }

        $attendance = Attendance::where('universite_id', $universityId)
            ->where('id', $attendanceId)
            ->firstOrFail();

        $attendanceData = $attendance->toArray();

        $attendance->delete();

        // Audit log
        AuditLog::create([
            'universite_id'     => $universityId,
            'utilisateur_id'    => $user->id,
            'type_ressource'    => 'Attendance',
            'id_ressource'      => $attendanceId,
            'action'            => 'delete',
            'anciennes_valeurs' => $attendanceData,
            'adresse_ip'        => request()->ip(),
        ]);

        return response()->json(['message' => 'Attendance deleted']);
    }

    /**
     * Helper : Extraire les minutes de retard des notes
     */
    private function extractLateMinutes(string $notes): int
    {
        if (preg_match('/(\d+)\s*minutes?\s*late/', $notes, $matches)) {
            return (int)$matches[1];
        }

        return 0;
    }
}
