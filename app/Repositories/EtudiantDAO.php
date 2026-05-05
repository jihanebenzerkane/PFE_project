<?php
namespace App\Repositories;
use App\Models\Etudiant;
class EtudiantDAO{
    public function create (array $data): Etudiant{
        return Etudiant::create($data);
    }

    public function findByNomPrenom(string $nom, string $prenom): ?Etudiant {
        return Etudiant::where('nom', $nom)->where('prenom', $prenom)->first();
    }

    public function findByCne(string $cne): ?Etudiant {
        return Etudiant::where('cne', $cne)->first();
    }

    public function all(): \Illuminate\Database\Eloquent\Collection{
        return Etudiant::all();
    }
}