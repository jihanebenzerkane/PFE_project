<?php

namespace App\Http\Controllers;

use App\Models\Jury;
use App\Repositories\JuryDAO;
use Illuminate\Http\Request;

class JuryController extends Controller
{
    public function __construct(
        private JuryDAO $juryDAO
    ) {}

    public function index()
    {
    $jurys = $this->juryDAO->getAll();
    return view('jurys.index', compact('jurys'));
    }

    public function store(Request $request)
    {
        $jury = new Jury();
        $this->juryDAO->save($jury);

        return redirect()->route('jurys.index');
    }

    public function provePvId(int $id)
    {
        $jury = $this->juryDAO->findById($id);
        return view('jurys.pv', compact('jury'));
    }
}