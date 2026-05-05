<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Soutenance extends Model
{
    use HasFactory;

    protected $table = 'soutenances';

    // Uses your schema: projet_id, creneau_id, jury_id, salle (string)
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
