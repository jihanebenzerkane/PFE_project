<?php
namespace App\Repositories;
use App\Models\Projet;

class ProjetDAO {
    public function create(array $data): Projet {
        return Projet::create($data);
    }

    public function findById(int $id): ?Projet {
        return Projet::with(['etudiant', 'etudiant2', 'encadrant'])->find($id);
    }

    public function findBySujet(string $sujet): ?Projet {
        return Projet::where('sujet', $sujet)->first();
    }

    public function findByEtudiant(int $etudiantId): ?Projet {
        return Projet::where('etudiant_id', $etudiantId)
                     ->orWhere('etudiant2_id', $etudiantId)
                     ->first();
    }

    public function all(): \Illuminate\Database\Eloquent\Collection {
        return Projet::with(['etudiant', 'etudiant2', 'encadrant'])->get();
    }
}