<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Creneau extends Model
{
    protected $table = 'creneaux';
    protected $fillable = ['date', 'heure_debut', 'heure_fin'];

    public function soutenances()
    {
        return $this->hasMany(Soutenance::class);
    }
}
