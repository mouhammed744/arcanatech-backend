<?php

use Illuminate\Database\Migrations\Migration;

// No-op : colonne jeton_jwt déjà incluse dans create_jwt_tokens_table (jetons_jwt).
return new class extends Migration
{
    public function up(): void {}
    public function down(): void {}
};
