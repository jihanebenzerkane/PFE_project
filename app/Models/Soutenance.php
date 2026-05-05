<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Models\Etudiant;
use App\Models\Enseignant;
use App\Models\Jury;
use App\Models\Salle;
use App\Models\Creneau;

class Soutenance extends Model
{
    use HasFactory;

    protected $table = 'soutenances';

    protected $fillable = [
        'etudiant_id',
        'encadrant_id',
        'jury_id',
        'salle_id',
        'creneau_id',
        'langue',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function encadrant()
    {
        return $this->belongsTo(Enseignant::class, 'encadrant_id');
    }

    public function jury()
    {
        return $this->belongsTo(Jury::class, 'jury_id');
    }

    public function salle()
    {
        return $this->belongsTo(Salle::class);
    }

    public function creneau()
    {
        return $this->belongsTo(Creneau::class);
    }
}