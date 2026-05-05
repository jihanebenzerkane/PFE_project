<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Enseignant;
use App\Models\Soutenance;

class Jury extends Model
{
    use HasFactory;

    protected $fillable = [];

    public function enseignants()
    {
        return $this->belongsToMany(Enseignant::class, 'jury_enseignant')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function soutenance()
    {
        return $this->hasOne(Soutenance::class);
    }
}