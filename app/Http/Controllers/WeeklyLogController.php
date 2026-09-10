<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WeeklyLogController extends Controller
{
    private function activityLogPath(): string
    {
        return base_path('CSIT-Internship Activity Log - 1.xlsx');
    }

    private function dailyReportsPath(): string
    {
        return base_path('Daily_Reports.xlsx');
    }

    /**
     * Load a workbook while silencing PhpSpreadsheet 1.x deprecations on PHP 8.4+.
     */
    private function loadSpreadsheet(string $path): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $previous = error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        try {
            return \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        } finally {
            error_reporting($previous);
        }
    }

    /**
     * Resolve a sheet by name, tolerating trailing spaces in workbook tab names.
     */
    private function sheetByName(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, string $name): ?\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $sheet = $spreadsheet->getSheetByName($name);
        if ($sheet) {
            return $sheet;
        }

        $wanted = trim($name);
        foreach ($spreadsheet->getAllSheets() as $candidate) {
            if (trim($candidate->getTitle()) === $wanted) {
                return $candidate;
            }
        }

        return null;
    }

    private function cellPlain($cell): string
    {
        if ($cell === null) {
            return '';
        }

        $val = $cell->getValue();
        if ($val === null || $val === '') {
            return '';
        }
        if ($val instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            return trim($val->getPlainText());
        }
        if ($val instanceof \DateTimeInterface) {
            return $val->format('Y-m-d');
        }

        return trim((string) $val);
    }

    /**
     * Normalize Excel cell values that may be serial dates or DateTime objects.
     */
    private function formatExcelDate($cell, $fallback = 'Not Set'): string
    {
        if ($cell === null) {
            return $fallback;
        }

        $val = $cell->getValue();
        if ($val === null || $val === '') {
            return $fallback;
        }

        if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val)->format('Y-m-d');
        }

        if ($val instanceof \DateTimeInterface) {
            return $val->format('Y-m-d');
        }

        if ($val instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            $val = $val->getPlainText();
        }

        if (is_numeric($val) && (float) $val > 20000 && (float) $val < 80000) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $val)->format('Y-m-d');
            } catch (\Exception $e) {
                // fall through
            }
        }

        $parsed = $this->parseFlexibleDate((string) $val);

        return $parsed ? $parsed->format('Y-m-d') : (trim((string) $val) ?: $fallback);
    }

    private function parseFlexibleDate(string $raw): ?\DateTime
    {
        $raw = trim($raw);
        if ($raw === '' || strcasecmp($raw, 'Not Set') === 0) {
            return null;
        }

        try {
            return new \DateTime($raw);
        } catch (\Exception $e) {
            // continue
        }

        $normalized = preg_replace('/(\d+)(ST|ND|RD|TH)/i', '$1', $raw);
        $normalized = preg_replace('/\s+/', ' ', (string) $normalized);
        $normalized = str_ireplace('FEBRURARY', 'FEBRUARY', $normalized);
        $ts = strtotime($normalized);
        if ($ts === false) {
            return null;
        }

        return (new \DateTime())->setTimestamp($ts);
    }

    private function coverField(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $cCell, string $bCell): string
    {
        $fromC = $this->cellPlain($sheet->getCell($cCell));
        if ($fromC !== '') {
            return $fromC;
        }

        $fromB = $this->cellPlain($sheet->getCell($bCell));
        if ($fromB === '') {
            return '';
        }
        if (str_contains($fromB, ':')) {
            [, $after] = explode(':', $fromB, 2);

            return trim($after);
        }

        return $fromB;
    }

    private function readCoverProfile(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $cover): array
    {
        $startRaw = $this->coverField($cover, 'C8', 'B8');
        $startDate = $this->formatExcelDate($cover->getCell('C8'), '');
        if ($startDate === '' || $startDate === 'Not Set') {
            $parsed = $this->parseFlexibleDate($startRaw);
            $startDate = $parsed ? $parsed->format('Y-m-d') : ($startRaw !== '' ? $startRaw : 'Not set');
        }

        return [
            'name' => $this->coverField($cover, 'C3', 'B3') ?: 'Not set',
            'reg_number' => $this->coverField($cover, 'C4', 'B4') ?: 'Not set',
            'company' => $this->coverField($cover, 'C5', 'B5') ?: 'Not set',
            'supervisor' => $this->coverField($cover, 'C6', 'B6') ?: 'Not set',
            'supervisor_email' => $this->coverField($cover, 'C7', 'B7') ?: 'Not set',
            'start_date' => $startDate ?: 'Not set',
            'supervisor_signature' => $this->hasSupervisorSignatureImage($cover) ? 'Uploaded' : 'Not set',
        ];
    }

    private function writeCoverProfile(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $data
    ): void {
        $labels = [
            3 => 'STUDENT NAME',
            4 => 'REG NUMBER',
            5 => 'COMPANY/ORGANIZATION',
            6 => 'SUPERVISOR NAME',
            7 => 'SUPERVISOR EMAIL',
            8 => 'INTERNSHIP START DATE',
        ];
        $values = [
            3 => $data['name'] ?? '',
            4 => $data['reg_number'] ?? '',
            5 => $data['company'] ?? '',
            6 => $data['supervisor'] ?? '',
            7 => $data['supervisor_email'] ?? '',
            8 => $data['start_date'] ?? '',
        ];

        foreach ($labels as $row => $label) {
            $sheet->setCellValue("B{$row}", $label . ':');
            $sheet->setCellValue("C{$row}", $values[$row]);
        }

        $sheet->setCellValue('B9', 'SUPERVISOR SIGNATURE:');
        $sheet->setCellValue('C9', '');
    }

    /** Week summary lives under the SUMMARY label (C7), with A7 as legacy fallback. */
    private function weekSummary(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): string
    {
        $c7 = trim($this->cellPlain($sheet->getCell('C7')));
        if ($c7 !== '') {
            return $c7;
        }

        return trim($this->cellPlain($sheet->getCell('A7')));
    }

    private function setWeekSummary(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $summary): void
    {
        $sheet->setCellValue('C7', $summary);
        $sheet->setCellValue('A7', $summary);
    }

    public function index()
    {
        $filePath = $this->activityLogPath();
        $dailyFile = $this->dailyReportsPath();

        $dailyCounts = [];

        // 1. Pre-load Daily Log Counts (Unique dates per week)
        if (file_exists($dailyFile)) {
            try {
                $dailySpreadsheet = $this->loadSpreadsheet($dailyFile);
                $dailySheet = $dailySpreadsheet->getActiveSheet();
                foreach ($dailySheet->getRowIterator(2) as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    $cells = [];
                    foreach ($cellIterator as $cell) {
                        $cells[] = $cell->getValue();
                    }
                    if (isset($cells[0]) && isset($cells[1])) {
                        $w = $cells[0];
                        $d = $cells[1];
                        if (!isset($dailyCounts[$w])) {
                            $dailyCounts[$w] = [];
                        }
                        $dailyCounts[$w][$d] = true;
                    }
                }
            } catch (\Exception $e) {
            }
        }

        $weeks = [];
        $previousWeekCompleted = true; // Week 1 is always unlocked

        $studentDetails = [
            'name' => 'Not set',
            'reg_number' => 'Not set',
            'company' => 'Not set',
            'supervisor' => 'Not set',
            'supervisor_email' => 'Not set',
            'supervisor_signature' => 'Not set',
            'start_date' => 'Not set',
        ];

        if (file_exists($filePath)) {
            try {
                $spreadsheet = $this->loadSpreadsheet($filePath);

                $coverSheet = $this->sheetByName($spreadsheet, 'COVER-PAGE');
                if ($coverSheet) {
                    $studentDetails = $this->readCoverProfile($coverSheet);
                }

                for ($i = 1; $i <= 16; $i++) {
                    $sheetName = "WEEK-{$i}";
                    $sheet = $this->sheetByName($spreadsheet, $sheetName);

                    $status = 'Pending';
                    $summary = '';
                    $isLocked = !$previousWeekCompleted;

                    if ($sheet) {
                        $summary = $this->weekSummary($sheet);
                        $daysLogged = isset($dailyCounts[$i]) ? count($dailyCounts[$i]) : 0;

                        if (!empty($summary) && $daysLogged >= 5) {
                            $status = 'Completed';
                        } elseif (!empty($summary) || $daysLogged > 0) {
                            $status = 'In Progress';
                        }
                    }

                    $weeks[] = [
                        'number' => $i,
                        'status' => $status,
                        'is_locked' => $isLocked,
                        'preview' => \Illuminate\Support\Str::limit($summary, 50),
                    ];

                    $previousWeekCompleted = ($status === 'Completed');
                }
            } catch (\Exception $e) {
                // Handle error gracefully
            }
        } else {
            for ($i = 1; $i <= 16; $i++) {
                $weeks[] = ['number' => $i, 'status' => 'Pending', 'is_locked' => ($i > 1), 'preview' => ''];
            }
        }

        return view('dashboard', compact('weeks', 'studentDetails'));
    }

    public function create()
    {
        $week = request('week');
        
        // If no week specified, calculate current week
        if (!$week) {
            $filePath = $this->activityLogPath();
            if (file_exists($filePath)) {
                try {
                    $spreadsheet = $this->loadSpreadsheet($filePath);
                    $coverSheet = $this->sheetByName($spreadsheet, 'COVER-PAGE');
                    if ($coverSheet) {
                        $profile = $this->readCoverProfile($coverSheet);
                        $startDate = $profile['start_date'] ?? '';
                        $parsed = $this->parseFlexibleDate((string) $startDate);
                        if ($parsed) {
                            $today = new \DateTime('today');
                            $diff = $parsed->diff($today);
                            $daysDiff = $diff->days + 1;
                            $week = max(1, min(16, (int) ceil($daysDiff / 7)));
                        }
                    }
                } catch (\Exception $e) {
                }
            }
            $week = $week ?? 1; // Default to week 1
        }
        
        // Prevent access to locked weeks
        if ($week > 1) {
            $prevWeek = $week - 1;
            $data = $this->getWeekDataInternal($prevWeek);
            if (!isset($data['status']) || $data['status'] !== 'Completed') {
                return redirect()->route('dashboard')->withErrors(["Week $week is locked until Week $prevWeek is completed."]);
            }
        }

        return view('weekly-log.create', compact('week'));
    }

    private function hasSupervisorSignatureImage(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): bool
    {
        foreach ($sheet->getDrawingCollection() as $drawing) {
            if ($drawing->getCoordinates() === 'C9') {
                return true;
            }
        }

        return false;
    }

    private function removeSupervisorSignatureImages(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $collection = $sheet->getDrawingCollection();
        $keys = [];

        foreach ($collection as $key => $drawing) {
            if ($drawing->getCoordinates() === 'C9') {
                $keys[] = $key;
            }
        }

        foreach ($keys as $key) {
            $collection->offsetUnset($key);
        }
    }

    private function embedSupervisorSignatureImage(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        \Illuminate\Http\UploadedFile $signature
    ): void {
        $this->removeSupervisorSignatureImages($sheet);

        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setName('Supervisor Signature');
        $drawing->setDescription('Supervisor Signature');
        $drawing->setPath($signature->getPathname());
        $drawing->setCoordinates('C9');
        $drawing->setOffsetX(5);
        $drawing->setOffsetY(5);
        $drawing->setHeight(42);
        $drawing->setWorksheet($sheet);

        $sheet->getRowDimension(9)->setRowHeight(42);
        $sheet->getColumnDimension('C')->setWidth(32);
    }

    /**
     * Internal helper to get structured data for a week
     */
    private function getWeekDataInternal($week)
    {
        $filePath = $this->activityLogPath();
        if (!file_exists($filePath)) {
            return [];
        }

        try {
            $spreadsheet = $this->loadSpreadsheet($filePath);
            $sheet = $this->sheetByName($spreadsheet, "WEEK-{$week}");
            if (!$sheet) {
                return [];
            }

            $summary = $this->weekSummary($sheet);

            // Daily check
            $dailyFile = $this->dailyReportsPath();
            $daysLogged = 0;
            if (file_exists($dailyFile)) {
                $dailySpreadsheet = $this->loadSpreadsheet($dailyFile);
                $dailySheet = $dailySpreadsheet->getActiveSheet();
                $uniqueDates = [];
                foreach ($dailySheet->getRowIterator(2) as $row) {
                    $cells = [];
                    foreach ($row->getCellIterator() as $cell) { $cells[] = $cell->getValue(); }
                    if (isset($cells[0]) && $cells[0] == $week && isset($cells[1])) {
                        $uniqueDates[$cells[1]] = true;
                    }
                }
                $daysLogged = count($uniqueDates);
            }

            $status = 'Pending';
            if (!empty($summary) && $daysLogged >= 5) {
                $status = 'Completed';
            } elseif (!empty($summary) || $daysLogged > 0) {
                $status = 'In Progress';
            }

            return ['status' => $status];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'week' => 'required|integer|min:1|max:16',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'days_present' => 'required|numeric|min:0',
            'days_absent' => 'required|numeric|min:0',
            'summary' => 'required|string',
        ]);

        $weekNumber = $validated['week'];

        // Backend Lock Check
        if ($weekNumber > 1) {
            $prevData = $this->getWeekDataInternal($weekNumber - 1);
            if (!isset($prevData['status']) || $prevData['status'] !== 'Completed') {
                return back()->withErrors(['error' => "Week $weekNumber is locked."]);
            }
        }

        $sheetName = "WEEK-{$weekNumber}";
        $filePath = $this->activityLogPath();

        if (!file_exists($filePath)) {
            return back()->withErrors(['file' => 'Excel file not found.']);
        }

        try {
            // Logic to write to Excel will go here.
            // Using a simple spreadhseet manipulation approach or Maatwebsite import/export
            // Since we need to write to specific cells in an existing file, loading it is best.
            
            $spreadsheet = $this->loadSpreadsheet($filePath);
            $sheet = $this->sheetByName($spreadsheet, $sheetName);

            if (!$sheet) {
                return back()->withErrors(['week' => "Sheet $sheetName not found in Excel file."]);
            }

            // Map fields to cells (based on Analysis)
            // FROM (DATE): D1 -> Right E1
            // TO (DATE): E1
            // NUMBER OF DAYS PRESENT: C4 -> Value at D4 (Neighbor right is D4? No, text value is at C4, input usually next to it)
            // Analysis said: 'NUMBER OF DAYS PRESENT:' at C4. Right neighbor (D4) was None. So D4 is likely the input.
            // 'NUMBER OF DAYS ABSENT:' at C5. Right neighbor (D5) likely input.
            // 'SUMMARY OF ACTIVITIES FOR THE WEEK:' at C6. Cell below is C7. Merged?
            // Usually summary is a large block below. Let's assume A7 or C7. 
            // Analysis said 'Row 11: COMPLETE SUMMARY...'. 
            // Let's assume C7 for now or search for it.
            // Actually, let's trust the Implementation Plan or Re-verify if needed.
            // Plan said: D2 (Start), E2 (End), D4 (Present), D5 (Absent), A7 (Summary)
            // Analysis found 'FROM (DATE)' at D1. So value likely at D2??
            // Or right next to it? "Found 'FROM (DATE)' at D1 -> Right (E1): TO (DATE)".
            // So D1 is the label "FROM (DATE)". Value might be D2 (below) or E1 (right? No E1 is TO DATE).
            // Usually standard forms have Label: Value.
            // If D1 is "From", E1 is "To". Values might be D2 and E2.
            
            $sheet->setCellValue('D2', $validated['start_date']);
            $sheet->setCellValue('E2', $validated['end_date']);
            $sheet->setCellValue('D4', $validated['days_present']);
            $sheet->setCellValue('D5', $validated['days_absent']);
            $this->setWeekSummary($sheet, $validated['summary']);

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($filePath);

            return back()->with('success', "Week {$weekNumber} summary saved.");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to save to Excel: ' . $e->getMessage()]);
        }
    }

    public function getWeekData($week)
    {
        $sheetName = "WEEK-{$week}";
        $filePath = $this->activityLogPath();

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'Excel file not found'], 404);
        }

        try {
            $spreadsheet = $this->loadSpreadsheet($filePath);
            $sheet = $this->sheetByName($spreadsheet, $sheetName);

            if (!$sheet) {
                return response()->json(['error' => "Sheet $sheetName not found"], 404);
            }

            // Helper to clean up date values if they come back weird
            $formatDate = function($cell) {
                $val = $cell->getValue();
                if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                    return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val)->format('Y-m-d');
                }
                return $val; // Return as is (likely string if we saved it as string)
            };

            // Fetch Daily Logs
            $dailyLogs = [];
            $dailyFile = $this->dailyReportsPath();
            if (file_exists($dailyFile)) {
                try {
                    $dailySpreadsheet = $this->loadSpreadsheet($dailyFile);
                    $dailySheet = $dailySpreadsheet->getActiveSheet();
                    foreach ($dailySheet->getRowIterator(2) as $row) {
                        $cellIterator = $row->getCellIterator();
                        $cellIterator->setIterateOnlyExistingCells(false);
                        $cells = [];
                        foreach ($cellIterator as $cell) {
                            $cells[] = $cell->getValue();
                        }
                        // Col A=Week, B=Date, C=Activity
                        if (isset($cells[0]) && $cells[0] == $week) {
                            $dateVal = $cells[1];
                             // Format date if needed
                            if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($dailySheet->getCell('B'.$row->getRowIndex()))) {
                                $dateVal = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateVal)->format('Y-m-d');
                            }
                            $dailyLogs[] = [
                                'row_index' => $row->getRowIndex(),
                                'date' => $dateVal,
                                'activity' => $cells[2] ?? ''
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // Ignore daily log errors for now
                }
            }

            $profileStart = 'Not set';
            $coverSheet = $this->sheetByName($spreadsheet, 'COVER-PAGE');
            if ($coverSheet) {
                $profileStart = $this->readCoverProfile($coverSheet)['start_date'];
            }

            return response()->json([
                'start_date' => $sheet->getCell('D2')->getFormattedValue(),
                'end_date' => $sheet->getCell('E2')->getFormattedValue(),
                'days_present' => $sheet->getCell('D4')->getValue(),
                'days_absent' => $sheet->getCell('D5')->getValue(),
                'summary' => $this->weekSummary($sheet),
                'daily_logs' => $dailyLogs,
                'internship_start' => $profileStart
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeDaily(Request $request)
    {
        $request->validate([
            'week' => 'required|integer',
            'date' => 'required|date',
            'activity' => 'required|string',
        ]);

        // Date Range Validation
        $filePath = $this->activityLogPath();
        if (file_exists($filePath)) {
            try {
                $spreadsheet = $this->loadSpreadsheet($filePath);
                $coverSheet = $this->sheetByName($spreadsheet, 'COVER-PAGE');
                if ($coverSheet) {
                    $internStart = $this->readCoverProfile($coverSheet)['start_date'] ?? '';
                    $start = $this->parseFlexibleDate((string) $internStart);
                    if ($start) {
                        $weekStart = clone $start;
                        $weekStart->modify('+' . ($request->week - 1) * 7 . ' days');
                        $weekEnd = clone $weekStart;
                        $weekEnd->modify('+4 days'); // Friday

                        $logDate = new \DateTime($request->date);
                        $today = new \DateTime('today');

                        if ($logDate < $weekStart || $logDate > $weekEnd) {
                            return response()->json(['error' => "Pick a date in Week {$request->week} ({$weekStart->format('Y-m-d')} to {$weekEnd->format('Y-m-d')})."], 422);
                        }

                        if ($logDate > $today) {
                            return response()->json(['error' => 'Future dates cannot be logged.'], 422);
                        }
                    }
                }
            } catch (\Exception $e) {
            }
        }

        // Backend Lock Check
        if ($request->week > 1) {
            $prevData = $this->getWeekDataInternal($request->week - 1);
            if (!isset($prevData['status']) || $prevData['status'] !== 'Completed') {
                return response()->json(['error' => "Week {$request->week} is locked."], 403);
            }
        }

        $filePath = $this->dailyReportsPath();
        if (!file_exists($filePath)) {
            return response()->json(['error' => 'Daily Reports file not found'], 404);
        }

        try {
            $spreadsheet = $this->loadSpreadsheet($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            
            // Find next empty row
            $highestRow = $sheet->getHighestRow() + 1;
            
            $sheet->setCellValue('A' . $highestRow, $request->week);
            $sheet->setCellValue('B' . $highestRow, $request->date);
            $sheet->setCellValue('C' . $highestRow, $request->activity);

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($filePath);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateDaily(Request $request)
    {
        $request->validate([
            'row_index' => 'required|integer',
            'date' => 'required|date',
            'activity' => 'required|string',
        ]);

        $filePath = $this->dailyReportsPath();
        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        try {
            $spreadsheet = $this->loadSpreadsheet($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            $row = $request->row_index;
            $sheet->setCellValue('B' . $row, $request->date);
            $sheet->setCellValue('C' . $row, $request->activity);

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($filePath);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteDaily(Request $request)
    {
        $request->validate([
            'row_index' => 'required|integer',
        ]);

        $filePath = $this->dailyReportsPath();
        if (!file_exists($filePath)) {
            return response()->json(['error' => 'Daily Reports file not found'], 404);
        }

        try {
            $spreadsheet = $this->loadSpreadsheet($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->removeRow($request->row_index);

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($filePath);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'reg_number' => 'required|string',
            'company' => 'required|string',
            'supervisor' => 'nullable|string',
            'supervisor_email' => 'nullable|email',
            'supervisor_signature' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'start_date' => 'required|date',
        ]);

        $filePath = $this->activityLogPath();
        if (!file_exists($filePath)) {
            return back()->withErrors(['error' => 'Excel file not found']);
        }

        try {
            $spreadsheet = $this->loadSpreadsheet($filePath);
            $sheet = $this->sheetByName($spreadsheet, 'COVER-PAGE');
            
            if (!$sheet) {
                return back()->withErrors(['error' => 'COVER-PAGE not found in Excel file']);
            }

            $this->writeCoverProfile($sheet, [
                'name' => $request->name,
                'reg_number' => $request->reg_number,
                'company' => $request->company,
                'supervisor' => $request->supervisor,
                'supervisor_email' => $request->supervisor_email,
                'start_date' => $request->start_date,
            ]);

            if ($request->hasFile('supervisor_signature')) {
                $this->embedSupervisorSignatureImage($sheet, $request->file('supervisor_signature'));
            }

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($filePath);

            return back()->with('success', 'Profile saved to the activity log workbook.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update profile: ' . $e->getMessage()]);
        }
    }
}
