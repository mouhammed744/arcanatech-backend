<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Kreait\Firebase\Factory;

class TestFirebase extends Command
{
    protected $signature = 'firebase:test';
    protected $description = 'Tester la connexion Firebase';

    public function handle()
    {
        try {
            $this->info('⏳ Connexion à Firebase...');

            $firebase = (new Factory)
                ->withServiceAccount(config('firebase.projects.app.credentials'));

            $messaging = $firebase->createMessaging();

            $this->info('✅ Connexion Firebase réussie !');
            $this->info('✅ Messaging service prêt !');

        } catch (\Exception $e) {
            $this->error('❌ Erreur : ' . $e->getMessage());
        }
    }
}