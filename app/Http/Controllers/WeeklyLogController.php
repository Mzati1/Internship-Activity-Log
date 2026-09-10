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

        if (is_numeric($val) && (float) $val > 20000 && (float) $val < 80000) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $val)->format('Y-m-d');
            } catch (\Exception $e) {
                // fall through
            }
        }

        return (string) $val;
    }

    public function index()
    {
        $filePath = $this->activityLogPath();
        $dailyFile = $this->dailyReportsPath();

        $dailyCounts = [];

        // 1. Pre-load Daily Log Counts (Unique dates per week)
        if (file_exists($dailyFile)) {
            try {
                $dailySpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($dailyFile);
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
            'name' => 'Not Set',
            'reg_number' => 'Not Set',
            'company' => 'Not Set',
            'supervisor' => 'Not Set',
            'supervisor_email' => 'Not Set',
            'supervisor_signature' => 'Not Set',
            'start_date' => 'Not Set',
        ];

        if (file_exists($filePath)) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

                // Fetch Student Details from COVER-PAGE
                $coverSheet = $spreadsheet->getSheetByName('COVER-PAGE');
                if ($coverSheet) {
                    $studentDetails['name'] = $coverSheet->getCell('C3')->getValue() ?: 'Not Set';
                    $studentDetails['reg_number'] = $coverSheet->getCell('C4')->getValue() ?: 'Not Set';
                    $studentDetails['company'] = $coverSheet->getCell('C5')->getValue() ?: 'Not Set';
                    $studentDetails['supervisor'] = $coverSheet->getCell('C6')->getValue() ?: 'Not Set';
                    $studentDetails['supervisor_email'] = $coverSheet->getCell('C7')->getValue() ?: 'Not Set';
                    $studentDetails['start_date'] = $this->formatExcelDate($coverSheet->getCell('C8'));
                    $studentDetails['supervisor_signature'] = $this->hasSupervisorSignatureImage($coverSheet) ? 'Uploaded' : 'Not Set';
                }

                for ($i = 1; $i <= 16; $i++) {
                    $sheetName = "WEEK-{$i}";
                    $sheet = $spreadsheet->getSheetByName($sheetName);
                    
                    $status = 'Pending';
                    $summary = '';
                    $isLocked = !$previousWeekCompleted;

                    if ($sheet) {
                        $summary = trim($sheet->getCell('A7')->getValue() ?? '');
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

                    // For next iteration
                    $previousWeekCompleted = ($status === 'Completed');
                }
            } catch (\Exception $e) {
                // Handle error gracefully
            }
        } else {
            // Fallback if file missing
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
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                    $coverSheet = $spreadsheet->getSheetByName('COVER-PAGE');
                    if ($coverSheet) {
                        $startDate = $this->formatExcelDate($coverSheet->getCell('C8'), '');
                        if ($startDate) {
                            $start = new \DateTime($startDate);
                            $today = new \DateTime('today');
                            $diff = $start->diff($today);
                            $daysDiff = $diff->days + 1;
                            $week = max(1, min(16, ceil($daysDiff / 7)));
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
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getSheetByName("WEEK-{$week}");
            if (!$sheet) {
                return [];
            }

            $summary = trim($sheet->getCell('A7')->getValue() ?? '');

            // Daily check
            $dailyFile = $this->dailyReportsPath();
            $daysLogged = 0;
            if (file_exists($dailyFile)) {
                $dailySpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($dailyFile);
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
            
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getSheetByName($sheetName);

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
            $sheet->setCellValue('A7', $validated['summary']); // Assuming it starts here

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($filePath);

            return back()->with('success', "Log for Week {$weekNumber} saved successfully!");

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
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getSheetByName($sheetName);

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
                    $dailySpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($dailyFile);
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

            $profileStart = 'Not Set';
            $coverSheet = $spreadsheet->getSheetByName('COVER-PAGE');
            if ($coverSheet) {
                $profileStart = $this->formatExcelDate($coverSheet->getCell('C8'));
            }

            return response()->json([
                'start_date' => $sheet->getCell('D2')->getFormattedValue(),
                'end_date' => $sheet->getCell('E2')->getFormattedValue(),
                'days_present' => $sheet->getCell('D4')->getValue(),
                'days_absent' => $sheet->getCell('D5')->getValue(),
                'summary' => $sheet->getCell('A7')->getValue(),
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
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                $coverSheet = $spreadsheet->getSheetByName('COVER-PAGE');
                if ($coverSheet) {
                    $internStart = $this->formatExcelDate($coverSheet->getCell('C8'), '');
                    if ($internStart) {
                        $start = new \DateTime($internStart);
                        $weekStart = clone $start;
                        $weekStart->modify('+' . ($request->week - 1) * 7 . ' days');
                        $weekEnd = clone $weekStart;
                        $weekEnd->modify('+4 days'); // Friday

                        $logDate = new \DateTime($request->date);
                        $today = new \DateTime('today');

                        if ($logDate < $weekStart || $logDate > $weekEnd) {
                            return response()->json(['error' => "Date must be within Week {$request->week} range (" . $weekStart->format('Y-m-d') . " to " . $weekEnd->format('Y-m-d') . ")"], 422);
                        }

                        if ($logDate > $today) {
                            return response()->json(['error' => "You cannot log activities for future dates."], 422);
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
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
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
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
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
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
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
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getSheetByName('COVER-PAGE');
            
            if (!$sheet) {
                return back()->withErrors(['error' => 'COVER-PAGE not found in Excel file']);
            }

            $sheet->setCellValue('C3', $request->name);
            $sheet->setCellValue('C4', $request->reg_number);
            $sheet->setCellValue('C5', $request->company);
            $sheet->setCellValue('C6', $request->supervisor);
            $sheet->setCellValue('C7', $request->supervisor_email);
            $sheet->setCellValue('C8', $request->start_date);
            $sheet->setCellValue('B9', 'SUPERVISOR SIGNATURE:');
            $sheet->setCellValue('C9', '');

            if ($request->hasFile('supervisor_signature')) {
                $this->embedSupervisorSignatureImage($sheet, $request->file('supervisor_signature'));
            }

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($filePath);

            return back()->with('success', 'Profile updated successfully!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update profile: ' . $e->getMessage()]);
        }
    }
}
