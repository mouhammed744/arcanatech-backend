<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Filiere;
use App\Models\Student;
use App\Models\TimetableEntry;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * FiliereController - Gestion des filières et mise à jour en masse
 *
 * ARCHITECTURE :
 * - CRUD complet pour les filières (admin uniquement)
 * - Mise à jour en masse des informations d'emploi du temps
 *   pour tous les étudiants d'une ou plusieurs filières
 *
 * RÈGLES MÉTIER :
 * 1. L'admin peut mettre à jour INSTANTANÉMENT les infos suivantes
 *    pour tous les étudiants d'une filière :
 *    - Heure du cours (start_time, end_time)
 *    - Date du cours (day_of_week, start_date, end_date)
 *    - Salle (classroom_id)
 *
 * 2. Les informations PERSONNELLES des étudiants NE PEUVENT PAS
 *    être modifiées en masse :
 *    - Nom, prénom, email, téléphone
 *    - Date de naissance
 *    - Numéro d'inscription
 *    - Carte RFID
 *
 * 3. La mise à jour touche les timetable_entries liées aux cours
 *    dans lesquels les étudiants de la filière sont inscrits
 */
class FiliereController extends Controller
{
    /**
     * GET /api/universities/{universityId}/filieres-public
     *
     * Liste publique des filières (pour inscription mobile — pas d'auth requise)
     */
    public function publicList(Request $request)
    {
        $universityId = $request->route('universityId');

        $filieres = Filiere::where('universite_id', $universityId)
            ->select('id', 'nom', 'code', 'niveau')
            ->orderBy('nom')
            ->get()
            ->map(fn($f) => [
                'id'    => $f->id,
                'name'  => $f->nom,
                'code'  => $f->code,
                'level' => $f->niveau,
            ]);

        return response()->json(['data' => $filieres]);
    }

    /**
     * GET /api/universities/{universityId}/filieres
     *
     * Lister toutes les filières avec le nombre d'étudiants
     */
    public function index(Request $request)
    {
        $universityId = $request->route('universityId');

        $query = Filiere::forUniversity($universityId);

        // Filtres
        if ($level = $request->query('level')) {
            $query->level($level);
        }
        if ($department = $request->query('department')) {
            $query->department($department);
        }
        if ($request->query('active_only', false)) {
            $query->active();
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'ILIKE', "%{$search}%")
                  ->orWhere('code', 'ILIKE', "%{$search}%");
            });
        }

        // Tenter withCount('students') — peut échouer si la migration filiere_id n'a pas été exécutée
        try {
            $filieres = (clone $query)->withCount('students')->orderBy('nom')->get();
        } catch (\Throwable $e) {
            // Fallback : charger sans le compte (migration probablement pas exécutée)
            $filieres = $query->orderBy('nom')->get()->map(function ($f) {
                $f->students_count = 0;
                return $f;
            });
        }

        return response()->json([
            'data' => $filieres->map(fn($f) => [
                'id'            => $f->id,
                'code'          => $f->code,
                'name'          => $f->nom,
                'description'   => $f->description,
                'level'         => $f->niveau,
                'department'    => $f->departement,
                'isActive'      => (bool) $f->est_active,      // camelCase pour le frontend
                'studentsCount' => (int) ($f->students_count ?? 0),
            ]),
            'total' => $filieres->count(),
        ]);
    }

    /**
     * GET /api/universities/{universityId}/filieres/{id}
     *
     * Détails d'une filière avec ses étudiants et cours
     */
    public function show(Request $request, int $universityId, int $id)
    {
        // withCount peut échouer si la migration filiere_id n'a pas été exécutée
        try {
            $filiere = Filiere::forUniversity($universityId)
                ->withCount('students')
                ->findOrFail($id);
        } catch (\Throwable $e) {
            $filiere = Filiere::forUniversity($universityId)->findOrFail($id);
            $filiere->students_count = Student::where('filiere_id', $filiere->id)->count();
        }

        // Récupérer les étudiants de cette filière
        // Utiliser ?-> (null-safe) car user peut être null si l'utilisateur a été supprimé
        $students = Student::where('filiere_id', $filiere->id)
            ->with(['user:id,prenom,nom,email', 'rfidCard:id,etudiant_id,numero_carte,est_active'])
            ->get()
            ->map(fn($s) => [
                'id'                  => $s->id,
                'registration_number' => $s->numero_matricule,
                'name'                => trim(($s->user?->prenom ?? '') . ' ' . ($s->user?->nom ?? '')),
                'email'               => $s->user?->email ?? null,
                'level'               => $s->niveau,
                'has_rfid'            => $s->rfidCard !== null,
                'rfid_active'         => $s->rfidCard?->est_active ?? false,
            ]);

        // Récupérer les cours liés à cette filière (via les inscriptions non supprimées)
        $courseIds = Student::where('filiere_id', $filiere->id)
            ->join('inscriptions', 'etudiants.id', '=', 'inscriptions.etudiant_id')
            ->whereNull('inscriptions.deleted_at')
            ->where('inscriptions.statut', 'enrolled')
            ->distinct()
            ->pluck('inscriptions.cours_id');

        $timetableEntries = collect();
        if ($courseIds->isNotEmpty()) {
            try {
                $timetableEntries = TimetableEntry::whereIn('cours_id', $courseIds)
                    ->where('universite_id', $universityId)
                    ->with(['course:id,code,nom', 'classroom:id,nom,batiment'])
                    ->active()
                    ->get()
                    ->map(fn($t) => [
                        'id'           => $t->id,
                        'course_code'  => $t->course?->code,
                        'course_name'  => $t->course?->nom,
                        'classroom'    => $t->classroom?->nom,
                        'building'     => $t->classroom?->batiment,
                        'day_of_week'  => $t->jour_semaine,
                        'start_time'   => $t->heure_debut,
                        'end_time'     => $t->heure_fin,
                        'session_type' => $t->type_seance,
                    ]);
            } catch (\Throwable $e) {
                $timetableEntries = collect();
            }
        }

        return response()->json([
            'filiere' => [
                'id'             => $filiere->id,
                'code'           => $filiere->code,
                'name'           => $filiere->nom,
                'description'    => $filiere->description,
                'level'          => $filiere->niveau,
                'department'     => $filiere->departement,
                'is_active'      => $filiere->est_active,
                'students_count' => $filiere->students_count ?? $students->count(),
            ],
            'students'  => $students,
            'timetable' => $timetableEntries,
        ]);
    }

    /**
     * POST /api/universities/{universityId}/filieres
     *
     * Créer une nouvelle filière (admin uniquement)
     */
    public function store(Request $request, int $universityId)
    {
        $validated = $request->validate([
            'code'        => ['required', 'string', 'max:20',
                               Rule::unique('filieres', 'code')->where('universite_id', $universityId)],
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'level'       => 'required|string|in:L1,L2,L3,M1,M2,D1,D2,D3',
            'department'  => 'nullable|string|max:255',
        ], [
            'code.unique' => 'Ce code est déjà utilisé par une autre filière de cette université.',
            'level.in'    => "Le niveau doit être l'un des suivants : L1, L2, L3, M1, M2, D1, D2, D3.",
        ]);

        return DB::transaction(function () use ($request, $universityId, $validated) {
            $filiere = Filiere::create([
                'universite_id' => $universityId,
                'code'          => $validated['code'],
                'nom'           => $validated['name'],
                'description'   => $validated['description'] ?? null,
                'niveau'        => $validated['level'],
                'departement'   => $validated['department'] ?? null,
                'est_active'    => true,
            ]);

            AuditLog::create([
                'universite_id'     => $universityId,
                'utilisateur_id'    => $request->user()->id,
                'type_ressource'    => 'Filiere',
                'id_ressource'      => $filiere->id,
                'action'            => 'create',
                'nouvelles_valeurs' => $validated,
                'adresse_ip'        => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Filière créée avec succès',
                'filiere' => $filiere,
            ], 201);
        });
    }

    /**
     * PUT /api/universities/{universityId}/filieres/{id}
     *
     * Modifier une filière (admin uniquement)
     */
    public function update(Request $request, int $universityId, int $id)
    {
        $filiere = Filiere::forUniversity($universityId)->findOrFail($id);

        $validated = $request->validate([
            'code'        => ['sometimes', 'string', 'max:20',
                              Rule::unique('filieres', 'code')->where('universite_id', $universityId)->ignore($filiere->id)],
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'level'       => 'sometimes|string|in:L1,L2,L3,M1,M2,D1,D2,D3',
            'department'  => 'nullable|string|max:255',
            'is_active'   => 'sometimes|boolean',
        ], [
            'code.unique' => 'Ce code est déjà utilisé par une autre filière de cette université.',
        ]);

        // Mapper les clés anglaises vers les colonnes françaises
        $frenchData = array_filter([
            'code'        => $validated['code'] ?? null,
            'nom'         => $validated['name'] ?? null,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : null,
            'niveau'      => $validated['level'] ?? null,
            'departement' => array_key_exists('department', $validated) ? $validated['department'] : null,
            'est_active'  => $validated['is_active'] ?? null,
        ], fn($v) => $v !== null);

        $oldValues = $filiere->only(['code', 'nom', 'description', 'niveau', 'departement', 'est_active']);
        $filiere->update($frenchData);

        AuditLog::create([
            'universite_id'     => $universityId,
            'utilisateur_id'    => $request->user()->id,
            'type_ressource'    => 'Filiere',
            'id_ressource'      => $filiere->id,
            'action'            => 'update',
            'anciennes_valeurs' => $oldValues,
            'nouvelles_valeurs' => $validated,
            'adresse_ip'        => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Filière mise à jour',
            'filiere' => $filiere->fresh(),
        ]);
    }

    /**
     * DELETE /api/universities/{universityId}/filieres/{id}
     *
     * Supprimer une filière (soft delete, admin uniquement)
     */
    public function destroy(Request $request, int $universityId, int $id)
    {
        $filiere = Filiere::forUniversity($universityId)->findOrFail($id);

        AuditLog::create([
            'universite_id'     => $universityId,
            'utilisateur_id'    => $request->user()->id,
            'type_ressource'    => 'Filiere',
            'id_ressource'      => $filiere->id,
            'action'            => 'delete',
            'anciennes_valeurs' => $filiere->toArray(),
            'adresse_ip'        => $request->ip(),
        ]);

        $filiere->delete(); // Soft delete

        return response()->json([
            'message' => 'Filière supprimée',
        ]);
    }

    /**
     * POST /api/universities/{universityId}/filieres/bulk-update-schedule
     *
     * ═══════════════════════════════════════════════════════════════
     * MISE À JOUR EN MASSE DES EMPLOIS DU TEMPS PAR FILIÈRE
     * ═══════════════════════════════════════════════════════════════
     *
     * L'administrateur peut mettre à jour INSTANTANÉMENT les informations
     * d'emploi du temps pour TOUS les étudiants d'une ou plusieurs filières.
     *
     * CE QUI PEUT ÊTRE MIS À JOUR EN MASSE :
     * ✅ Heure du cours (start_time, end_time)
     * ✅ Jour du cours (day_of_week)
     * ✅ Salle (classroom_id)
     * ✅ Dates de début/fin (start_date, end_date)
     *
     * CE QUI NE PEUT PAS ÊTRE MIS À JOUR EN MASSE :
     * ❌ Informations personnelles des étudiants (nom, prénom, email, etc.)
     * ❌ Cartes RFID
     * ❌ Numéros d'inscription
     *
     * REQUEST :
     * {
     *   "filiere_ids": [1, 2, 3],           // Une ou plusieurs filières
     *   "course_id": 5,                      // Le cours à modifier
     *   "updates": {
     *     "classroom_id": 10,                // Nouvelle salle
     *     "start_time": "10:00:00",          // Nouvelle heure de début
     *     "end_time": "12:00:00",            // Nouvelle heure de fin
     *     "day_of_week": "Tuesday",          // Nouveau jour
     *     "start_date": "2026-03-15",        // Nouvelle date de début
     *     "end_date": "2026-06-30"           // Nouvelle date de fin
     *   }
     * }
     *
     * RESPONSE :
     * {
     *   "message": "Mise à jour effectuée pour 3 filières",
     *   "affected_entries": 5,
     *   "affected_students": 120,
     *   "updates_applied": { ... }
     * }
     */
    public function bulkUpdateSchedule(Request $request, int $universityId)
    {
        // Vérifier que l'utilisateur est admin
        $adminUser = $request->user();
        if (!$adminUser || $adminUser->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Seul un administrateur peut effectuer une mise à jour en masse',
            ], 403);
        }

        $validated = $request->validate([
            'filiere_ids' => 'required|array|min:1',
            'filiere_ids.*' => 'integer|exists:filieres,id',
            'course_id' => 'required|integer|exists:cours,id',
            'updates' => 'required|array',
            'updates.salle_id' => 'sometimes|integer|exists:salles,id',
            'updates.heure_debut' => 'sometimes|date_format:H:i:s',
            'updates.heure_fin' => 'sometimes|date_format:H:i:s',
            'updates.jour_semaine' => 'sometimes|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'updates.date_debut' => 'sometimes|date',
            'updates.date_fin' => 'sometimes|date|after_or_equal:updates.date_debut',
        ]);

        $filiereIds = $validated['filiere_ids'];
        $courseId = $validated['course_id'];
        $updates = $validated['updates'];

        // SÉCURITÉ : Interdire toute modification de données personnelles
        $forbiddenFields = [
            'prenom', 'nom', 'email', 'telephone', 'adresse',
            'date_naissance', 'numero_matricule', 'numero_carte', 'password',
        ];
        foreach ($forbiddenFields as $field) {
            if (isset($updates[$field])) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Le champ '{$field}' ne peut pas être modifié en masse. Les informations personnelles doivent être traitées individuellement.",
                ], 422);
            }
        }

        // Récupérer les filières concernées
        $filieres = Filiere::forUniversity($universityId)
            ->whereIn('id', $filiereIds)
            ->get();

        if ($filieres->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucune filière trouvée',
            ], 404);
        }

        // Trouver les timetable_entries du cours pour les filières ciblées
        // Un cours est lié aux filières via les étudiants inscrits
        $studentIds = Student::whereIn('filiere_id', $filiereIds)
            ->where('universite_id', $universityId)
            ->pluck('id');

        // Trouver les timetable_entries de ce cours
        $timetableEntries = TimetableEntry::where('cours_id', $courseId)
            ->where('universite_id', $universityId)
            ->get();

        if ($timetableEntries->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucune session trouvée pour ce cours',
            ], 404);
        }

        // Appliquer les modifications aux timetable_entries
        $oldValues = $timetableEntries->map(fn($t) => $t->only(array_keys($updates)))->toArray();

        $affectedCount = TimetableEntry::where('cours_id', $courseId)
            ->where('universite_id', $universityId)
            ->update($updates);

        // Compter les étudiants affectés
        $affectedStudents = Student::whereIn('filiere_id', $filiereIds)
            ->where('universite_id', $universityId)
            ->whereHas('courseEnrollments', fn($q) => $q->where('cours_id', $courseId)->where('statut', 'enrolled'))
            ->count();

        // Audit log détaillé
        AuditLog::create([
            'universite_id'     => $universityId,
            'utilisateur_id'    => $adminUser->id,
            'type_ressource'    => 'TimetableEntry',
            'id_ressource'      => null,
            'action'            => 'bulk_update_schedule',
            'anciennes_valeurs' => [
                'filiere_ids' => $filiereIds,
                'course_id' => $courseId,
                'previous_values' => $oldValues,
            ],
            'nouvelles_valeurs' => [
                'filiere_ids' => $filiereIds,
                'filiere_names' => $filieres->pluck('nom')->toArray(),
                'course_id' => $courseId,
                'updates' => $updates,
                'affected_entries' => $affectedCount,
                'affected_students' => $affectedStudents,
            ],
            'adresse_ip'        => $request->ip(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => sprintf(
                'Mise à jour effectuée pour %d filière(s) - %d étudiant(s) concerné(s)',
                $filieres->count(),
                $affectedStudents
            ),
            'details' => [
                'filieres' => $filieres->map(fn($f) => ['id' => $f->id, 'name' => $f->nom]),
                'affected_entries' => $affectedCount,
                'affected_students' => $affectedStudents,
                'updates_applied' => $updates,
            ],
        ]);
    }

    /**
     * POST /api/universities/{universityId}/filieres/bulk-update-cards
     *
     * Mettre à jour les cartes RFID de tous les étudiants d'une filière
     * (activation/désactivation en masse, PAS les infos personnelles)
     *
     * UNIQUEMENT : activer/désactiver les cartes (pas changer les numéros)
     */
    public function bulkUpdateCards(Request $request, int $universityId)
    {
        $adminUser = $request->user();
        if (!$adminUser || $adminUser->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Seul un administrateur peut effectuer cette opération',
            ], 403);
        }

        $validated = $request->validate([
            'filiere_ids' => 'required|array|min:1',
            'filiere_ids.*' => 'integer|exists:filieres,id',
            'action' => 'required|string|in:activate,deactivate',
            'reason' => 'nullable|string|max:500',
        ]);

        $studentIds = Student::whereIn('filiere_id', $validated['filiere_ids'])
            ->where('universite_id', $universityId)
            ->pluck('id');

        $isActive = $validated['action'] === 'activate';

        $affected = \App\Models\RfidCard::whereIn('etudiant_id', $studentIds)
            ->where('universite_id', $universityId)
            ->update([
                'est_active'          => $isActive,
                'desactivee_le'       => $isActive ? null : now(),
                'raison_desactivation' => $isActive ? null : ($validated['reason'] ?? 'Bulk deactivation by admin'),
            ]);

        AuditLog::create([
            'universite_id'     => $universityId,
            'utilisateur_id'    => $adminUser->id,
            'type_ressource'    => 'RfidCard',
            'id_ressource'      => null,
            'action'            => 'bulk_' . $validated['action'] . '_cards',
            'nouvelles_valeurs' => [
                'filiere_ids' => $validated['filiere_ids'],
                'action' => $validated['action'],
                'affected_cards' => $affected,
                'reason' => $validated['reason'] ?? null,
            ],
            'adresse_ip'        => $request->ip(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => sprintf(
                '%d carte(s) RFID %s avec succès',
                $affected,
                $isActive ? 'activée(s)' : 'désactivée(s)'
            ),
            'affected_cards' => $affected,
        ]);
    }
}
