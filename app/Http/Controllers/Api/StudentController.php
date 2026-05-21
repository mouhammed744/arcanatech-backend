<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    /**
     * GET /api/universities/{universityId}/students
     */
    public function index(Request $request)
    {
        $universityId      = $request->route('universityId');
        $user              = auth()->user();
        $authenticatedUser = \App\Models\User::find($user->id);

        $query = Student::where('universite_id', $universityId);

        if ($request->has('level')) {
            $query->where('niveau', $request->query('level'));
        }

        if ($request->has('search')) {
            $searchTerm = '%' . $request->query('search') . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('user', function ($subQ) use ($searchTerm) {
                    $subQ->where('prenom', 'like', $searchTerm)
                         ->orWhere('nom', 'like', $searchTerm)
                         ->orWhere('email', 'like', $searchTerm);
                })
                ->orWhere('numero_matricule', 'like', $searchTerm);
            });
        }

        if ($request->has('course_id')) {
            $courseId = $request->query('course_id');
            $query->whereHas('courseEnrollments', function ($q) use ($courseId) {
                $q->where('cours_id', $courseId);
            });
        }

        if ($request->has('has_rfid_card')) {
            $hasCard = $request->query('has_rfid_card') === 'true';
            $query->whereHas('rfidCard', function ($q) {
                $q->where('est_active', true);
            }, $hasCard ? '>=' : '<', $hasCard ? 1 : 1);
        }

        // Les enseignants voient uniquement les étudiants inscrits à leurs cours
        if ($authenticatedUser->teacher) {
            $teacherCourseIds = $authenticatedUser->teacher->courses()->pluck('cours.id');
            $query->whereHas('courseEnrollments', function ($q) use ($teacherCourseIds) {
                $q->whereIn('cours_id', $teacherCourseIds);
            });
        }

        // Les étudiants ne voient que eux-mêmes
        if ($authenticatedUser->student) {
            $query->where('id', $authenticatedUser->student->id);
        }

        $limit  = min($request->query('limit', 50), 100);
        $offset = $request->query('offset', 0);
        $total  = $query->count();

        $students = $query->with(['user', 'rfidCard', 'courseEnrollments', 'filiere'])
            ->orderBy('numero_matricule')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function ($student) {
                return [
                    'id'                  => $student->id,
                    'user_id'             => $student->utilisateur_id,
                    'name'                => $student->user?->full_name ?? 'Étudiant inconnu',
                    'email'               => $student->user?->email ?? '',
                    'phone'               => $student->user?->telephone,
                    'gender'              => $student->user?->genre,
                    'registration_number' => $student->numero_matricule,
                    'level'               => $student->niveau,
                    'filiere'             => $student->filiere ? [
                        'id'   => $student->filiere->id,
                        'name' => $student->filiere->nom ?? '',
                        'code' => $student->filiere->code ?? '',
                    ] : null,
                    'registered_at'       => $student->created_at?->toDateString(),
                    'rfid_card'           => $student->rfidCard ? [
                        'id'             => $student->rfidCard->id,
                        'card_number'    => $student->rfidCard->numero_carte,
                        'is_active'      => $student->rfidCard->est_active,
                        'last_scanned_at'=> $student->rfidCard->dernier_scan_le,
                    ] : null,
                    'enrollment_count'    => $student->courseEnrollments->count(),
                ];
            });

        return response()->json([
            'data'       => $students,
            'pagination' => [
                'total'  => $total,
                'limit'  => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    /**
     * GET /api/universities/{universityId}/students/{id}
     */
    public function show(Request $request, $universityId, $studentId)
    {
        $user    = auth()->user();
        $student = Student::where('universite_id', $universityId)
            ->where('id', $studentId)
            ->with([
                'user',
                'rfidCard',
                'filiere',
                'courseEnrollments.course.teacher.user',
                'attendances.timetableEntry.course',
            ])
            ->firstOrFail();

        if ($user->student && $user->student->id !== (int) $studentId) {
            abort(403, 'Not authorized to view this student profile');
        }

        $courseStats = [];
        foreach ($student->courseEnrollments as $enrollment) {
            $course = $enrollment->course;
            if (!$course) continue; // Skip enrollments for deleted courses

            $attendances = $student->attendances()
                ->whereHas('timetableEntry', function ($q) use ($course) {
                    $q->where('cours_id', $course->id);
                })
                ->get();

            $totalAttendances = $attendances->count();
            $presentCount     = $attendances->where('statut', 'present')->count();
            $lateCount        = $attendances->where('statut', 'late')->count();

            if ($totalAttendances > 0) {
                $courseStats[] = [
                    'course_id'          => $course->id,
                    'course_name'        => $course->nom,
                    'course_code'        => $course->code,
                    'total_sessions'     => $course->timetableEntries->count(),
                    'attended_sessions'  => $totalAttendances,
                    'present_count'      => $presentCount,
                    'late_count'         => $lateCount,
                    'absent_count'       => $totalAttendances - $presentCount - $lateCount,
                    'attendance_rate'    => round((($presentCount + $lateCount) / $totalAttendances) * 100, 2),
                ];
            }
        }

        return response()->json([
            'id'                  => $student->id,
            // Champs plats (attendus par le formulaire d'édition)
            'name'                => $student->user?->full_name ?? 'Étudiant inconnu',
            'email'               => $student->user?->email ?? '',
            'phone'               => $student->user?->telephone,
            'gender'              => $student->user?->genre,
            'registration_number' => $student->numero_matricule,
            'level'               => $student->niveau,
            'filiere_id'          => $student->filiere_id,
            'filiere'             => $student->filiere ? [
                'id'   => $student->filiere->id,
                'name' => $student->filiere->nom ?? '',
                'code' => $student->filiere->code ?? '',
            ] : null,
            // Objet user complet (pour les pages de détail)
            'user' => [
                'id'         => $student->user?->id,
                'name'       => $student->user?->full_name ?? 'Étudiant inconnu',
                'email'      => $student->user?->email ?? '',
                'phone'      => $student->user?->telephone,
                'gender'     => $student->user?->genre,
                'address'    => $student->user?->adresse,
                'created_at' => $student->user?->created_at,
            ],
            'rfid_card'           => $student->rfidCard ? [
                'id'             => $student->rfidCard->id,
                'card_number'    => $student->rfidCard->numero_carte,
                'is_active'      => $student->rfidCard->est_active,
                'issued_at'      => $student->rfidCard->assignee_le,
                'last_scanned_at'=> $student->rfidCard->dernier_scan_le,
                'total_scans'    => $student->rfidCard->accessLogs()->count(),
                'successful_scans' => $student->rfidCard->accessLogs()->where('statut', 'granted')->count(),
                'failed_scans'   => $student->rfidCard->accessLogs()->where('statut', 'refused')->count(),
            ] : null,
            'courses' => $student->courseEnrollments->map(function ($enrollment) {
                $course = $enrollment->course;
                if (!$course) return null;
                return [
                    'id'       => $course->id,
                    'name'     => $course->nom,
                    'code'     => $course->code,
                    'level'    => $course->niveau,
                    'credits'  => $course->credits,
                    'teacher'  => $course->teacher ? [
                        'id'        => $course->teacher->id,
                        'name'      => $course->teacher->user?->full_name ?? 'Enseignant inconnu',
                        'specialty' => $course->teacher->specialite,
                    ] : null,
                ];
            })->filter()->values(),
            'course_statistics'   => $courseStats,
            'overall_statistics'  => [
                'total_courses'           => $student->courseEnrollments->count(),
                'total_sessions_attended' => $student->attendances->count(),
                'present_count'           => $student->attendances->where('statut', 'present')->count(),
                'late_count'              => $student->attendances->where('statut', 'late')->count(),
                'absent_count'            => $student->attendances->where('statut', 'absent')->count(),
                'overall_attendance_rate' => $this->calculateOverallAttendanceRate($student),
            ],
            'recent_attendances' => $student->attendances()
                ->with(['timetableEntry.course', 'timetableEntry.classroom'])
                ->orderBy('scanne_le', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($attendance) {
                    $timetable = $attendance->timetableEntry;
                    return [
                        'id'         => $attendance->id,
                        'status'     => $attendance->statut,
                        'scanned_at' => $attendance->scanne_le,
                        'course'     => $timetable?->course ? [
                            'id'   => $timetable->course->id,
                            'name' => $timetable->course->nom ?? 'Cours inconnu',
                            'code' => $timetable->course->code ?? '—',
                        ] : ['id' => null, 'name' => 'Cours inconnu', 'code' => '—'],
                        'timetable'  => $timetable ? [
                            'start_time' => $timetable->heure_debut,
                            'end_time'   => $timetable->heure_fin,
                            'classroom'  => $timetable->classroom?->nom ?? '—',
                        ] : null,
                    ];
                }),
        ]);
    }

    /**
     * GET /api/universities/{universityId}/students/{id}/attendances
     */
    public function attendances(Request $request, $universityId, $studentId)
    {
        $user    = auth()->user();
        $student = Student::where('universite_id', $universityId)
            ->where('id', $studentId)
            ->firstOrFail();

        if ($user->student && $user->student->id !== (int) $studentId) {
            abort(403, 'Not authorized to view these attendance records');
        }

        $query = $student->attendances();

        if ($request->has('status')) {
            $query->where('statut', $request->query('status'));
        }

        if ($request->has('course_id')) {
            $courseId = $request->query('course_id');
            $query->whereHas('timetableEntry', function ($q) use ($courseId) {
                $q->where('cours_id', $courseId);
            });
        }

        if ($request->has('date_from')) {
            $dateFrom = \Carbon\Carbon::parse($request->query('date_from'))->startOfDay();
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($request->has('date_to')) {
            $dateTo = \Carbon\Carbon::parse($request->query('date_to'))->endOfDay();
            $query->where('created_at', '<=', $dateTo);
        }

        $limit  = min($request->query('limit', 50), 100);
        $offset = $request->query('offset', 0);
        $total  = $query->count();

        $attendances = $query->with(['timetableEntry.course', 'timetableEntry.classroom'])
            ->orderBy('scanne_le', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function ($attendance) {
                $timetable = $attendance->timetableEntry;
                return [
                    'id'     => $attendance->id,
                    'course' => $timetable?->course ? [
                        'id'   => $timetable->course->id,
                        'name' => $timetable->course->nom ?? 'Cours inconnu',
                        'code' => $timetable->course->code ?? '—',
                    ] : ['id' => null, 'name' => 'Cours inconnu', 'code' => '—'],
                    'status'              => $attendance->statut,
                    'scanned_at'          => $attendance->scanne_le,
                    'timetable'           => $timetable ? [
                        'day'        => $timetable->jour_semaine,
                        'start_time' => $timetable->heure_debut,
                        'end_time'   => $timetable->heure_fin,
                        'classroom'  => $timetable->classroom?->nom ?? '—',
                    ] : null,
                    'notes'               => $attendance->notes,
                    'verification_method' => $attendance->methode_verification,
                ];
            });

        return response()->json([
            'data'       => $attendances,
            'pagination' => [
                'total'  => $total,
                'limit'  => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    /**
     * GET /api/universities/{universityId}/students/{studentId}/timetable
     */
    public function timetable(Request $request, int $universityId, int $studentId): JsonResponse
    {
        $student = \App\Models\Student::where('id', $studentId)
            ->where('universite_id', $universityId)
            ->firstOrFail();

        // Cours auxquels l'étudiant est inscrit individuellement
        $enrolledCourseIds = \App\Models\CourseEnrollment::where('etudiant_id', $student->id)
            ->pluck('cours_id');

        // Cours liés à la filière de l'étudiant (programmés par l'admin)
        $filiereCourseIds = collect();
        if ($student->filiere_id) {
            $filiereCourseIds = DB::table('filiere_cours')
                ->where('filiere_id', $student->filiere_id)
                ->pluck('cours_id');
        }

        // Union des deux sources — un étudiant voit les cours de sa filière
        // ET ses cours individuels éventuels
        $courseIds = $enrolledCourseIds->merge($filiereCourseIds)->unique();

        $entries = \App\Models\TimetableEntry::whereIn('cours_id', $courseIds)
            ->where('universite_id', $universityId)
            ->with(['course.teacher.user', 'classroom'])
            ->orderBy('jour_semaine')
            ->orderBy('heure_debut')
            ->get()
            ->map(function ($entry) {
                $dayMap = [
                    'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3,
                    'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7,
                ];
                return [
                    'id'        => $entry->id,
                    'dayOfWeek' => $dayMap[$entry->jour_semaine] ?? 1,
                    'dayName'   => $entry->jour_semaine,
                    'startTime' => substr($entry->heure_debut, 0, 5),
                    'endTime'   => substr($entry->heure_fin, 0, 5),
                    'course'    => $entry->course ? [
                        'id'   => $entry->course->id,
                        'name' => $entry->course->nom,
                        'code' => $entry->course->code,
                    ] : null,
                    'teacher'   => $entry->course?->teacher?->user ? [
                        'name' => $entry->course->teacher->user->prenom . ' ' . $entry->course->teacher->user->nom,
                    ] : null,
                    'classroom' => $entry->classroom ? [
                        'id'   => $entry->classroom->id,
                        'name' => $entry->classroom->nom,
                    ] : null,
                ];
            });

        return response()->json(['data' => $entries]);
    }

    /**
     * POST /api/universities/{universityId}/students
     */
    public function store(Request $request, int $universityId): JsonResponse
    {
        $validated = $request->validate([
            'first_name'          => 'required|string|min:2|max:50',
            'last_name'           => 'required|string|min:2|max:50',
            'email'               => 'required|email|max:255|unique:utilisateurs,email',
            'phone'               => 'nullable|string|max:20',
            'registration_number' => [
                'required', 'string', 'max:50',
                Rule::unique('etudiants', 'numero_matricule')->where('universite_id', $universityId),
            ],
            'level'      => 'required|string|in:L1,L2,L3,M1,M2,D1,D2,D3',
            'filiere_id' => 'nullable|integer|exists:filieres,id',
            'gender'     => 'nullable|string|in:M,F',
        ], [
            'email.unique'               => 'Cette adresse email est déjà utilisée.',
            'registration_number.unique' => 'Ce numéro matricule est déjà utilisé dans cette université.',
            'level.in'                   => 'Le niveau doit être : L1, L2, L3, M1, M2, D1, D2 ou D3.',
            'gender.in'                  => 'Le sexe doit être M ou F.',
        ]);

        return DB::transaction(function () use ($validated, $universityId, $request) {
            $tempPassword = Str::random(10) . '!1A';

            $user = User::create([
                'universite_id' => $universityId,
                'prenom'        => trim($validated['first_name']),
                'nom'           => trim($validated['last_name']),
                'email'         => strtolower(trim($validated['email'])),
                'telephone'     => $validated['phone'] ?? null,
                'genre'         => $validated['gender'] ?? null,
                'password'      => Hash::make($tempPassword),
                'role'          => 'student',
                'est_actif'     => true,
            ]);

            $student = Student::create([
                'utilisateur_id'  => $user->id,
                'universite_id'   => $universityId,
                'numero_matricule'=> trim($validated['registration_number']),
                'niveau'          => $validated['level'],
                'filiere_id'      => $validated['filiere_id'] ?? null,
            ]);

            AuditLog::create([
                'universite_id'  => $universityId,
                'utilisateur_id' => $request->user()->id,
                'type_ressource' => 'Student',
                'id_ressource'   => $student->id,
                'action'         => 'create',
                'nouvelles_valeurs' => [
                    'numero_matricule' => $student->numero_matricule,
                    'niveau'           => $student->niveau,
                    'email'            => $user->email,
                ],
                'adresse_ip'     => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Étudiant créé avec succès',
                'student' => [
                    'id'                  => $student->id,
                    'registration_number' => $student->numero_matricule,
                    'level'               => $student->niveau,
                    'filiere_id'          => $student->filiere_id,
                    'name'                => $user->prenom . ' ' . $user->nom,
                    'email'               => $user->email,
                    'phone'               => $user->telephone,
                    'gender'              => $user->genre,
                ],
            ], 201);
        });
    }

    /**
     * PUT /api/universities/{universityId}/students/{studentId}
     */
    public function update(Request $request, int $universityId, int $studentId): JsonResponse
    {
        $student = Student::where('universite_id', $universityId)
            ->where('id', $studentId)
            ->firstOrFail();

        $validated = $request->validate([
            'first_name'          => 'sometimes|string|min:2|max:50',
            'last_name'           => 'sometimes|string|min:2|max:50',
            'email'               => ['sometimes', 'email', 'max:255',
                                      Rule::unique('utilisateurs', 'email')->ignore($student->utilisateur_id)],
            'phone'               => 'nullable|string|max:20',
            'registration_number' => [
                'sometimes', 'string', 'max:50',
                Rule::unique('etudiants', 'numero_matricule')
                    ->where('universite_id', $universityId)
                    ->ignore($student->id),
            ],
            'level'      => 'sometimes|string|in:L1,L2,L3,M1,M2,D1,D2,D3',
            'filiere_id' => 'nullable|integer|exists:filieres,id',
            'gender'     => 'nullable|string|in:M,F',
        ], [
            'email.unique'               => 'Cette adresse email est déjà utilisée.',
            'registration_number.unique' => 'Ce numéro matricule est déjà utilisé dans cette université.',
        ]);

        return DB::transaction(function () use ($validated, $student, $universityId, $request) {
            // Map incoming English field names to French DB column names for User
            $userFieldMap = [
                'first_name' => 'prenom',
                'last_name'  => 'nom',
                'email'      => 'email',
                'phone'      => 'telephone',
                'gender'     => 'genre',
            ];
            $userFields = [];
            foreach ($userFieldMap as $inputKey => $dbKey) {
                if (isset($validated[$inputKey])) {
                    $userFields[$dbKey] = $validated[$inputKey];
                }
            }
            if (!empty($userFields)) {
                $student->user->update($userFields);
            }

            // Map incoming English field names to French DB column names for Student
            $studentFieldMap = [
                'registration_number' => 'numero_matricule',
                'level'               => 'niveau',
                'filiere_id'          => 'filiere_id',
            ];
            $studentFields = [];
            foreach ($studentFieldMap as $inputKey => $dbKey) {
                if (array_key_exists($inputKey, $validated)) {
                    $studentFields[$dbKey] = $validated[$inputKey];
                }
            }
            if (!empty($studentFields)) {
                $student->update($studentFields);
            }

            AuditLog::create([
                'universite_id'  => $universityId,
                'utilisateur_id' => $request->user()->id,
                'type_ressource' => 'Student',
                'id_ressource'   => $student->id,
                'action'         => 'update',
                'nouvelles_valeurs' => $validated,
                'adresse_ip'     => $request->ip(),
            ]);

            $student->refresh();
            return response()->json([
                'message' => 'Étudiant mis à jour',
                'student' => [
                    'id'                  => $student->id,
                    'registration_number' => $student->numero_matricule,
                    'level'               => $student->niveau,
                    'filiere_id'          => $student->filiere_id,
                    'name'                => $student->user->prenom . ' ' . $student->user->nom,
                    'email'               => $student->user->email,
                    'phone'               => $student->user->telephone,
                ],
            ]);
        });
    }

    /**
     * DELETE /api/universities/{universityId}/students/{studentId}
     */
    public function destroy(Request $request, int $universityId, int $studentId): JsonResponse
    {
        $student = Student::where('universite_id', $universityId)
            ->where('id', $studentId)
            ->firstOrFail();

        AuditLog::create([
            'universite_id'  => $universityId,
            'utilisateur_id' => $request->user()->id,
            'type_ressource' => 'Student',
            'id_ressource'   => $student->id,
            'action'         => 'delete',
            'anciennes_valeurs' => [
                'numero_matricule' => $student->numero_matricule,
                'niveau'           => $student->niveau,
                'email'            => $student->user->email ?? null,
            ],
            'adresse_ip'     => $request->ip(),
        ]);

        $student->delete();
        return response()->json(['message' => 'Étudiant supprimé']);
    }

    private function calculateOverallAttendanceRate(Student $student): float
    {
        $totalAttendances = $student->attendances->count();
        if ($totalAttendances === 0) {
            return 0.0;
        }

        $presentCount = $student->attendances->where('statut', 'present')->count();
        $lateCount    = $student->attendances->where('statut', 'late')->count();

        return round((($presentCount + $lateCount) / $totalAttendances) * 100, 2);
    }
}
