<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Projet extends Model
{
    protected $table = "projets";
    protected $fillable = ["title", "description", "entreprise", "statut", "etudiant_id"];
    public function etudiant(){
        return $this->belongsTo(etudiant::class);
    }
}
