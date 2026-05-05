<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Projet extends Model
{
    protected $fillable = ['titre', 'etudiant_id', 'encadrant_id'];

    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function encadrant()
    {
        return $this->belongsTo(Enseignant::class, 'encadrant_id');
    }

    public function soutenance()
    {
        return $this->hasOne(Soutenance::class);
    }
}
