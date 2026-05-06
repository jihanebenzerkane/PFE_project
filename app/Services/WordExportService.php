<?php

namespace App\Services;

use App\Models\PlanningSnapshot;
use App\Models\AffectationSnapshot;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Table;

class WordExportService
{
    /**
     * Download a snapshot as a Word document.
     */
    public function downloadSnapshot($snapshot, string $type)
    {
        $phpWord = new PhpWord();
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'orientation'  => 'landscape',
            'pageSizeW'    => 23811,
            'pageSizeH'    => 16838,
            'marginTop'    => 800,
            'marginBottom' => 800,
            'marginLeft'   => 800,
            'marginRight'  => 800,
            
        ]);

        // ── Institutional Header Box (matches PDF) ─────────────────────────
        $year = now()->month >= 8 ? now()->year : now()->year - 1;
        $anneeUniversitaire = $year . '/' . ($year + 1);

        $center = ['alignment' => 'center'];

        // Create a 1x1 table for the bordered box effect
        $phpWord->addTableStyle('HeaderBox', [
            'borderSize' => 18, // 1.5 pt = 12 * 1.5 = 18 twips
            'borderColor' => '333333',
            'cellMargin' => 80,
            'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER
        ]);
        $headerTable = $section->addTable('HeaderBox');
        $headerCell = $headerTable->addRow()->addCell(8000); // 55% width approx

        $headerCell->addText(
            'Ecole Nationale des Sciences Appliquées - Al Hoceima',
            ['bold' => true, 'size' => 12],
            array_merge($center, ['spaceAfter' => 40])
        );

        $headerCell->addText(
            'Département Mathématiques et Informatique',
            ['size' => 11],
            array_merge($center, ['spaceAfter' => 40])
        );

        if ($type === 'planning') {
            $headerCell->addText(
                "Planning des soutenances des Projets de Fin d'Etude",
                ['size' => 10],
                array_merge($center, ['spaceAfter' => 40])
            );
            $headerCell->addText(
                '(Première Session)',
                ['size' => 10, 'italic' => true],
                array_merge($center, ['spaceAfter' => 40])
            );
        } else {
            $headerCell->addText(
                "Affectation des encadrants de Projet de Fin d'Etude",
                ['size' => 10],
                array_merge($center, ['spaceAfter' => 40])
            );
        }

        $headerCell->addText(
            'Année Universitaire ' . $anneeUniversitaire,
            ['size' => 10],
            array_merge($center, ['spaceAfter' => 0])
        );

        $section->addTextBreak(1); // Add some space below the header box

        // ── Color Legend ──
        if ($type === 'affectation') {
            $legendStyle = ['cellMargin' => 40, 'borderSize' => 0, 'borderColor' => 'FFFFFF'];
            $phpWord->addTableStyle('LegendTable', $legendStyle);
            $legendTable = $section->addTable('LegendTable');
            
            $addLegend = function($table, $color, $text) {
                $table->addRow(250);
                $table->addCell(500, ['bgColor' => $color])->addText('', ['size' => 8]);
                $table->addCell(8000)->addText($text, ['size' => 9]);
            };

            $addLegend($legendTable, 'C6EFCE', 'Filière TDIA — Transformation Digitale et Intelligence Artificielle');
            $addLegend($legendTable, 'F4B183', 'Filière ID — Ingénierie des Données');
            $addLegend($legendTable, 'BDD7EE', 'Filière GI — Génie Informatique');

            $section->addTextBreak(1);
        }


        $rows = collect($snapshot->data);

        if ($type === 'planning') {
            $this->addPlanningTable($section, $rows);
        } else {
            $this->addAffectationTable($section, $rows);
        }

        $filename = $type . '_' . $snapshot->id . '.docx';
        $tempPath = storage_path('app/' . $filename);

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Download the current live planning as Word.
     */
    public function downloadLivePlanning()
    {
        $snapshot = PlanningSnapshot::latest()->first();
        if (!$snapshot) {
            return back()->with('error', 'Aucun planning généré.');
        }
        return $this->downloadSnapshot($snapshot, 'planning');
    }

    /**
     * Download the current live affectation as Word.
     */
    public function downloadLiveAffectation()
    {
        $snapshot = AffectationSnapshot::latest()->first();
        if (!$snapshot) {
            return back()->with('error', 'Aucune affectation générée.');
        }
        return $this->downloadSnapshot($snapshot, 'affectation');
    }

    private function addPlanningTable($section, $rows): void
    {
        $phpWord = $section->getPhpWord();
        $phpWord->addTableStyle('PlanTable', [
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 50,
            'layout'      => 'fixed',
        ]);

        $table = $section->addTable('PlanTable');

        // Header
        $headerBg = '000000';
        $boldCenter = ['bold' => true, 'size' => 8, 'color' => 'FFFFFF'];
        $centerPara = ['alignment' => 'center'];

        $headers = ['ID', 'Encadrant', 'Membre de jury 1', 'Membre de jury 2', 'Date', 'Heure', 'Salle', 'Nom', 'Prénom', 'Filière'];
        $widths  = [700, 3300, 3300, 3300, 1800, 1600, 1400, 2500, 2500, 1600];

        $table->addRow();
        foreach ($headers as $index => $header) {
            $table->addCell($widths[$index], ['bgColor' => $headerBg])->addText($header, $boldCenter, $centerPara);
        }

        foreach ($rows as $i => $row) {
            $bgBase = $i % 2 === 0 ? 'FFFFFF' : 'DDEBF7';
            
            $f = strtoupper($row['filiere'] ?? '');
            $filiereColor = \App\Services\PdfExportService::applyFiliereColor($f);
            
            $encadrant = $row['encadrant'] ?? '';
            $encColor = \App\Services\PdfExportService::getProfessorColor($encadrant);
            
            $jury1 = $row['examinateurs'][0] ?? '';
            $j1Color = \App\Services\PdfExportService::getProfessorColor($jury1);
            
            $jury2 = $row['examinateurs'][1] ?? '';
            $j2Color = \App\Services\PdfExportService::getProfessorColor($jury2);

            $table->addRow();

            // ID
            $table->addCell($widths[0], ['bgColor' => ltrim($encColor, '#')])->addText($i + 1, ['bold' => true, 'size' => 8], $centerPara);
            
            // Encadrant
            $table->addCell($widths[1], ['bgColor' => ltrim($encColor, '#')])->addText(str_replace('Dr. ', '', $encadrant), ['bold' => true, 'size' => 8]);
            
            // Membre 1
            $table->addCell($widths[2], ['bgColor' => ltrim($j1Color, '#')])->addText(str_replace('Dr. ', '', $jury1), ['bold' => true, 'size' => 8]);
            
            // Membre 2
            $table->addCell($widths[3], ['bgColor' => ltrim($j2Color, '#')])->addText(str_replace('Dr. ', '', $jury2), ['bold' => true, 'size' => 8]);
            
            // Date
            $table->addCell($widths[4], ['bgColor' => 'FFFF00'])->addText($row['date'] ?? '', ['bold' => true, 'size' => 8], $centerPara);
            
            // Heure
            $table->addCell($widths[5], ['bgColor' => $bgBase])->addText($row['heure_debut'] ?? '', ['size' => 8], $centerPara);
            
            // Salle
            $table->addCell($widths[6], ['bgColor' => $bgBase])->addText($row['salle'] ?? '', ['bold' => true, 'size' => 8], $centerPara);
            
            // Nom etudiant
            $table->addCell($widths[7], ['bgColor' => ltrim($filiereColor, '#')])->addText(strtoupper($row['etudiant_nom'] ?? ''), ['size' => 8]);
            
            // Prenom etudiant
            $table->addCell($widths[8], ['bgColor' => ltrim($filiereColor, '#')])->addText($row['etudiant_prenom'] ?? '', ['size' => 8]);
            
            // Filiere
            $fText = '';
            if (str_contains($f, 'TDIA')) $fText = 'TDIA';
            elseif (str_contains($f, 'GI')) $fText = 'GI';
            elseif (str_contains($f, 'ID')) $fText = 'ID';
            else $fText = $row['filiere'] ?? '';

            $table->addCell($widths[9], ['bgColor' => ltrim($filiereColor, '#')])->addText($fText, ['bold' => true, 'size' => 8], $centerPara);
        }
    }

    private function addAffectationTable($section, $rows): void
    {
        $bgToWord = [
            '#C6EFCE' => 'C6EFCE', // TDIA green
            '#BDD7EE' => 'BDD7EE', // GI blue
            '#F4B183' => 'F4B183', // ID orange
            '#ffffff'  => 'FFFFFF',
        ];

        $phpWord = $section->getPhpWord();
        $phpWord->addTableStyle('AffTable', [
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 80,
            'layout'      => 'fixed',
        ]);
        $table = $section->addTable('AffTable');

        $boldCenter = ['bold' => true, 'size' => 9];
        $centerPara = ['alignment' => 'center'];
        $headerBg   = '2F5496';
        $subBg      = 'D9E1F2';

        $colWidths = [2000, 2000, 2250, 2250, 2250, 2250, 2250, 2250, 2250, 2250];

        // Main header
        $table->addRow();
        $table->addCell(4000, ['bgColor' => $headerBg, 'gridSpan' => 2])->addText('Encadrant', ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'], $centerPara);
        $table->addCell(18000, ['bgColor' => $headerBg, 'gridSpan' => 8])->addText('Etudiants encadrés', ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'], $centerPara);

        // Sub-header
        $headers = ['Nom', 'Prénom', 'Etudiant 1 Nom', 'Prénom', 'Etudiant 2 Nom', 'Prénom', 'Etudiant 3 Nom', 'Prénom', 'Etudiant 4 Nom', 'Prénom'];
        $table->addRow();
        foreach ($headers as $idx => $h) {
            $table->addCell($colWidths[$idx], ['bgColor' => $subBg])->addText($h, ['bold' => true, 'size' => 8, 'color' => '1F2D6B'], $centerPara);
        }

        // Sort alphabetically by enc_nom, then group by encadrant
        $grouped = collect($rows)->sortBy('enc_nom')->groupBy('encadrant');

        foreach ($grouped as $encadrant => $students) {
            // Majority-vote bg color from all students in this group
            $bgCounts = $students->countBy('bg');
            $bgHex    = $bgCounts->sortDesc()->keys()->first() ?? '#ffffff';
            $bgWord   = ltrim($bgHex, '#');  // strip leading #
            $bgWord   = strtoupper($bgWord);

            $firstRow  = $students->first();
            $encNom    = $firstRow['enc_nom'] ?? '';
            $encPrenom = $firstRow['enc_prenom'] ?? '';
            if (empty($encNom) && $encadrant !== 'Non assigné') {
                $parts = explode(' ', $encadrant, 2);
                $encNom    = $parts[0] ?? '';
                $encPrenom = $parts[1] ?? '';
            }

            $chunks = $students->chunk(4);
            foreach ($chunks as $chunk) {
                $names = $chunk->values();
                $table->addRow();
                $table->addCell(2000, ['bgColor' => 'FFFFFF'])->addText(strtoupper($encNom), ['bold' => true, 'size' => 8]);
                $table->addCell(2000, ['bgColor' => 'FFFFFF'])->addText($encPrenom, ['size' => 8]);
                for ($k = 0; $k < 4; $k++) {
                    $student = $names[$k] ?? null;
                    $eNom    = strtoupper($student['etu_nom'] ?? '');
                    $ePrenom = $student['etu_prenom'] ?? '';
                    $eBgHex  = $student['bg'] ?? '#ffffff';
                    $eBgWord = strtoupper(ltrim($eBgHex, '#'));
                    $table->addCell(2250, ['bgColor' => $eBgWord])->addText($eNom, ['size' => 8]);
                    $table->addCell(2250, ['bgColor' => $eBgWord])->addText($ePrenom, ['size' => 8]);
                }
            }
        }
    }
}
