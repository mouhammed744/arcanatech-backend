<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\University;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * UniversityController - Gestion basique des universités
 */
class UniversityController extends Controller
{
    /**
     * GET /api/universities
     *
     * Liste publique des universités (pour dropdown d'inscription mobile)
     */
    public function index(): JsonResponse
    {
        $universities = University::select('id', 'code', 'nom', 'ville', 'nb_chiffres_matricule', 'prefixe_matricule', 'url_logo')
            ->orderBy('nom')
            ->get()
            ->map(fn($u) => [
                'id'              => $u->id,
                'name'            => $u->nom,
                'code'            => $u->code,
                'city'            => $u->ville,
                'studentIdDigits' => $u->nb_chiffres_matricule,
                'studentIdPrefix' => $u->prefixe_matricule,
                'logoUrl'         => $u->url_logo,
            ]);

        return response()->json(['data' => $universities]);
    }

    /**
     * GET /api/universities/{id}
     *
     * Détail d'une université
     */
    public function show(Request $request, $id)
    {
        $user = auth()->user();
        
        // Vérifier que l'université appartient au user ou qu'il est admin
        if ($user->universite_id !== (int)$id && !$user->hasRole('admin')) {
            abort(403, 'Not authorized to view this university');
        }

        $university = University::findOrFail($id);

        return response()->json([
            'id'   => $university->id,
            'name' => $university->nom,
            'code' => $university->code,
            'city' => $university->ville,
            'statistics' => [
                'total_students' => $university->students()->count(),
                'total_teachers' => $university->teachers()->count(),
                'total_courses' => $university->courses()->count(),
                'total_classrooms' => $university->classrooms()->count(),
            ],
        ]);
    }
}
