<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ClassroomController — Listage des salles de cours
 *
 * ENDPOINTS :
 * GET /api/universities/{uniId}/classrooms   → liste des salles actives
 */
class ClassroomController extends Controller
{
    // ──────────────────────────────────────────
    // GET /universities/{uniId}/classrooms
    // ──────────────────────────────────────────
    public function index(Request $request, int $universityId): JsonResponse
    {
        $classrooms = Classroom::where('universite_id', $universityId)
            ->where('est_active', true)
            ->orderBy('nom')
            ->get(['id', 'nom', 'batiment', 'numero_salle', 'capacite', 'type']);

        return response()->json(['data' => $classrooms]);
    }
}
