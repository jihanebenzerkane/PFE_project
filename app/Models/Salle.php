<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;



class Salle extends Model
{
    protected $fillable = [
        'nom',
        'capacite',
    ];

    public function soutenances()
    {
        return $this->hasMany(Soutenance::class);
    }
}

