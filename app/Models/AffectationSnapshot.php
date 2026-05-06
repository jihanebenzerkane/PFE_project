<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffectationSnapshot extends Model
{
    protected $fillable = ['label', 'data', 'etudiants_count'];

    protected $casts = [
        'data' => 'array',
    ];
}
