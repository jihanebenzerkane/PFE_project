<?php

namespace App\Http\Controllers;

use App\Models\Salle;
use App\Repositories\SalleDAO;
use Illuminate\Http\Request;

class SalleController extends Controller
{
    public function __construct(
        private SalleDAO $salleDAO
    ) {}

    public function index()
    {
        $salles = $this->salleDAO->findAll();
        return view('salles.index', compact('salles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom'      => 'required|string|max:50',
            'capacite' => 'required|integer|min:1',
        ]);

        $salle = new Salle();
        $salle->nom      = $request->nom;
        $salle->capacite = $request->capacite;
        $this->salleDAO->save($salle);

        return redirect()->route('salles.index');
    }

    public function destroy(int $id)
    {
        $this->salleDAO->delete($id);
        return redirect()->route('salles.index');
    }
}