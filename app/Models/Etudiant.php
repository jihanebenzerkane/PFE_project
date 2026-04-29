<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Etudiant extends Model{
 protected $table = "etudiant";
 protected $fillable = ["id","nom","prenom","filiere"];
}