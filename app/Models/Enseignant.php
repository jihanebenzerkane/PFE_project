<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enseignant extends Model {
    use HasFactory;

    protected $table = 'enseignants';
    protected $fillable = ['nom', 'prenom', 'discipline'];

    public function projets() {
        return $this->hasMany(Projet::class, 'enseignant_id');
    }
}