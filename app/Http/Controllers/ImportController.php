<?php
namespace App\Http\Controllers;

use App\Models\Etudiant;
use App\Models\Enseignant;
use App\Models\Projet;
use App\Models\Salle;
use App\Models\AffectationSnapshot;
use App\Services\ExcelImportService;
use Illuminate\Http\Request;
use App\Models\Soutenance;
use App\Models\Jury;

class ImportController extends Controller{
    protected  ExcelImportService $importService; //un objet de type Excelimportservice  
    //injection de dependances bach ne pas creer l’objet manuellement
    public function __construct(ExcelImportService $importService) //un constructeur mais avec injection 
    {
        $this->importService = $importService;
    }

    //class 1 - import
    public function showForm(){
        return view ('import.index');

    }

    //class 2 -import
    public function importMaster(Request $request){
        $request->validate([
            'files' => 'required',
            'files.*' => 'file|mimes:xlsx, xls|max:5000',
        ]);
        $results = ['students'=> 0,'enseignants'=> 0,'salles'=> 0];
        foreach($request->file('files') as $file){
            $filename = strtolower($file->getClientOriginalName()); //tu obtiens un objet UploadedFile. getClientOriginalName() retourne le nom original du fichier tel qu’il a été envoyé par le client (l’utilisateur).
            $filiere = $this->detectFiliere($filename, $file);
            $r = $this->importService->importMaster($file, $filiere);   
            $results['students'] += $r['students'];
            $results['enseignants'] += $r['enseignants'];
            $results['salles'] += $r['salles'];
        }
        return redirect()->route('affectation.index')->with('success', "Importation terminée : {$results['students']} etudiants, {$results['enseignants']} ensaignants, {$results['salles']} salles.");
    }
    //class 3 - importEtudiants
    public function importEtudiants(Request $request){
        $request->validate([
            'file_projets'  => 'required',
            'file_projets.*' => 'file|mimes:xlsx,xls|max:5000',
            'file_emails'   => 'required',
            'file_emails.*'  => 'file|mimes:xlsx,xls|max:5000',

        ]);
        
    Etudiant::query()->delete();
    Projet::query()->delete();
    AffectationSnapshot::query()->delete();

    $emailLookup = $this->importService->buildGlobalEmailLookup($request->file('file_emails'));
    $totalCount = 0;
    $details = [];

    foreach($request->file('file_projets') as $projectFile){
        $filename = strtolower($projectFile->getClientOriginalName());
        $filiere  = $this->detectFiliere($filename, $projectFile);

        $result      = $this->importService->import($projectFile, $emailLookup, $filiere);
        $totalCount += $result['students'];
        $details[]   = "{$result['students']} étudiants ($filiere)";
    }
    return redirect()->route('import.form')->with('success', "Importés : $totalCount étudiants :" . implode(', ', $details));
}

    //class 4 - importEnseignants
    public function importEnseignants(Request $request){
        $request->validate([
        'file_enseignants' => 'required|file|mimes:xlsx,xls|max:5000'
    ]);
    Enseignant::query()->delete();

    $result = $this->importService->importEnseignants($request->file('file_enseignants'));

    return redirect()->route('import.form')->with('success', "{$result['enseignant']} enseignants importés avec succées.");

    }
    //class5 -importSalles
    public function importSalles(Request $request){
        $request->validate([
            'file_salles' => 'required|file|mimes:xlsx,xls|max:5000'
        ]);
        Salle::query()->delete();
        $result = $this->importService->importSalles($request->file('file_salles'));
        return redirect()->route('import.form')->with('success', "{$result['salles']} salles importés avec succées.");
    }
    //class6 - imoportALL
    public function importAll(Request $request){
     $results = ['students' => 0, 'projects' => 0, 'enseignants' => 0, 'salles' => 0];
    $summary = [];
    if ($request->hasFile('file_projets')) {
        // On ne supprime que si on importe de nouveaux fichiers
        Etudiant::query()->delete();
        Projet::query()->delete();
        AffectationSnapshot::query()->delete();
          $emailLookup = [];
        if ($request->hasFile('file_emails')) {
            $emailLookup = $this->importService->buildGlobalEmailLookup($request->file('file_emails'));
        }

        foreach ($request->file('file_projets') as $projectFile) {
            $filename = strtolower($projectFile->getClientOriginalName());
            $filiere  = $this->detectFiliere($filename, $projectFile);
            $r = $this->importService->import($projectFile, $emailLookup, $filiere);
            $results['students'] += $r['students'];
            $results['projects'] += $r['projects'];
        }
        $summary[] = "{$results['students']} étudiants";
    }


    if ($request->hasFile('file_enseignants')) {
        Enseignant::query()->delete(); // Optionnel : à voir si on veut vider ou juste ajouter
        $r = $this->importService->importEnseignants($request->file('file_enseignants'));
        $results['enseignants'] = $r['enseignant'];
        $summary[] = "{$results['enseignants']} enseignants";
    }

  
    if ($request->hasFile('file_salles')) {
        
        $r = $this->importService->importSalles($request->file('file_salles'));
        $results['salles'] = $r['salles'];
        $summary[] = "{$results['salles']} salles";
    }

    if (empty($summary)) {
        return redirect()->route('import.form')->with('error', "Aucun fichier n'a été sélectionné.");
    }

    return redirect()->route('import.form')->with('success',
        "Importation terminée : " . implode(', ', $summary) . "."
    );

    }
    //class7 -CLEAR DATA
    public function clearData(){
        
        Etudiant::query()->delete();
        Enseignant::query()->delete();
        Salle::query()->delete();
        AffectationSnapshot::query()->delete();
        Soutenance::query()->delete();
        Jury::query()->delete();

        return redirect()->route('import.form')->with('success', "Toutes les données ont été supprimées avec succès.");

    }
    //helpers
    //filere detecteur
    private function detectFiliere(string $filename, $file): string{
        $map = [
            'ingenierie' => 'Ingénierie des Données',
            'ingénierie' => 'Ingénierie des Données',
            'donnees' => 'Ingénierie des Données',
            'données' => 'Ingénierie des Données',

            'tdia' => 'Transformation Digitale & Intelligence Artificielle',
            'transformation' => 'Transformation Digitale & Intelligence Artificielle',
            'digitale' => 'Transformation Digitale & Intelligence Artificielle',
            'artificielle' => 'Transformation Digitale & Intelligence Artificielle',

            'genie' => 'Génie Informatique',
            'génie' => 'Génie Informatique',            
        ];
        foreach ($map as $keywords => $filiere){
            if(str_contains($filename, $keywords))
                return $filiere;
        }

        if (preg_match('/\bid\b/i', $filename))
            return 'Ingénierie des Données';
        if (preg_match('/\bgi\b/i', $filename))
            return 'Génie Informatique';

        try{
            $rows = \Maatwebsite\Excel\Facades\Excel::toArray([], $file)[0];
            $haystack = strtolower(implode(' ', array_map('strval', array_merge(...array_slice($rows, 0, 5)))));

            if (str_contains($haystack, 'ingénierie') || str_contains($haystack, 'ingenierie') || str_contains($haystack, 'données'))
                return 'Ingénierie des Données';

            if (str_contains($haystack, 'tdia') || str_contains($haystack, 'transformation'))
                return 'Transformation Digitale & Intelligence Artificielle';
            if (str_contains($haystack, 'génie') || str_contains($haystack, 'genie'))
                return 'Génie Informatique';
        
        }catch (\Throwable $e){

        }
        return 'Inconnue';
    }













}