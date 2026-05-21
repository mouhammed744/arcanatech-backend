<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearTestData extends Command
{
    protected $signature   = 'db:clear-test-data {--force : Ignorer la confirmation}';
    protected $description = 'Supprime toutes les données de test en conservant les universités et le compte admin';

    public function handle(): int
    {
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  Cela supprimera TOUTES les données (étudiants, enseignants, cours, présences…). Continuer ?')) {
                $this->info('Opération annulée.');
                return self::SUCCESS;
            }
        }

        $this->info('🧹 Suppression des données de test...');

        DB::statement('SET session_replication_role = replica;'); // Désactive les FK temporairement (PostgreSQL)

        $tables = [
            'presences'       => 'Présences',
            'inscriptions'    => 'Inscriptions aux cours',
            'cartes_rfid'     => 'Cartes RFID',
            'seances'         => 'Séances (emploi du temps)',
            'cours'           => 'Cours',
            'salles'          => 'Salles',
            'etudiants'       => 'Étudiants',
            'enseignants'     => 'Enseignants',
            'journaux_acces'  => 'Journaux d\'accès',
            'jetons_jwt'      => 'Tokens JWT',
            'journaux_audit'  => 'Journaux d\'audit',
        ];

        foreach ($tables as $table => $label) {
            try {
                $count = DB::table($table)->count();
                DB::table($table)->truncate();
                $this->line("  ✅ <info>{$label}</info> ({$count} entrées supprimées)");
            } catch (\Throwable $e) {
                $this->line("  ⚠️  <comment>{$label}</comment> : " . $e->getMessage());
            }
        }

        // Supprimer les utilisateurs de test (garder uniquement l'admin)
        $deleted = DB::table('utilisateurs')
            ->where('role', '!=', 'admin')
            ->delete();
        $this->line("  ✅ <info>Utilisateurs de test</info> ({$deleted} supprimés, admins conservés)");

        DB::statement('SET session_replication_role = DEFAULT;'); // Réactive les FK

        $this->newLine();
        $this->info('✨ Base de données nettoyée avec succès.');
        $this->line('   → Universités conservées');
        $this->line('   → Comptes admin conservés');

        return self::SUCCESS;
    }
}
