<?php

use Illuminate\Database\Migrations\Migration;

// No-op : colonne chemin_avatar déjà incluse dans create_users_table (utilisateurs).
return new class extends Migration
{
    public function up(): void {}
    public function down(): void {}
};
