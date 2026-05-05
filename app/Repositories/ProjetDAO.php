<?php
namespace App\Repositories;
use App\Models\Projet;
//construire la relation between db & models
class ProjetDAO{
 public function create(array $data): Projet{
  return Projet::create($data);
 }
 public function findByTitre(string $titre): ?Projet {
  return Projet::where('titre', $titre)->first();
 }
 public function all(): \Illuminate\Database\Eloquent\Collection{
  return Projet:: with('etudiant')->get(); //eager loading laravel va fetcher tout pas projet par projet 
 }
}