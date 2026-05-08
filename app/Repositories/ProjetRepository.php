<?php

namespace App\Repositories;

use App\Models\Projet;

class ProjetRepository
{
    public function create(array $data): Projet
    {
        return Projet::create($data);
    }

    public function findByTitre(string $titre): ?Projet
    {
        return Projet::where('titre', $titre)->first();
    }

    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        // Eager loading — Laravel fetches everything at once, not project by project
        return Projet::with('etudiant')->get();
    }
}
