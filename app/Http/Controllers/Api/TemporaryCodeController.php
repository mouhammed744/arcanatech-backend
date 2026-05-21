<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TemporaryAccessCode;
use App\Models\Student;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TemporaryCodeController extends Controller
{
    /**
     * POST /api/universities/{universityId}/temporary-codes
     * Generate a temporary access code for a student
     */
    public function store(Request $request, int $universityId): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:etudiants,id'],
            'classroom_id' => ['nullable', 'integer', 'exists:salles,id'],
            'timetable_entry_id' => ['nullable', 'integer', 'exists:seances,id'],
            'reason' => ['nullable', 'string', 'max:500'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:60'],
        ]);

        $admin = auth()->user();
        $durationMinutes = $validated['duration_minutes'] ?? 15;

        $code = TemporaryAccessCode::create([
            'universite_id'  => $universityId,
            'etudiant_id'    => $validated['student_id'],
            'genere_par'     => $admin->id,
            'salle_id'       => $validated['classroom_id'] ?? null,
            'seance_id'      => $validated['timetable_entry_id'] ?? null,
            'code'           => TemporaryAccessCode::generateCode(),
            'raison'         => $validated['reason'] ?? 'Retard autorisé par l\'administrateur',
            'expire_le'      => now()->addMinutes($durationMinutes),
        ]);

        // Load relationships for response
        $code->load(['student.user', 'classroom', 'generatedBy']);

        AuditLog::create([
            'universite_id'     => $universityId,
            'utilisateur_id'    => $admin->id,
            'type_ressource'    => 'TemporaryAccessCode',
            'id_ressource'      => $code->id,
            'action'            => 'create',
            'adresse_ip'        => $request->ip(),
            'nouvelles_valeurs' => [
                'student_id' => $code->etudiant_id,
                'code' => $code->code,
                'expires_at' => $code->expire_le->toISOString(),
                'reason' => $code->raison,
            ],
        ]);

        return response()->json([
            'message' => 'Code temporaire généré avec succès.',
            'temporaryCode' => [
                'id' => $code->id,
                'code' => $code->code,
                'student' => [
                    'id' => $code->student->id,
                    'name' => $code->student->user->prenom . ' ' . $code->student->user->nom,
                    'registrationNumber' => $code->student->numero_matricule,
                ],
                'classroom' => $code->classroom ? [
                    'id' => $code->classroom->id,
                    'name' => $code->classroom->nom,
                ] : null,
                'reason' => $code->raison,
                'expiresAt' => $code->expire_le->toISOString(),
                'expiresIn' => $durationMinutes . ' minutes',
                'generatedBy' => $admin->prenom . ' ' . $admin->nom,
                'isUsed' => false,
            ],
        ], 201);
    }

    /**
     * GET /api/universities/{universityId}/temporary-codes
     * List temporary codes (with filters)
     */
    public function index(Request $request, int $universityId): JsonResponse
    {
        $query = TemporaryAccessCode::where('universite_id', $universityId)
            ->with(['student.user', 'classroom', 'generatedBy'])
            ->orderByDesc('created_at');

        // Filters
        if ($request->has('active_only') && $request->boolean('active_only')) {
            $query->where('est_utilise', false)->where('expire_le', '>', now());
        }
        if ($request->filled('student_id')) {
            $query->where('etudiant_id', $request->student_id);
        }

        $limit = min((int) ($request->limit ?? 50), 100);
        $offset = (int) ($request->offset ?? 0);

        $total = $query->count();
        $codes = $query->skip($offset)->take($limit)->get();

        return response()->json([
            'data' => $codes->map(fn($code) => [
                'id' => $code->id,
                'code' => $code->code,
                'student' => [
                    'id' => $code->student->id,
                    'name' => $code->student->user->prenom . ' ' . $code->student->user->nom,
                    'registrationNumber' => $code->student->numero_matricule,
                ],
                'classroom' => $code->classroom?->nom,
                'reason' => $code->raison,
                'expiresAt' => $code->expire_le->toISOString(),
                'isExpired' => $code->expire_le->isPast(),
                'isUsed' => $code->est_utilise,
                'usedAt' => $code->utilise_le?->toISOString(),
                'generatedBy' => $code->generatedBy->prenom . ' ' . $code->generatedBy->nom,
                'createdAt' => $code->created_at->toISOString(),
            ]),
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    /**
     * POST /api/universities/{universityId}/temporary-codes/validate
     * Validate and use a temporary code (called from RFID reader or student mobile app)
     * This is a PUBLIC endpoint (no JWT required) - used by door readers
     */
    public function validateCode(Request $request, int $universityId): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
            'classroom_id' => ['required', 'integer', 'exists:salles,id'],
        ]);

        $tempCode = TemporaryAccessCode::where('code', strtoupper($request->code))
            ->where('universite_id', $universityId)
            ->where('est_utilise', false)
            ->where('expire_le', '>', now())
            ->with(['student.user', 'classroom'])
            ->first();

        if (!$tempCode) {
            return response()->json([
                'status' => 'refused',
                'message' => 'Code invalide, expiré ou déjà utilisé.',
            ], 403);
        }

        // Optional: check if classroom matches
        if ($tempCode->salle_id && $tempCode->salle_id !== (int) $request->classroom_id) {
            return response()->json([
                'status' => 'refused',
                'message' => 'Ce code n\'est pas valide pour cette salle.',
            ], 403);
        }

        // Mark as used
        $tempCode->markAsUsed();

        // Create attendance record if timetable_entry exists
        if ($tempCode->seance_id) {
            \App\Models\Attendance::updateOrCreate(
                [
                    'seance_id'   => $tempCode->seance_id,
                    'etudiant_id' => $tempCode->etudiant_id,
                ],
                [
                    'universite_id'        => $universityId,
                    'statut'               => 'late',
                    'scanne_le'            => now(),
                    'methode_verification' => 'temporary_code',
                    'notes'                => 'Accès par code temporaire: ' . $tempCode->raison,
                ]
            );
        }

        AuditLog::create([
            'universite_id'  => $universityId,
            'utilisateur_id' => $tempCode->student->utilisateur_id,
            'type_ressource' => 'TemporaryAccessCode',
            'id_ressource'   => $tempCode->id,
            'action'         => 'used',
            'adresse_ip'     => $request->ip(),
        ]);

        return response()->json([
            'status' => 'granted',
            'message' => 'Accès autorisé par code temporaire.',
            'student' => [
                'id'                 => $tempCode->student->id,
                'name'               => $tempCode->student->user->prenom . ' ' . $tempCode->student->user->nom,
                'registrationNumber' => $tempCode->student->numero_matricule,
            ],
        ]);
    }

    /**
     * DELETE /api/universities/{universityId}/temporary-codes/{id}
     * Revoke a temporary code
     */
    public function destroy(Request $request, int $universityId, string $id): JsonResponse
    {
        $code = TemporaryAccessCode::where('id', $id)
            ->where('universite_id', $universityId)
            ->firstOrFail();

        if ($code->est_utilise) {
            return response()->json([
                'message' => 'Ce code a déjà été utilisé et ne peut pas être révoqué.',
            ], 422);
        }

        $code->update(['expire_le' => now()]); // Expire immediately

        AuditLog::create([
            'universite_id'  => $universityId,
            'utilisateur_id' => auth()->id(),
            'type_ressource' => 'TemporaryAccessCode',
            'id_ressource'   => $code->id,
            'action'         => 'revoked',
            'adresse_ip'     => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Code temporaire révoqué.',
        ]);
    }
}
