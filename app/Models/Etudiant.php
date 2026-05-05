<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Etudiant extends Model
{
    protected $fillable = ['nom', 'prenom', 'filiere'];

    public function projet()
    {
        return $this->hasOne(Projet::class);
    }
}
