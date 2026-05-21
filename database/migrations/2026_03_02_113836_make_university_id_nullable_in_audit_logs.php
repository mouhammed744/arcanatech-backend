<?php

use Illuminate\Database\Migrations\Migration;

// No-op : universite_id déjà nullable dans create_audit_logs_table (journaux_audit).
return new class extends Migration
{
    public function up(): void {}
    public function down(): void {}
};
