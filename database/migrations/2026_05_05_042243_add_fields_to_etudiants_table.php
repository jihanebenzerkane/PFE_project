<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::table('etudiants', function (Blueprint $table) {
        $table->string('cne')->nullable()->after('id');
        $table->string('email_personnel')->nullable()->after('filiere');
        $table->string('email_academique')->nullable()->after('email_personnel');
    });
}

public function down(): void
{
    Schema::table('etudiants', function (Blueprint $table) {
        $table->dropColumn(['cne', 'email_personnel', 'email_academique']);
    });
}
};
