<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('etudiants')->onDelete('cascade');
            $table->foreignId('etudiant2_id')->nullable()->constrained('etudiants')->nullOnDelete();
            $table->foreignId('encadrant_id')->constrained('enseignants')->onDelete('cascade');
            $table->string('sujet');
            $table->string('nom_entreprise')->nullable();
            $table->string('encadrant_industriel')->nullable();
            $table->enum('langue_soutenance', ['Français', 'Anglais', 'Arabe'])->default('Français');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projets');
    }
};
