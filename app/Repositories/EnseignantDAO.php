<?php

namespace App\Repositories;

use App\Models\Enseignant;

class EnseignantDAO
{
    /**
     * Get all teachers in the database.
     */
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
}
