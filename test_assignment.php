<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app()->make(App\Services\AssignmentService::class);
$creneaux = app()->make(App\Repositories\CreneauRepository::class)->findAll();
$busyIds = App\Models\Soutenance::whereHas('projet', fn($q) => $q->where('encadrant_id', 1))->pluck('creneau_id')->toArray();
$busyCreneaux = $creneaux->whereIn('id', $busyIds);

echo 'Busy IDs: ' . json_encode($busyIds) . PHP_EOL;
echo 'Busy Creneaux: ' . $busyCreneaux->count() . PHP_EOL;

// Test strtotime
foreach($creneaux->take(3) as $c) {
    echo $c->heure_debut . ' => ' . strtotime($c->heure_debut) . PHP_EOL;
}
