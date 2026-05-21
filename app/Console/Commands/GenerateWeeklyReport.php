<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\University;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Course;
use App\Models\AccessLog;
use App\Models\User;
use App\Notifications\WeeklyReportNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class GenerateWeeklyReport extends Command
{
    protected $signature = 'arcana:weekly-report';

    protected $description = 'Génère et envoie un rapport hebdomadaire de présence aux administrateurs';

    public function handle(): int
    {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $reportsSent = 0;

        $universities = University::all();

        foreach ($universities as $university) {
            // Stats de la semaine
            $attendances = Attendance::where('universite_id', $university->id)
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->get();

            if ($attendances->isEmpty()) {
                continue;
            }

            $totalRecords = $attendances->count();
            $presentCount = $attendances->where('statut', 'present')->count();
            $lateCount = $attendances->where('statut', 'late')->count();
            $absentCount = $attendances->where('statut', 'absent')->count();
            $presenceRate = $totalRecords > 0
                ? round(($presentCount + $lateCount) / $totalRecords * 100, 1)
                : 0;

            // Étudiants les plus absents
            $topAbsent = Attendance::where('universite_id', $university->id)
                ->where('statut', 'absent')
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->selectRaw('etudiant_id, count(*) as absence_count')
                ->groupBy('etudiant_id')
                ->orderByDesc('absence_count')
                ->limit(5)
                ->with('student.user')
                ->get()
                ->map(fn($row) => [
                    'name'               => $row->student?->user?->full_name ?? 'Inconnu',
                    'registration_number' => $row->student?->numero_matricule ?? '-',
                    'absence_count'       => $row->absence_count,
                ]);

            // Scans RFID de la semaine
            $accessLogs = AccessLog::where('universite_id', $university->id)
                ->whereBetween('scanne_le', [$weekStart, $weekEnd])
                ->get();

            $report = [
                'university'     => $university->nom,
                'period'         => $weekStart->format('d/m/Y') . ' - ' . $weekEnd->format('d/m/Y'),
                'total_records'  => $totalRecords,
                'present'        => $presentCount,
                'late'           => $lateCount,
                'absent'         => $absentCount,
                'presence_rate'  => $presenceRate,
                'top_absent'     => $topAbsent->toArray(),
                'total_scans'    => $accessLogs->count(),
                'scans_granted'  => $accessLogs->where('statut', 'granted')->count(),
                'scans_refused'  => $accessLogs->where('statut', 'refused')->count(),
                'total_students' => Student::where('universite_id', $university->id)->count(),
                'total_courses'  => Course::where('universite_id', $university->id)->count(),
            ];

            // Envoyer aux admins de l'université
            $admins = User::where('universite_id', $university->id)
                ->where('role', 'admin')
                ->get();

            foreach ($admins as $admin) {
                $admin->notify(new WeeklyReportNotification($report));
                $reportsSent++;
            }

            Log::info("[ARCANA] Rapport hebdomadaire : {$university->nom} - Taux de présence {$presenceRate}%.");
        }

        $this->info("{$reportsSent} rapports hebdomadaires envoyés pour {$universities->count()} universités.");
        return self::SUCCESS;
    }
}
