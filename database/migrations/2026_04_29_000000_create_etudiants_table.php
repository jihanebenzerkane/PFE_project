<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Superseded by 2026_05_04_200000_create_etudiants_table.php
    }

    public function down(): void
    {
        Schema::dropIfExists('etudiants');
    }
};
