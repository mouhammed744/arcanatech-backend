<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Filiere;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * ReportsController — Génération de rapports PDF et CSV
 *
 * Routes :
 *   POST /api/reports/generate
 *   GET  /api/reports/history
 */
class ReportsController extends Controller
{
    /**
     * POST /api/reports/generate
     *
     * Body JSON :
     *   type      : 'global' | 'class' | 'individual'
     *   format    : 'pdf' | 'csv'
     *   startDate : YYYY-MM-DD (optionnel)
     *   endDate   : YYYY-MM-DD (optionnel)
     *   targetId  : string — matricule ou ID étudiant (si type = individual)
     */
    public function generate(Request $request)
    {
        $request->validate([
            'type'      => 'required|in:global,class,individual',
            'format'    => 'required|in:pdf,csv',
            'startDate' => 'nullable|date',
            'endDate'   => 'nullable|date|after_or_equal:startDate',
            'targetId'  => 'nullable|string',
        ]);

        $universityId = auth()->user()->universite_id;
        $type         = $request->input('type');
        $format       = $request->input('format');
        $startDate    = $request->input('startDate') ?? Carbon::today()->subDays(30)->toDateString();
        $endDate      = $request->input('endDate')   ?? Carbon::today()->toDateString();
        $targetId     = $request->input('targetId');

        $data = match ($type) {
            'individual' => $this->buildIndividualData($universityId, $targetId, $startDate, $endDate),
            'class'      => $this->buildClassData($universityId, $startDate, $endDate),
            default      => $this->buildGlobalData($universityId, $startDate, $endDate),
        };

        $filename = 'rapport_' . $type . '_' . now()->format('Y-m-d');

        return $format === 'pdf'
            ? $this->generatePdf($data, $type, $startDate, $endDate, $filename)
            : $this->generateCsv($data, $type, $filename);
    }

    /**
     * GET /api/reports/history
     * (Liste simplifiée — extensible avec un modèle Report en base si besoin)
     */
    public function history()
    {
        return response()->json(['data' => []]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Collecte des données
    // ──────────────────────────────────────────────────────────────────────

    private function buildGlobalData(int $universityId, string $start, string $end): array
    {
        $total    = Student::where('universite_id', $universityId)->count();
        $baseQ    = Attendance::where('universite_id', $universityId)
                              ->whereBetween('scanne_le', [$start . ' 00:00:00', $end . ' 23:59:59']);

        $present  = (clone $baseQ)->where('statut', 'present')->count();
        $late     = (clone $baseQ)->where('statut', 'late')->count();
        $absent   = (clone $baseQ)->where('statut', 'absent')->count();
        $scanned  = $present + $late;
        $rate     = $scanned > 0 ? round($present / $scanned * 100, 1) : 0;

        // Classement des 20 meilleurs étudiants
        $rankings = Attendance::where('presences.universite_id', $universityId)
            ->whereBetween('presences.scanne_le', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->join('etudiants', 'etudiants.id', '=', 'presences.etudiant_id')
            ->join('utilisateurs', 'utilisateurs.id', '=', 'etudiants.utilisateur_id')
            ->leftJoin('filieres', 'filieres.id', '=', 'etudiants.filiere_id')
            ->selectRaw('
                etudiants.numero_matricule AS matricule,
                CONCAT(utilisateurs.prenom, \' \', utilisateurs.nom) AS nom,
                COALESCE(filieres.nom, \'—\') AS filiere,
                COUNT(*) AS total,
                SUM(presences.statut = \'present\') AS present,
                SUM(presences.statut = \'late\') AS retard,
                SUM(presences.statut = \'absent\') AS absent
            ')
            ->groupBy('etudiants.id', 'etudiants.numero_matricule', 'utilisateurs.prenom', 'utilisateurs.nom', 'filieres.nom')
            ->orderByRaw('present DESC')
            ->limit(20)
            ->get()
            ->map(function ($r) {
                $s = $r->present + $r->retard;
                return [
                    'matricule' => $r->matricule,
                    'nom'       => $r->nom,
                    'filiere'   => $r->filiere,
                    'present'   => (int) $r->present,
                    'retard'    => (int) $r->retard,
                    'absent'    => (int) $r->absent,
                    'taux'      => $s > 0 ? round($r->present / $s * 100, 1) : 0,
                ];
            })->toArray();

        return compact('total', 'present', 'late', 'absent', 'rate', 'rankings');
    }

    private function buildClassData(int $universityId, string $start, string $end): array
    {
        $filieres = Filiere::where('universite_id', $universityId)
            ->with(['students' => function ($q) {
                $q->with('user');
            }])
            ->get();

        $rows = [];
        foreach ($filieres as $filiere) {
            $studentIds = $filiere->students->pluck('id');
            if ($studentIds->isEmpty()) continue;

            $baseQ   = Attendance::where('universite_id', $universityId)
                                  ->whereIn('etudiant_id', $studentIds)
                                  ->whereBetween('scanne_le', [$start . ' 00:00:00', $end . ' 23:59:59']);

            $present = (clone $baseQ)->where('statut', 'present')->count();
            $late    = (clone $baseQ)->where('statut', 'late')->count();
            $absent  = (clone $baseQ)->where('statut', 'absent')->count();
            $scanned = $present + $late;

            $rows[] = [
                'filiere'   => $filiere->nom ?? '—',
                'code'      => $filiere->code ?? '—',
                'niveau'    => $filiere->level ?? '—',
                'etudiants' => $filiere->students->count(),
                'present'   => $present,
                'retard'    => $late,
                'absent'    => $absent,
                'taux'      => $scanned > 0 ? round($present / $scanned * 100, 1) : 0,
            ];
        }

        return ['rows' => $rows];
    }

    private function buildIndividualData(int $universityId, ?string $targetId, string $start, string $end): array
    {
        $query = Student::where('universite_id', $universityId)->with(['user', 'filiere']);

        if ($targetId) {
            $query->where(function ($q) use ($targetId) {
                $q->where('id', is_numeric($targetId) ? (int) $targetId : 0)
                  ->orWhere('numero_matricule', $targetId);
            });
        }

        $student = $query->first();

        if (!$student) {
            return ['found' => false, 'rows' => []];
        }

        $baseQ  = Attendance::where('universite_id', $universityId)
                             ->where('etudiant_id', $student->id)
                             ->whereBetween('scanne_le', [$start . ' 00:00:00', $end . ' 23:59:59']);

        $present = (clone $baseQ)->where('statut', 'present')->count();
        $late    = (clone $baseQ)->where('statut', 'late')->count();
        $absent  = (clone $baseQ)->where('statut', 'absent')->count();
        $scanned = $present + $late;

        $history = (clone $baseQ)
            ->orderBy('scanne_le', 'desc')
            ->limit(50)
            ->get()
            ->map(fn ($a) => [
                'date'   => Carbon::parse($a->scanne_le)->format('d/m/Y H:i'),
                'statut' => match ($a->statut) {
                    'present' => 'Présent',
                    'late'    => 'En retard',
                    'absent'  => 'Absent',
                    default   => $a->statut,
                },
            ])->toArray();

        return [
            'found'     => true,
            'nom'       => $student->user?->full_name ?? 'Inconnu',
            'matricule' => $student->numero_matricule,
            'filiere'   => $student->filiere?->nom ?? '—',
            'niveau'    => $student->niveau,
            'present'   => $present,
            'retard'    => $late,
            'absent'    => $absent,
            'taux'      => $scanned > 0 ? round($present / $scanned * 100, 1) : 0,
            'history'   => $history,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // Générateurs de fichiers
    // ──────────────────────────────────────────────────────────────────────

    private function generatePdf(array $data, string $type, string $start, string $end, string $filename)
    {
        $html = $this->buildHtml($data, $type, $start, $end);

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
            ]);

        return $pdf->download($filename . '.pdf');
    }

    private function generateCsv(array $data, string $type, string $filename)
    {
        $rows = $this->buildCsvRows($data, $type);

        $csv = implode("\n", array_map(
            fn ($row) => implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', $v) . '"', $row)),
            $rows
        ));

        return response("\xEF\xBB\xBF" . $csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Construction du HTML (PDF)
    // ──────────────────────────────────────────────────────────────────────

    private function buildHtml(array $data, string $type, string $start, string $end): string
    {
        $title = match ($type) {
            'individual' => 'Rapport individuel',
            'class'      => 'Rapport par formation',
            default      => 'Rapport global',
        };
        $period = Carbon::parse($start)->format('d/m/Y') . ' — ' . Carbon::parse($end)->format('d/m/Y');
        $generated = now()->format('d/m/Y à H:i');

        $body = match ($type) {
            'individual' => $this->htmlIndividual($data),
            'class'      => $this->htmlClass($data),
            default      => $this->htmlGlobal($data),
        };

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1e293b; padding: 32px; }
  h1 { font-size: 20px; font-weight: 700; color: #4F46E5; margin-bottom: 4px; }
  .subtitle { font-size: 12px; color: #64748b; margin-bottom: 6px; }
  .meta { font-size: 10px; color: #94a3b8; margin-bottom: 28px; }
  .kpi-grid { display: table; width: 100%; margin-bottom: 24px; border-spacing: 8px; }
  .kpi { display: table-cell; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 14px; text-align: center; width: 25%; }
  .kpi-val { font-size: 28px; font-weight: 800; color: #4F46E5; }
  .kpi-lbl { font-size: 10px; color: #64748b; margin-top: 2px; }
  h2 { font-size: 13px; font-weight: 700; color: #334155; margin: 20px 0 10px; border-bottom: 2px solid #E2E8F0; padding-bottom: 6px; }
  table { width: 100%; border-collapse: collapse; font-size: 10px; }
  th { background: #4F46E5; color: #fff; padding: 8px 10px; text-align: left; font-weight: 600; }
  td { padding: 7px 10px; border-bottom: 1px solid #F1F5F9; }
  tr:nth-child(even) td { background: #F8FAFC; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 99px; font-size: 9px; font-weight: 700; }
  .badge-green  { background: #DCFCE7; color: #16A34A; }
  .badge-yellow { background: #FEF9C3; color: #CA8A04; }
  .badge-red    { background: #FEE2E2; color: #DC2626; }
  .footer { margin-top: 40px; font-size: 9px; color: #CBD5E1; text-align: center; border-top: 1px solid #E2E8F0; padding-top: 10px; }
</style>
</head>
<body>
  <h1>UniAccess · {$title}</h1>
  <p class="subtitle">Période : {$period}</p>
  <p class="meta">Généré le {$generated}</p>
  {$body}
  <div class="footer">UniAccess — Système de gestion universitaire &nbsp;|&nbsp; {$generated}</div>
</body>
</html>
HTML;
    }

    private function htmlGlobal(array $d): string
    {
        $rows = '';
        foreach ($d['rankings'] as $i => $r) {
            $cls = $r['taux'] >= 80 ? 'badge-green' : ($r['taux'] >= 60 ? 'badge-yellow' : 'badge-red');
            $rows .= "<tr>
                <td>" . ($i + 1) . "</td>
                <td>{$r['nom']}</td>
                <td>{$r['matricule']}</td>
                <td>{$r['filiere']}</td>
                <td>{$r['present']}</td>
                <td>{$r['retard']}</td>
                <td>{$r['absent']}</td>
                <td><span class=\"badge {$cls}\">{$r['taux']} %</span></td>
            </tr>";
        }

        return "
        <div class='kpi-grid'>
          <div class='kpi'><div class='kpi-val'>{$d['total']}</div><div class='kpi-lbl'>Étudiants</div></div>
          <div class='kpi'><div class='kpi-val'>{$d['present']}</div><div class='kpi-lbl'>Présences</div></div>
          <div class='kpi'><div class='kpi-val'>{$d['late']}</div><div class='kpi-lbl'>Retards</div></div>
          <div class='kpi'><div class='kpi-val'>{$d['rate']} %</div><div class='kpi-lbl'>Ponctualité</div></div>
        </div>
        <h2>Classement étudiants (top 20)</h2>
        <table>
          <thead><tr><th>#</th><th>Nom</th><th>Matricule</th><th>Filière</th><th>Présent</th><th>Retard</th><th>Absent</th><th>Taux</th></tr></thead>
          <tbody>{$rows}</tbody>
        </table>";
    }

    private function htmlClass(array $d): string
    {
        $rows = '';
        foreach ($d['rows'] as $r) {
            $cls = $r['taux'] >= 80 ? 'badge-green' : ($r['taux'] >= 60 ? 'badge-yellow' : 'badge-red');
            $rows .= "<tr>
                <td>{$r['filiere']}</td><td>{$r['code']}</td><td>{$r['niveau']}</td>
                <td>{$r['etudiants']}</td><td>{$r['present']}</td><td>{$r['retard']}</td>
                <td>{$r['absent']}</td><td><span class=\"badge {$cls}\">{$r['taux']} %</span></td>
            </tr>";
        }

        return "
        <h2>Résultats par formation</h2>
        <table>
          <thead><tr><th>Filière</th><th>Code</th><th>Niveau</th><th>Étudiants</th><th>Présent</th><th>Retard</th><th>Absent</th><th>Taux</th></tr></thead>
          <tbody>{$rows}</tbody>
        </table>";
    }

    private function htmlIndividual(array $d): string
    {
        if (!$d['found']) {
            return "<p style='color:#ef4444;margin-top:20px'>Étudiant introuvable.</p>";
        }

        $cls  = $d['taux'] >= 80 ? 'badge-green' : ($d['taux'] >= 60 ? 'badge-yellow' : 'badge-red');
        $rows = '';
        foreach ($d['history'] as $h) {
            $c = match ($h['statut']) {
                'Présent'    => 'badge-green',
                'En retard'  => 'badge-yellow',
                default      => 'badge-red',
            };
            $rows .= "<tr><td>{$h['date']}</td><td><span class=\"badge {$c}\">{$h['statut']}</span></td></tr>";
        }

        return "
        <div class='kpi-grid'>
          <div class='kpi'><div class='kpi-val'>{$d['present']}</div><div class='kpi-lbl'>Présences</div></div>
          <div class='kpi'><div class='kpi-val'>{$d['retard']}</div><div class='kpi-lbl'>Retards</div></div>
          <div class='kpi'><div class='kpi-val'>{$d['absent']}</div><div class='kpi-lbl'>Absences</div></div>
          <div class='kpi'><div class='kpi-val'>{$d['taux']} %</div><div class='kpi-lbl'>Ponctualité</div></div>
        </div>
        <p><strong>Étudiant :</strong> {$d['nom']} &nbsp;|&nbsp; <strong>Matricule :</strong> {$d['matricule']} &nbsp;|&nbsp; <strong>Filière :</strong> {$d['filiere']} ({$d['niveau']})</p>
        <h2>Historique des présences</h2>
        <table>
          <thead><tr><th>Date / Heure</th><th>Statut</th></tr></thead>
          <tbody>{$rows}</tbody>
        </table>";
    }

    // ──────────────────────────────────────────────────────────────────────
    // Construction du CSV
    // ──────────────────────────────────────────────────────────────────────

    private function buildCsvRows(array $data, string $type): array
    {
        return match ($type) {
            'individual' => $this->csvIndividual($data),
            'class'      => $this->csvClass($data),
            default      => $this->csvGlobal($data),
        };
    }

    private function csvGlobal(array $d): array
    {
        $rows = [
            ['Résumé global'],
            ['Total étudiants', $d['total']],
            ['Présences', $d['present']],
            ['Retards', $d['late']],
            ['Absences', $d['absent']],
            ['Taux de ponctualité', $d['rate'] . '%'],
            [],
            ['#', 'Nom', 'Matricule', 'Filière', 'Présent', 'Retard', 'Absent', 'Taux (%)'],
        ];
        foreach ($d['rankings'] as $i => $r) {
            $rows[] = [$i + 1, $r['nom'], $r['matricule'], $r['filiere'], $r['present'], $r['retard'], $r['absent'], $r['taux']];
        }
        return $rows;
    }

    private function csvClass(array $d): array
    {
        $rows = [['Filière', 'Code', 'Niveau', 'Étudiants', 'Présent', 'Retard', 'Absent', 'Taux (%)']];
        foreach ($d['rows'] as $r) {
            $rows[] = [$r['filiere'], $r['code'], $r['niveau'], $r['etudiants'], $r['present'], $r['retard'], $r['absent'], $r['taux']];
        }
        return $rows;
    }

    private function csvIndividual(array $d): array
    {
        if (!$d['found']) return [['Étudiant introuvable']];

        $rows = [
            ['Nom', $d['nom']],
            ['Matricule', $d['matricule']],
            ['Filière', $d['filiere']],
            ['Niveau', $d['niveau']],
            ['Présences', $d['present']],
            ['Retards', $d['retard']],
            ['Absences', $d['absent']],
            ['Taux', $d['taux'] . '%'],
            [],
            ['Date / Heure', 'Statut'],
        ];
        foreach ($d['history'] as $h) {
            $rows[] = [$h['date'], $h['statut']];
        }
        return $rows;
    }
}
