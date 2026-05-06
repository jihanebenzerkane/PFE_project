<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Manually fetch and diff
$creneaux = App\Models\Creneau::all();
foreach($creneaux as $c1) {
    foreach($creneaux as $c2) {
        if ($c1->id !== $c2->id && $c1->date->eq($c2->date)) {
            $diff = abs(strtotime($c1->heure_debut) - strtotime($c2->heure_debut));
            echo "{$c1->heure_debut->format('H:i')} vs {$c2->heure_debut->format('H:i')} = $diff\n";
        }
    }
}
