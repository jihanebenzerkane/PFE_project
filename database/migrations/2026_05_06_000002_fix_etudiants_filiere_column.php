<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change filiere from enum to plain string to support full names
        DB::statement("ALTER TABLE etudiants MODIFY filiere VARCHAR(255) NOT NULL DEFAULT 'Inconnue'");
    }

    public function down(): void
    {
        // Revert to enum (best effort)
        DB::statement("ALTER TABLE etudiants MODIFY filiere ENUM('GI','TDIA','ID') NOT NULL");
    }
};
