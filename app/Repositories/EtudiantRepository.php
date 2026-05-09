<?php

namespace App\Repositories;

use App\Models\Etudiant;

class EtudiantRepository
{
    public function create(array $data): Etudiant
    {
        return Etudiant::create($data); //calls Model::create() return un nv etd instance avec son id
    }

    public function updateOrCreate(array $attributes, array $values): Etudiant
    {
        return Etudiant::updateOrCreate($attributes, $values);
    }

    public function findByNomPrenom(string $nom, string $prenom): ?Etudiant
    {
        return Etudiant::where('nom', $nom)->where('prenom', $prenom)->first();
    }

    public function findByCne(string $cne): ?Etudiant
    {
        return Etudiant::where('cne', $cne)->first();
    }

    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return Etudiant::all();
    }
}
