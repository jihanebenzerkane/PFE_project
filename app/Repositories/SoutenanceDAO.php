<?php

namespace App\Repositories;

use App\Models\Soutenance;

class SoutenanceDAO {

    public function findAll()
    {
        // We use 'with' to eager load the relationships, so the HTML table loads instantly!
        return Soutenance::with(['projet.etudiant', 'projet.encadrant'])->get();
    }

    public function findById($id): Soutenance
    {
        return Soutenance::findOrFail($id);
    }
}
?>