<?php

use Illuminate\Database\Migrations\Migration;

// No-op : colonnes 2FA method/code déjà incluses dans create_users_table (utilisateurs).
return new class extends Migration
{
    public function up(): void {}
    public function down(): void {}
};
