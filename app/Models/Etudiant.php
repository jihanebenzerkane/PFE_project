<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Etudiant extends Model{
 protected $table = "etudiants";
protected $fillable = ['cne', 'nom', 'prenom', 'filiere', 'email_personnel', 'email_academique'];
  public function projets(){
        return $this->hasMany(Projet::class);
    }
}