<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanningSnapshot extends Model
{
    protected $fillable = ['label', 'data', 'soutenances_count'];

    protected $casts = [
        'data' => 'array',
    ];
}
