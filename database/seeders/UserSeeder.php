<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\University;
use Illuminate\Database\Seeder;

/**
 * UserSeeder — Compte administrateur LCS
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $university = University::where('code', 'LCS')->first()
                   ?? University::first();

        User::create([
            'universite_id' => $university?->id,
            'prenom'        => 'Théophile',
            'nom'           => 'ZANNOU',
            'email'         => 'admin@lcs.edu',
            'password'      => 'Admin@2024!',
            'telephone'     => '+229 97 12 34 56',
            'role'          => 'admin',
            'est_actif'     => true,
        ]);
    }
}
