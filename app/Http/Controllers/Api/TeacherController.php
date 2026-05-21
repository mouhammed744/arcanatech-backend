<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * TeacherController - Gestion des enseignants
 * 
 * VISIBILITÉ :
 * - Admin : voir tous les enseignants
 * - Enseignant : voir son profil
 */
class TeacherController extends Controller
{
    /**
     * GET /api/universities/{universityId}/teachers
     * 
     * Lister les enseignants
     * 
     * QUERY PARAMS :
     * - specialty=Mathematics    (filter)
     * - grade=Professor|Lecturer (filter)
     * - search=Jean Dupont       (search par nom)
     * - limit=50
     * - offset=0
     * 
     * RESPONSE :
     * {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Jean Dupont",
     *       "email": "jean@university.fr",
     *       "specialty": "Mathematics",
     *       "grade": "Professor",
     *       "hire_date": "2020-09-01",
     *       "courses_count": 3,
     *       "total_students": 85
     *     }
     *   ],
     *   "pagination": {...}
     * }
     */
    public function index(Request $request)
    {
        $universityId = $request->route('universityId');

        // ===== Filtres =====
        $query = Teacher::where('universite_id', $universityId);

        if ($request->has('specialty')) {
            $query->where('specialite', $request->query('specialty'));
        }

        if ($request->has('grade')) {
            $query->where('grade', $request->query('grade'));
        }

        if ($request->has('search')) {
            $searchTerm = '%' . $request->query('search') . '%';
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('prenom', 'like', $searchTerm)
                  ->orWhere('nom', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm);
            });
        }

        // ===== Pagination =====
        $limit = min($request->query('limit', 50), 100);
        $offset = $request->query('offset', 0);
        $total = $query->count();

        // ===== Récupérer les données =====
        $teachers = $query->with([
            'user',
            'courses',
        ])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function ($teacher) {
                // Compter les étudiants uniques inscrits à ses cours
                $totalStudents = $teacher->courses
                    ->flatMap(fn($course) => $course->courseEnrollments)
                    ->unique('etudiant_id')
                    ->count();

                return [
                    'id' => $teacher->id,
                    'name' => $teacher->user->full_name,
                    'email' => $teacher->user->email,
                    'phone' => $teacher->user->telephone,
                    'specialty' => $teacher->specialite,
                    'grade' => $teacher->grade,
                    'hire_date' => $teacher->date_embauche,
                    'courses_count' => $teacher->courses->count(),
                    'total_students' => $totalStudents,
                ];
            });

        return response()->json([
            'data' => $teachers,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    /**
     * GET /api/universities/{universityId}/teachers/{id}
     * 
     * Détail d'un enseignant avec ses cours et statistiques
     */
    public function show(Request $request, $universityId, $teacherId)
    {
        $teacher = Teacher::where('universite_id', $universityId)
            ->where('id', $teacherId)
            ->with([
                'user',
                'courses.timetableEntries',
                'courses.courseEnrollments.student.user',
            ])
            ->firstOrFail();

        // Calculer les statistiques de présence par cours
        $courseStats = [];
        foreach ($teacher->courses as $course) {
            $attendances = \App\Models\Attendance::whereHas(
                'timetableEntry',
                fn($q) => $q->where('cours_id', $course->id)
            )->get();

            $totalAttendances = $attendances->count();
            $presentCount = $attendances->where('statut', 'present')->count();
            $lateCount = $attendances->where('statut', 'late')->count();

            if ($totalAttendances > 0) {
                $courseStats[] = [
                    'id' => $course->id,
                    'name' => $course->nom,
                    'code' => $course->code,
                    'level' => $course->niveau,
                    'credits' => $course->credits,
                    'enrolled_students' => $course->courseEnrollments->count(),
                    'sessions' => $course->timetableEntries->count(),
                    'attendance_records' => $totalAttendances,
                    'attendance_rate' => round(
                        (($presentCount + $lateCount) / $totalAttendances) * 100,
                        2
                    ),
                ];
            }
        }

        return response()->json([
            'id' => $teacher->id,
            'user' => [
                'id' => $teacher->user->id,
                'name' => $teacher->user->full_name,
                'email' => $teacher->user->email,
                'phone' => $teacher->user->telephone,
                'address' => $teacher->user->adresse,
            ],
            'specialty' => $teacher->specialite,
            'grade' => $teacher->grade,
            'hire_date' => $teacher->date_embauche,
            'courses' => $courseStats,
            'statistics' => [
                'total_courses' => $teacher->courses->count(),
                'total_students' => $teacher->courses
                    ->flatMap(fn($c) => $c->courseEnrollments)
                    ->unique('etudiant_id')
                    ->count(),
                'total_sessions' => $teacher->courses
                    ->sum(fn($c) => $c->timetableEntries->count()),
            ],
        ]);
    }

    /**
     * POST /api/universities/{universityId}/teachers
     * Créer un enseignant (crée un User + Teacher)
     */
    public function store(Request $request, int $universityId)
    {
        $validated = $request->validate([
            'firstName'    => 'required|string|max:100',
            'lastName'     => 'required|string|max:100',
            'email'        => 'required|email|unique:utilisateurs,email',
            'phone'        => 'nullable|string|max:20',
            'specialty'    => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'universite_id' => $universityId,
                'prenom'        => $validated['firstName'],
                'nom'           => $validated['lastName'],
                'email'         => $validated['email'],
                'telephone'     => $validated['phone'] ?? null,
                'role'          => 'teacher',
                'password'      => Hash::make(Str::random(16)),
            ]);

            $teacher = Teacher::create([
                'universite_id' => $universityId,
                'utilisateur_id' => $user->id,
                'specialite'    => $validated['specialty'] ?? null,
                'statut'        => 'active',
            ]);

            AuditLog::create([
                'universite_id'     => $universityId,
                'utilisateur_id'    => $request->user()->id,
                'type_ressource'    => 'Teacher',
                'id_ressource'      => $teacher->id,
                'action'            => 'create',
                'nouvelles_valeurs' => $validated,
                'adresse_ip'        => $request->ip(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Enseignant créé avec succès',
                'teacher' => [
                    'id'        => $teacher->id,
                    'name'      => $user->full_name,
                    'email'     => $user->email,
                    'phone'     => $user->telephone,
                    'specialty' => $teacher->specialite,
                ],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de la création', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/universities/{universityId}/teachers/{teacherId}
     * Modifier un enseignant
     */
    public function update(Request $request, int $universityId, int $teacherId)
    {
        $teacher = Teacher::where('universite_id', $universityId)->findOrFail($teacherId);

        $validated = $request->validate([
            'firstName' => 'sometimes|string|max:100',
            'lastName'  => 'sometimes|string|max:100',
            'email'     => 'sometimes|email|unique:utilisateurs,email,' . $teacher->utilisateur_id,
            'phone'     => 'nullable|string|max:20',
            'specialty' => 'nullable|string|max:255',
        ]);

        $teacher->user->update(array_filter([
            'prenom'    => $validated['firstName'] ?? null,
            'nom'       => $validated['lastName'] ?? null,
            'email'     => $validated['email'] ?? null,
            'telephone' => $validated['phone'] ?? null,
        ], fn($v) => $v !== null));

        $teacher->update(['specialite' => $validated['specialty'] ?? $teacher->specialite]);

        return response()->json([
            'message' => 'Enseignant mis à jour',
            'teacher' => [
                'id'        => $teacher->id,
                'name'      => $teacher->user->full_name,
                'email'     => $teacher->user->email,
                'phone'     => $teacher->user->telephone,
                'specialty' => $teacher->specialite,
            ],
        ]);
    }

    /**
     * DELETE /api/universities/{universityId}/teachers/{teacherId}
     * Supprimer un enseignant (soft delete)
     */
    public function destroy(Request $request, int $universityId, int $teacherId)
    {
        $teacher = Teacher::where('universite_id', $universityId)->findOrFail($teacherId);
        $teacher->delete();

        AuditLog::create([
            'universite_id'  => $universityId,
            'utilisateur_id' => $request->user()->id,
            'type_ressource' => 'Teacher',
            'id_ressource'   => $teacherId,
            'action'         => 'delete',
            'adresse_ip'     => $request->ip(),
        ]);

        return response()->json(['message' => 'Enseignant supprimé']);
    }
}
