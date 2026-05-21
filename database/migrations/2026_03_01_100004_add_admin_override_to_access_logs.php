<?php

use Illuminate\Database\Migrations\Migration;

// No-op : colonnes admin override déjà incluses dans create_access_logs_table (journaux_acces).
return new class extends Migration
{
    public function up(): void {}
    public function down(): void {}
};
