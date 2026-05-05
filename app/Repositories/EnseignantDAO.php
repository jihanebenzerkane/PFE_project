<?php
namespace App\Repositories;

use App\Models\Enseignant;

class EnseignantDAO {
    public function create(array $data): Enseignant {
        return Enseignant::create($data);
    }

    public function findByNomPrenom(string $nom, string $prenom): ?Enseignant {
        return Enseignant::where('nom', $nom)->where('prenom', $prenom)->first();
    }

    public function all(): \Illuminate\Database\Eloquent\Collection {
        return Enseignant::all();
    }
}