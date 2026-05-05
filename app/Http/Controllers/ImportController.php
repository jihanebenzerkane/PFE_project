<?php
namespace App\Http\Controllers;
use App\Services\ExcelImportService;
use Illuminate\Http\Request;

class ImportController extends Controller{
  protected ExcelImportService $importService;
  public function __construct (ExcelImportService $importService){
   $this->importService = $importService;
  }
  public function showForm(){
   return view('import');
  }
  public function import(Request $request){
   $request->validate([
       'file' => 'required|file|mimes:xlsx,xls|max:2048'
   ]);
   $file = $request->file('file');
   $rows = \Maatwebsite\Excel\Facades\Excel::toArray([], $file)[0];
   $firstCell = strtolower(trim($rows[0][0] ?? ''));
   if ($firstCell === 'encadrant' || $firstCell === 'nom') {
       $count = $this->importService->importEncadrants($file);
       $message = "$count enseignants importés avec succès.";
   } else {
       $filiere = $request->input('filiere', 'TDIA');
       $count = $this->importService->import($file, $filiere);
       $message = "$count étudiants importés avec succès ($filiere).";
   }
   return redirect()->back()->with('success', $message);
  }
}