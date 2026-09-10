<?php

/**
 * Install sample activity log (keep COVER-PAGE), clear week content, blank Daily_Reports.
 * Run: php scripts/reset-excel-start.php
 */

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

$root = dirname(__DIR__);
$sample = '/Users/mzati/Downloads/CSIT-Internship Activity Log Yankho Chisale.xlsx';
$activityPath = $root . '/CSIT-Internship Activity Log - 1.xlsx';
$dailyPath = $root . '/Daily_Reports.xlsx';

if (!file_exists($sample)) {
    fwrite(STDERR, "Sample not found: {$sample}\n");
    exit(1);
}

$prev = error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

copy($sample, $activityPath);
$spreadsheet = IOFactory::load($activityPath);

$cover = null;
foreach ($spreadsheet->getAllSheets() as $sheet) {
    if (trim($sheet->getTitle()) === 'COVER-PAGE') {
        $cover = $sheet;
        break;
    }
}

if ($cover) {
    // Normalize COVER-PAGE to B=label, C=value for clean app round-trips
    $rows = [
        3 => 'STUDENT NAME',
        4 => 'REG NUMBER',
        5 => 'COMPANY/ORGANIZATION',
        6 => 'SUPERVISOR NAME',
        7 => 'SUPERVISOR EMAIL',
        8 => 'INTERNSHIP START DATE',
    ];

    foreach ($rows as $row => $label) {
        $b = $cover->getCell("B{$row}")->getValue();
        $c = $cover->getCell("C{$row}")->getValue();
        $text = cellToString($b);

        $value = cellToString($c);
        if ($value === '' && $text !== '') {
            if (str_contains($text, ':')) {
                [, $after] = explode(':', $text, 2);
                $value = trim($after);
                // RichText dates may already be full string without clean split handled above
            } else {
                $value = $text;
            }
        }

        // Prefer extracting from RichText on B8 if still empty
        if ($row === 8 && $value === '' && $b instanceof RichText) {
            $value = trim($b->getPlainText());
            if (str_contains($value, ':')) {
                [, $after] = explode(':', $value, 2);
                $value = trim($after);
            }
        }

        $cover->setCellValue("B{$row}", $label . ':');
        $cover->setCellValue("C{$row}", $value);
    }

    $cover->setCellValue('B9', 'SUPERVISOR SIGNATURE:');
    if ($cover->getCell('C9')->getValue() === null) {
        $cover->setCellValue('C9', '');
    }
}

// Clear week sheets: keep structure/labels, wipe operational + narrative content
foreach ($spreadsheet->getAllSheets() as $sheet) {
    $title = trim($sheet->getTitle());
    if (!preg_match('/^WEEK-(\d+)$/', $title, $m)) {
        continue;
    }

    // Clear known value cells
    foreach (['D2', 'E2', 'D4', 'D5', 'A7', 'C7', 'C8', 'C9', 'C10', 'C11', 'C12', 'C13', 'C14', 'C15'] as $coord) {
        $sheet->setCellValue($coord, null);
    }

    // Clear any leftover narrative-looking cells in columns A–E rows 7–40
    for ($r = 7; $r <= 40; $r++) {
        for ($col = 1; $col <= 5; $col++) {
            $coord = Coordinate::stringFromColumnIndex($col) . $r;
            $val = $sheet->getCell($coord)->getValue();
            if ($val === null || $val === '') {
                continue;
            }
            $s = cellToString($val);
            // Keep static labels
            if (preg_match('/SUMMARY OF ACTIVITIES|COMPLETE SUMMARY|WEEKLY|SIGNATURE|INTERN|SUPERVISOR|DAYS PRESENT|DAYS ABSENT|FROM|TO/i', $s)) {
                continue;
            }
            $sheet->setCellValue($coord, null);
        }
    }
}

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save($activityPath);

// Blank Daily_Reports
$daily = new Spreadsheet();
$dSheet = $daily->getActiveSheet();
$dSheet->setTitle('Daily Reports');
$dSheet->setCellValue('A1', 'Week');
$dSheet->setCellValue('B1', 'Date');
$dSheet->setCellValue('C1', 'Activity');
$dailyWriter = IOFactory::createWriter($daily, 'Xlsx');
$dailyWriter->save($dailyPath);

error_reporting($prev);

echo "OK activity=" . filesize($activityPath) . " daily=" . filesize($dailyPath) . "\n";

function cellToString($val): string
{
    if ($val === null || $val === '') {
        return '';
    }
    if ($val instanceof RichText) {
        return trim($val->getPlainText());
    }
    if ($val instanceof DateTimeInterface) {
        return $val->format('Y-m-d');
    }
    return trim((string) $val);
}
