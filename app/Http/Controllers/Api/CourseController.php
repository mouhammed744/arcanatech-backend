<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * CourseController - Gestion des cours
 * 
 * VISIBILITÉ :
 * - Tous : lister et voir les détails des cours de leur université
 * - Enseignant : voir uniquement ses cours
 * - Admin : tous les cours de l'université
 */
class CourseController extends Controller
{
    /**
     * GET /api/universities/{universityId}/courses
     * 
     * Lister les cours de l'université
     * 
     * QUERY PARAMS :
     * - level=L1|L2|L3          (filter)
     * - semester=1|2             (filter)
     * - teacher_id=1             (filter)
     * - search=Calculus           (search par nom/code)
     * - limit=50
     * - offset=0
     * 
     * RESPONSE :
     * {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Calculus I",
     *       "code": "MATH101",
     *       "description": "Differential and integral calculus",
     *       "credits": 6,
     *       "level": "L1",
     *       "semester": 1,
     *       "max_lateness_minutes": 5,
     *       "teacher": {
     *         "id": 1,
     *         "name": "Jean Dupont",
     *         "specialty": "Mathematics"
     *       },
     *       "enrollment_count": 42,
     *       "sessions_count": 12
     *     }
     *   ],
     *   "pagination": {...}
     * }
     */
    public function index(Request $request)
    {
        $universityId = $request->route('universityId');
        $user = auth()->user();
        $authenticatedUser = \App\Models\User::find($user->id);

        // ===== Filtres =====
        $query = Course::where('universite_id', $universityId);

        if ($request->has('level')) {
            $query->where('niveau', $request->query('level'));
        }

        if ($request->has('semester')) {
            $query->where('semestre', $request->query('semester'));
        }

        if ($request->has('search')) {
            $searchTerm = '%' . $request->query('search') . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nom', 'like', $searchTerm)
                  ->orWhere('code', 'like', $searchTerm)
                  ->orWhere('description', 'like', $searchTerm);
            });
        }

        if ($request->has('teacher_id')) {
            $query->where('enseignant_id', $request->query('teacher_id'));
        }

        // ===== AUTORISATION =====
        // Les enseignants voient uniquement leurs cours
        if ($authenticatedUser->teacher) {
            $query->where('enseignant_id', $authenticatedUser->teacher->id);
        }

        // ===== Pagination =====
        $limit = min($request->query('limit', 50), 100);
        $offset = $request->query('offset', 0);
        $total = $query->count();

        // ===== Récupérer les données =====
        $courses = $query->with([
            'teacher.user',
            'courseEnrollments',
            'timetableEntries',
        ])
            ->orderBy('niveau')
            ->orderBy('nom')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'name' => $course->nom,
                    'code' => $course->code,
                    'description' => $course->description,
                    'credits' => $course->credits,
                    'level' => $course->niveau,
                    'semester' => $course->semestre,
                    'max_lateness_minutes' => $course->minutes_retard_max,
                    'teacher' => $course->teacher ? [
                        'id'        => $course->teacher->id,
                        'name'      => $course->teacher->user?->full_name ?? 'Enseignant inconnu',
                        'specialty' => $course->teacher->specialite,
                    ] : null,
                    'enrollment_count' => $course->courseEnrollments->count(),
                    'sessions_count' => $course->timetableEntries->count(),
                ];
            });

        return response()->json([
            'data' => $courses,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    /**
     * GET /api/universities/{universityId}/courses/{id}
     * 
     * Détail d'un cours avec tous les étudiants inscrits
     */
    public function show(Request $request, $universityId, $courseId)
    {
        $course = Course::where('universite_id', $universityId)
            ->where('id', $courseId)
            ->with([
                'teacher.user',
                'timetableEntries.classroom',
                'timetableEntries.attendances',
                'courseEnrollments.student.user',
            ])
            ->firstOrFail();

        // Calcul des statistiques de présence
        $attendances = $course->timetableEntries->flatMap->attendances;
        $totalAttendances = $attendances->count();
        $presentCount = $attendances->where('statut', 'present')->count();
        $lateCount = $attendances->where('statut', 'late')->count();
        $absentCount = $attendances->where('statut', 'absent')->count();

        return response()->json([
            'id' => $course->id,
            'name' => $course->nom,
            'code' => $course->code,
            'description' => $course->description,
            'credits' => $course->credits,
            'level' => $course->niveau,
            'semester' => $course->semestre,
            'max_lateness_minutes' => $course->minutes_retard_max,
            'teacher' => $course->teacher ? [
                'id'        => $course->teacher->id,
                'name'      => $course->teacher->user?->full_name ?? 'Enseignant inconnu',
                'specialty' => $course->teacher->specialite,
                'grade'     => $course->teacher->grade,
                'contact'   => $course->teacher->user?->email ?? '',
            ] : null,
            'timetable' => $course->timetableEntries->map(function ($entry) {
                return [
                    'id'         => $entry->id,
                    'day'        => $entry->jour_semaine,
                    'start_time' => $entry->heure_debut,
                    'end_time'   => $entry->heure_fin,
                    'classroom'  => $entry->classroom?->nom ?? '—',
                    'capacity'   => $entry->classroom?->max_capacity ?? 0,
                ];
            }),
            'students' => $course->courseEnrollments->map(function ($enrollment) {
                return [
                    'id'                  => $enrollment->student?->id,
                    'name'                => $enrollment->student?->user?->full_name ?? 'Étudiant inconnu',
                    'registration_number' => $enrollment->student?->numero_matricule ?? '—',
                ];
            }),
            'statistics' => [
                'total_attendances' => $totalAttendances,
                'present_count' => $presentCount,
                'late_count' => $lateCount,
                'absent_count' => $absentCount,
                'presence_rate' => $totalAttendances > 0
                    ? round((($presentCount + $lateCount) / $totalAttendances) * 100, 2)
                    : 0,
            ],
        ]);
    }

    /**
     * POST /api/universities/{universityId}/courses
     * 
     * Créer un cours (admin + teacher uniquement)
     * 
     * REQUEST :
     * {
     *   "name": "Calculus II",
     *   "code": "MATH102",
     *   "description": "...",
     *   "credits": 6,
     *   "level": "L1",
     *   "semester": 2,
     *   "teacher_id": 1,
     *   "max_lateness_minutes": 5
     * }
     */
    public function store(Request $request, $universityId)
    {
        $user = auth()->user();

        // Vérifier les autorisations
        if (!$user->hasRole('admin') && !$user->teacher) {
            abort(403, 'Only admins and teachers can create courses');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:cours,code',
            'description' => 'nullable|string',
            'credits' => 'required|integer|min:1|max:30',
            'level' => 'required|in:L1,L2,L3',
            'semester' => 'required|in:1,2',
            'teacher_id' => 'required|exists:enseignants,id',
            'max_lateness_minutes' => 'nullable|integer|min:0|max:60',
        ]);

        // Un enseignant ne peut créer que pour lui-même
        if ($user->teacher && $user->teacher->id !== $validated['teacher_id']) {
            abort(403, 'Teachers can only create courses for themselves');
        }

        $course = Course::create([
            'universite_id'       => $universityId,
            'nom'                 => $validated['name'],
            'code'                => $validated['code'],
            'description'         => $validated['description'],
            'credits'             => $validated['credits'],
            'niveau'              => $validated['level'],
            'semestre'            => $validated['semester'],
            'enseignant_id'       => $validated['teacher_id'],
            'minutes_retard_max'  => $validated['max_lateness_minutes'] ?? 5,
        ]);

        // Audit log
        AuditLog::create([
            'universite_id'     => $universityId,
            'utilisateur_id'    => $user->id,
            'type_ressource'    => 'Course',
            'id_ressource'      => $course->id,
            'action'            => 'create',
            'nouvelles_valeurs' => $course->toArray(),
            'adresse_ip'        => request()->ip(),
        ]);

        return response()->json([
            'message' => 'Course created',
            'course' => $course->load('teacher'),
        ], 201);
    }

    /**
     * PUT /api/universities/{universityId}/courses/{id}
     * 
     * Modifier un cours (admin + teacher-owner uniquement)
     */
    public function update(Request $request, $universityId, $courseId)
    {
        $user = auth()->user();
        $course = Course::where('universite_id', $universityId)
            ->where('id', $courseId)
            ->firstOrFail();

        // Vérifier les autorisations
        if (!$user->hasRole('admin') && $user->teacher?->id !== $course->enseignant_id) {
            abort(403, 'Not authorized to update this course');
        }

        $oldValues = $course->toArray();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'credits' => 'nullable|integer|min:1|max:30',
            'max_lateness_minutes' => 'nullable|integer|min:0|max:60',
        ]);

        // Mapper les clés anglaises vers les colonnes françaises
        $frenchData = array_filter([
            'nom'                => $validated['name'] ?? null,
            'description'        => $validated['description'] ?? null,
            'credits'            => $validated['credits'] ?? null,
            'minutes_retard_max' => $validated['max_lateness_minutes'] ?? null,
        ], fn($v) => $v !== null);

        $course->update($frenchData);

        // Audit log
        AuditLog::create([
            'universite_id'     => $universityId,
            'utilisateur_id'    => $user->id,
            'type_ressource'    => 'Course',
            'id_ressource'      => $course->id,
            'action'            => 'update',
            'anciennes_valeurs' => $oldValues,
            'nouvelles_valeurs' => $course->toArray(),
            'adresse_ip'        => request()->ip(),
        ]);

        return response()->json([
            'message' => 'Course updated',
            'course' => $course->load('teacher'),
        ]);
    }

    /**
     * DELETE /api/universities/{universityId}/courses/{id}
     * 
     * Supprimer un cours (admin only)
     */
    public function destroy(Request $request, $universityId, $courseId)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Only admins can delete courses');
        }

        $course = Course::where('universite_id', $universityId)
            ->where('id', $courseId)
            ->firstOrFail();

        $courseData = $course->toArray();

        // Vérifier qu'il n'y a pas d'inscriptions
        if ($course->courseEnrollments()->exists()) {
            abort(409, 'Cannot delete a course with enrolled students');
        }

        $course->delete();

        // Audit log
        AuditLog::create([
            'universite_id'     => $universityId,
            'utilisateur_id'    => $user->id,
            'type_ressource'    => 'Course',
            'id_ressource'      => $courseId,
            'action'            => 'delete',
            'anciennes_valeurs' => $courseData,
            'adresse_ip'        => request()->ip(),
        ]);

        return response()->json(['message' => 'Course deleted']);
    }
}
