<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\AccessLog;
use App\Models\Classroom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * StatsController — Statistiques globales pour le tableau de bord admin
 *
 * Routes :
 *   GET /api/stats/kpis
 *   GET /api/stats/punctuality-over-time
 *   GET /api/stats/student-rankings
 *   GET /api/stats/by-course
 */
class StatsController extends Controller
{
    /* ══════════════════════════════════════════════════════════════
     * GET /api/stats/kpis
     *
     * Retourne les indicateurs clés du tableau de bord :
     *   totalStudents, totalScansToday, punctualityRateToday,
     *   lateCountToday, absentCountToday, presentCountToday,
     *   activeReadersCount, totalReadersCount, alertsCount,
     *   comparedToYesterday { scans, punctuality, late }
     * ══════════════════════════════════════════════════════════════ */
    public function kpis(Request $request): JsonResponse
    {
        $universityId = auth()->user()->universite_id;
        $today        = Carbon::today()->toDateString();
        $yesterday    = Carbon::yesterday()->toDateString();

        // ── Étudiants ──────────────────────────────────────────────
        $totalStudents = Student::where('universite_id', $universityId)->count();

        // ── Présences aujourd'hui ──────────────────────────────────
        $todayBase = Attendance::where('universite_id', $universityId)
                               ->whereDate('scanne_le', $today);

        $presentToday      = (clone $todayBase)->where('statut', 'present')->count();
        $lateToday         = (clone $todayBase)->where('statut', 'late')->count();
        $absentToday       = (clone $todayBase)->where('statut', 'absent')->count();
        $totalScansToday   = (clone $todayBase)->count();   // tous les enregistrements du jour

        $scannedToday         = $presentToday + $lateToday;
        $punctualityRateToday = $scannedToday > 0
            ? round(($presentToday / $scannedToday) * 100, 1)
            : 0.0;

        // ── Présences hier (pour la comparaison) ──────────────────
        $yesterdayBase = Attendance::where('universite_id', $universityId)
                                   ->whereDate('scanne_le', $yesterday);

        $presentYesterday      = (clone $yesterdayBase)->where('statut', 'present')->count();
        $lateYesterday         = (clone $yesterdayBase)->where('statut', 'late')->count();
        $totalScansYesterday   = (clone $yesterdayBase)->count();
        $scannedYesterday      = $presentYesterday + $lateYesterday;
        $punctualityYesterday  = $scannedYesterday > 0
            ? round(($presentYesterday / $scannedYesterday) * 100, 1)
            : 0.0;

        // Variation en % par rapport à hier
        $scansChange = $totalScansYesterday > 0
            ? round((($totalScansToday - $totalScansYesterday) / $totalScansYesterday) * 100, 1)
            : 0.0;
        $punctualityChange = round($punctualityRateToday - $punctualityYesterday, 1);
        $lateChange = $lateYesterday > 0
            ? round((($lateToday - $lateYesterday) / $lateYesterday) * 100, 1)
            : 0.0;

        // ── Lecteurs RFID (salles distinctes avec logs d'accès) ────
        $totalReadersCount = AccessLog::where('universite_id', $universityId)
            ->whereNotNull('salle_id')
            ->distinct('salle_id')
            ->count('salle_id');

        $activeReadersCount = AccessLog::where('universite_id', $universityId)
            ->whereNotNull('salle_id')
            ->whereDate('scanne_le', $today)
            ->distinct('salle_id')
            ->count('salle_id');

        // Repli : si aucun log d'accès, utiliser le nombre de salles
        if ($totalReadersCount === 0) {
            $totalReadersCount  = Classroom::where('universite_id', $universityId)->count();
            $activeReadersCount = 0;
        }

        return response()->json([
            'totalStudents'        => $totalStudents,
            'totalScansToday'      => $totalScansToday,
            'punctualityRateToday' => $punctualityRateToday,
            'lateCountToday'       => $lateToday,
            'absentCountToday'     => $absentToday,
            'presentCountToday'    => $presentToday,
            'activeReadersCount'   => $activeReadersCount,
            'totalReadersCount'    => $totalReadersCount,
            'alertsCount'          => 0,
            'comparedToYesterday'  => [
                'scans'       => $scansChange,
                'punctuality' => $punctualityChange,
                'late'        => $lateChange,
            ],
        ]);
    }

    /* ══════════════════════════════════════════════════════════════
     * GET /api/stats/punctuality-over-time
     *
     * Paramètres (query) :
     *   startDate  YYYY-MM-DD  (défaut : -30 jours)
     *   endDate    YYYY-MM-DD  (défaut : aujourd'hui)
     *
     * Retourne un tableau de { date, punctualityRate, lateCount,
     *                          absentCount, presentCount }
     * ══════════════════════════════════════════════════════════════ */
    public function punctualityOverTime(Request $request): JsonResponse
    {
        $universityId = auth()->user()->universite_id;

        $startDate = $request->query('startDate', Carbon::today()->subDays(30)->toDateString());
        $endDate   = $request->query('endDate',   Carbon::today()->toDateString());

        // Récupérer les comptages groupés par date + statut
        $rows = Attendance::where('universite_id', $universityId)
            ->whereDate('scanne_le', '>=', $startDate)
            ->whereDate('scanne_le', '<=', $endDate)
            ->selectRaw('DATE(scanne_le) as date, statut, COUNT(*) as total')
            ->groupBy('date', 'statut')
            ->orderBy('date')
            ->get();

        // Pivoter par date
        $byDate = [];
        foreach ($rows as $row) {
            $d = $row->date;
            if (!isset($byDate[$d])) {
                $byDate[$d] = ['present' => 0, 'late' => 0, 'absent' => 0];
            }
            if (array_key_exists($row->statut, $byDate[$d])) {
                $byDate[$d][$row->statut] = (int) $row->total;
            }
        }

        $data = [];
        foreach ($byDate as $date => $counts) {
            $scanned         = $counts['present'] + $counts['late'];
            $punctualityRate = $scanned > 0
                ? round(($counts['present'] / $scanned) * 100, 1)
                : 0.0;

            $data[] = [
                'date'            => $date,
                'punctualityRate' => $punctualityRate,
                'lateCount'       => $counts['late'],
                'absentCount'     => $counts['absent'],
                'presentCount'    => $counts['present'],
            ];
        }

        return response()->json($data);
    }

    /* ══════════════════════════════════════════════════════════════
     * GET /api/stats/student-rankings
     *
     * Classement des étudiants par taux de ponctualité
     * ══════════════════════════════════════════════════════════════ */
    public function studentRankings(Request $request): JsonResponse
    {
        $universityId = auth()->user()->universite_id;

        $startDate = $request->query('startDate');
        $endDate   = $request->query('endDate');

        $query = Attendance::where('presences.universite_id', $universityId)
            ->join('etudiants', 'etudiants.id', '=', 'presences.etudiant_id')
            ->join('utilisateurs', 'utilisateurs.id', '=', 'etudiants.utilisateur_id')
            ->leftJoin('filieres', 'filieres.id', '=', 'etudiants.filiere_id')
            ->selectRaw('
                etudiants.id            AS student_id,
                etudiants.numero_matricule AS student_number,
                CONCAT(utilisateurs.prenom, \' \', utilisateurs.nom) AS student_name,
                COALESCE(filieres.nom, \'\') AS program_name,
                COUNT(*)                AS total,
                SUM(presences.statut = \'present\') AS present_count,
                SUM(presences.statut = \'late\')    AS late_count,
                SUM(presences.statut = \'absent\')  AS absent_count
            ')
            ->groupBy('etudiants.id', 'etudiants.numero_matricule',
                      'utilisateurs.prenom', 'utilisateurs.nom', 'filieres.nom');

        if ($startDate) {
            $query->whereDate('presences.scanne_le', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('presences.scanne_le', '<=', $endDate);
        }

        $results = $query->orderByRaw('present_count DESC')->limit(50)->get();

        $rankings = $results->map(function ($row, $idx) {
            $scanned         = $row->present_count + $row->late_count;
            $punctualityRate = $scanned > 0
                ? round(($row->present_count / $scanned) * 100, 1)
                : 0.0;

            return [
                'rank'            => $idx + 1,
                'studentId'       => (string) $row->student_id,
                'studentName'     => $row->student_name,
                'studentNumber'   => $row->student_number,
                'programName'     => $row->program_name,
                'punctualityRate' => $punctualityRate,
                'lateCount'       => (int) $row->late_count,
                'absentCount'     => (int) $row->absent_count,
            ];
        });

        return response()->json($rankings);
    }

    /* ══════════════════════════════════════════════════════════════
     * GET /api/stats/by-course
     *
     * Statistiques par cours
     * ══════════════════════════════════════════════════════════════ */
    public function byCourse(Request $request): JsonResponse
    {
        $universityId = auth()->user()->universite_id;

        $startDate = $request->query('startDate');
        $endDate   = $request->query('endDate');

        $query = Attendance::where('presences.universite_id', $universityId)
            ->join('seances', 'seances.id', '=', 'presences.seance_id')
            ->join('cours', 'cours.id', '=', 'seances.cours_id')
            ->leftJoin('enseignants', 'enseignants.id', '=', 'cours.enseignant_id')
            ->leftJoin('utilisateurs', 'utilisateurs.id', '=', 'enseignants.utilisateur_id')
            ->selectRaw('
                cours.id   AS course_id,
                cours.nom  AS course_name,
                cours.code AS course_code,
                CONCAT(COALESCE(utilisateurs.prenom, \'\'), \' \', COALESCE(utilisateurs.nom, \'\')) AS teacher_name,
                COUNT(DISTINCT seances.id)  AS total_sessions,
                COUNT(presences.id)         AS total_attendances,
                SUM(presences.statut = \'present\') AS present_count,
                SUM(presences.statut = \'late\')    AS late_count,
                SUM(presences.statut = \'absent\')  AS absent_count
            ')
            ->groupBy('cours.id', 'cours.nom', 'cours.code',
                      'utilisateurs.prenom', 'utilisateurs.nom');

        if ($startDate) {
            $query->whereDate('presences.scanne_le', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('presences.scanne_le', '<=', $endDate);
        }

        $results = $query->orderBy('cours.nom')->get();

        $stats = $results->map(function ($row) {
            $total           = (int) $row->total_attendances;
            $presentCount    = (int) $row->present_count;
            $lateCount       = (int) $row->late_count;
            $absentCount     = (int) $row->absent_count;
            $scanned         = $presentCount + $lateCount;

            $avgPunctuality  = $scanned > 0 ? round(($presentCount / $scanned) * 100, 1) : 0.0;
            $latePercentage  = $total > 0   ? round(($lateCount    / $total)   * 100, 1) : 0.0;
            $absentPercentage= $total > 0   ? round(($absentCount  / $total)   * 100, 1) : 0.0;
            $presentPercentage = $total > 0 ? round(($presentCount / $total)   * 100, 1) : 0.0;

            return [
                'courseId'          => (string) $row->course_id,
                'courseName'        => $row->course_name,
                'courseCode'        => $row->course_code,
                'teacherName'       => trim($row->teacher_name) ?: 'Non assigné',
                'averagePunctuality'=> $avgPunctuality,
                'totalSessions'     => (int) $row->total_sessions,
                'totalAttendances'  => $total,
                'latePercentage'    => $latePercentage,
                'absentPercentage'  => $absentPercentage,
                'presentPercentage' => $presentPercentage,
            ];
        });

        return response()->json($stats);
    }
}
