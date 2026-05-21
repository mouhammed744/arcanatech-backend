<?php

use Illuminate\Database\Migrations\Migration;

// No-op : colonnes type_carte et utilisateur_id déjà incluses dans create_rfid_cards_table (cartes_rfid).
return new class extends Migration
{
    public function up(): void {}
    public function down(): void {}
};
