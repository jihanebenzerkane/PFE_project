<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Soutenance extends Model
{
    protected $fillable = ['projet_id', 'creneau_id', 'jury_id', 'salle'];

    public function projet()
    {
        return $this->belongsTo(Projet::class);
    }

    public function creneau()
    {
        return $this->belongsTo(Creneau::class);
    }

    public function jury()
    {
        return $this->belongsTo(Jury::class);
    }
}
