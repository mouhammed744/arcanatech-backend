<?php

use Illuminate\Database\Migrations\Migration;

// No-op : toutes les colonnes sont déjà dans create_users_table (utilisateurs).
return new class extends Migration
{
    public function up(): void {}
    public function down(): void {}
};
