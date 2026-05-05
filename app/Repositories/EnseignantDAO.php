<?php

namespace App\Repositories;

use App\Models\Enseignant;

class EnseignantDAO
{
    public function findAll()
    {
        return Enseignant::all();
    }

    public function getInformatiqueProfs()
    {
        return Enseignant::where('specialite', 'Informatique')->orWhere('specialite', 'info')->get();
    }

    public function findResponsablePFE()
    {
        return Enseignant::where('is_responsable_pfe', true)->first();
    }

    public function findAvailableInsSlot($creneauId){}

    public function getCurrentLoad($id){}

    public function save($enseignant){}

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
