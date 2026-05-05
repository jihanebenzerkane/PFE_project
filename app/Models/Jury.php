<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jury extends Model
{
    protected $fillable = [];

    public function enseignants()
    {
        return $this->belongsToMany(Enseignant::class, 'jury_enseignant')
                    ->withPivot('role');
    }

    public function soutenances()
    {
        return $this->hasMany(Soutenance::class);
    }
}
