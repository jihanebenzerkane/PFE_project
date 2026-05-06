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
        Schema::table('creneaux', function (Blueprint $table) {
            $table->integer('capacite')->default(5)->after('heure_fin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('creneaux', function (Blueprint $table) {
            $table->dropColumn('capacite');
        });
    }
};
