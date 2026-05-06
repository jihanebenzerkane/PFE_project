<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app()->make(App\Services\AssignmentService::class);
$creneaux = app()->make(App\Repositories\CreneauRepository::class)->findAll();

// 1. Create a Soutenance for Encadrant 1 at the FIRST creneau
$firstCreneau = $creneaux->first();
$projet = App\Models\Projet::create(['titre' => 'Test', 'encadrant_id' => 1, 'etudiant_id' => 1]);
App\Models\Soutenance::create([
    'projet_id' => $projet->id,
    'creneau_id' => $firstCreneau->id,
    'salle' => 'Salle Test'
]);

echo "Created Soutenance for Encadrant 1 at {$firstCreneau->heure_debut}\n";

// 2. Now call pickCreneauAndSalle using reflection
$reflection = new ReflectionClass(get_class($service));
$method = $reflection->getMethod('pickCreneauAndSalle');
$method->setAccessible(true);

$perDayFiliereCount = [];
$busyCreneauxIds = App\Models\Soutenance::whereHas('projet', fn($q) => $q->where('encadrant_id', 1))->pluck('creneau_id')->toArray();
echo "Busy IDs from DB: " . json_encode($busyCreneauxIds) . "\n";
$busyCreneaux = $creneaux->whereIn('id', $busyCreneauxIds);
echo "Busy Creneaux Count from whereIn: " . $busyCreneaux->count() . "\n";

$result = $method->invokeArgs($service, [
    1, // encadrantId
    $creneaux,
    'GI', // filiere
    $perDayFiliereCount,
    999
]);

if ($result) {
    [$creneau, $salle] = $result;
    echo "Picked Creneau: {$creneau->heure_debut}\n";
    echo "Diff to first: " . abs(strtotime($creneau->heure_debut) - strtotime($firstCreneau->heure_debut)) . "\n";
} else {
    echo "No Creneau picked!\n";
}

// Cleanup
App\Models\Soutenance::truncate();
App\Models\Projet::truncate();
