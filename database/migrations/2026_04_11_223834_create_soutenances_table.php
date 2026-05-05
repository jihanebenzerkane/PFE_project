<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('soutenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('etudiants');
            $table->foreignId('encadrant_id')->constrained('encadrants');
            $table->foreignId('jury_id')->constrained('enseignants');
            $table->foreignId('salle_id')->constrained('salles');
            $table->foreignId('creneau_id')->constrained('creneaux');
            $table->enum('langue', ['FR', 'EN'])->default('FR');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soutenances');
    }
};
