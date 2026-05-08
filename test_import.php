<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$files = [
    'ID Students'   => 'excel files pdf/Ingénierie des données 3_Email.xlsx',
    'TDIA Students' => 'excel files pdf/Transformation Digitale & Intelligence Artificielle 3_Email.xlsx',
    'Profs'         => 'excel files pdf/Liste des Profs.xlsx',
];

foreach ($files as $label => $path) {
    echo "=== $label ===\n";
    $sheets = Maatwebsite\Excel\Facades\Excel::toArray(new stdClass, $path);
    echo "Number of sheets: " . count($sheets) . "\n";
    foreach ($sheets as $i => $sheet) {
        echo "  Sheet $i — " . count($sheet) . " rows\n";
        // Print first 3 rows
        foreach (array_slice($sheet, 0, 3) as $row) {
            echo "    " . json_encode(array_filter($row, fn($v) => $v !== null), JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
    echo "\n";
}
