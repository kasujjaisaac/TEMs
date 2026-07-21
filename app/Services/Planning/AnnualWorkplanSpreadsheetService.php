<?php

namespace App\Services\Planning;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

class AnnualWorkplanSpreadsheetService
{
    private const MAIN_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const PACKAGE_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';

    public function templateBinary(string $yearName = '2026/2027'): string
    {
        $months = $this->templateMonths($yearName);
        $rows = [
            1 => ['TEXARO TECHNOLOGIES LIMITED - ANNUAL CORPORATE WORKPLAN'],
            2 => ['Growth | Revenue | Profitability | Sustainability | Investment Readiness'],
            4 => PlanningPerformanceService::ANNUAL_WORKPLAN_HEADERS,
        ];
        $merges = ['A1:V1', 'A2:V2'];
        $rowStyles = [1 => 1, 2 => 2, 4 => 3];
        $rowHeights = [1 => 28, 2 => 22, 4 => 34];

        $rowNumber = 5;
        foreach ($months as $month) {
            $rows[$rowNumber] = [$month['label']];
            $merges[] = 'A' . $rowNumber . ':V' . $rowNumber;
            $rowStyles[$rowNumber] = 4;
            $rowHeights[$rowNumber] = 22;
            $rowNumber++;

            foreach ($month['examples'] as $example) {
                $rows[$rowNumber] = $example;
                $rowHeights[$rowNumber] = 44;
                $rowNumber++;
            }

            for ($i = 0; $i < 3; $i++) {
                $rows[$rowNumber] = array_fill(0, count(PlanningPerformanceService::ANNUAL_WORKPLAN_HEADERS), '');
                $rowHeights[$rowNumber] = 32;
                $rowNumber++;
            }
        }

        $totalRow = $rowNumber;
        $rows[$totalRow] = array_merge(
            ['ANNUAL TOTALS', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['=SUM(P6:P' . ($totalRow - 1) . ')', '=SUM(Q6:Q' . ($totalRow - 1) . ')', '=SUM(R6:R' . ($totalRow - 1) . ')'],
            ['', '', '', '']
        );
        $merges[] = 'A' . $totalRow . ':O' . $totalRow;
        $rowStyles[$totalRow] = 5;

        $instructions = [
            1 => ['TEMS Workplan Upload Template'],
            3 => ['How to use this template'],
            4 => ['1. Fill the Annual Workplan sheet only. Keep the column names unchanged.'],
            5 => ['2. Use one activity per row. Month bands and totals are ignored during import.'],
            6 => ['3. TEMS turns each row into a workplan target, monthly/weekly allocations, assignments, and audit trail.'],
            7 => ['4. Lead Unit may contain Finance, HR, Customer Success, Engineering, Commercial, Sales, or Management.'],
            8 => ['5. Revenue Target, Direct Cost, and Gross Profit should be numbers without commas where possible.'],
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'tems-workplan-');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('docProps/core.xml', $this->coreXml());
        $zip->addFromString('docProps/app.xml', $this->appXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($instructions, [], [1 => 1, 3 => 3], [1 => 28], [38]));
        $zip->addFromString('xl/worksheets/sheet2.xml', $this->worksheetXml($rows, $merges, $rowStyles, $rowHeights, [9, 14, 24, 42, 32, 22, 12, 8, 8, 8, 8, 24, 26, 12, 20, 20, 18, 19, 25, 26, 34, 14]));
        $zip->close();

        $binary = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $binary;
    }

    public function parseAnnualWorkplan(UploadedFile $file): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($file->getRealPath()) !== true) {
            return [[], ['Unable to open the uploaded Excel workplan file.']];
        }

        $sheetPath = $this->annualWorkplanSheetPath($zip);
        $sheetXml = $sheetPath ? $zip->getFromName($sheetPath) : false;
        if ($sheetXml === false && $zip->locateName('xl/worksheets/sheet2.xml') !== false) {
            $sheetXml = $zip->getFromName('xl/worksheets/sheet2.xml');
        }
        if ($sheetXml === false) {
            $zip->close();
            return [[], ['The workbook must include an Annual Workplan sheet.']];
        }

        $sharedStrings = $this->sharedStrings($zip);
        $zip->close();

        $worksheet = simplexml_load_string($sheetXml);
        if ($worksheet === false) {
            return [[], ['The Annual Workplan sheet could not be read.']];
        }

        $table = [];
        foreach ($worksheet->children(self::MAIN_NS)->sheetData->children(self::MAIN_NS)->row as $row) {
            $rowIndex = (int) $row->attributes()['r'];
            $table[$rowIndex] = $this->rowValues($row, $sharedStrings);
        }

        $headerRow = $this->headerRowNumber($table);
        if (! $headerRow) {
            return [[], ['The Annual Workplan sheet is missing the expected column header row.']];
        }

        $title = trim((string) ($table[1][1] ?? 'Annual Corporate Workplan'));
        $subtitle = trim((string) ($table[2][1] ?? ''));
        $yearRange = $this->yearRangeFromText($title . ' ' . $subtitle . ' ' . $file->getClientOriginalName());
        $workplanCode = 'CORP-ANNUAL-' . str_replace('/', '-', $yearRange);
        $workplanTitle = 'Annual Corporate Workplan ' . $yearRange;

        $headers = [];
        foreach ($table[$headerRow] as $column => $value) {
            $headers[$column] = strtolower(trim((string) $value));
        }

        $rows = [];
        $errors = [];
        foreach ($table as $rowNumber => $values) {
            if ($rowNumber <= $headerRow) {
                continue;
            }

            $annual = $this->annualRow($headers, $values);
            if ($this->isIgnorableAnnualRow($annual)) {
                continue;
            }

            $activity = trim((string) ($annual['activity / action'] ?? ''));
            if ($activity === '') {
                continue;
            }

            $dates = $this->monthDates((string) ($annual['month'] ?? ''), $yearRange);
            if (! $dates) {
                $errors[] = 'Row ' . $rowNumber . ' has an unknown month. Use values such as July 2026.';
                continue;
            }

            $targetValue = $this->number($annual['revenue target (ugx)'] ?? null);
            $unit = 'UGX';
            $targetType = 'Financial';
            if ($targetValue <= 0) {
                $targetValue = max(1, $this->number($annual['target units'] ?? null));
                $unit = 'units';
                $targetType = 'Numeric';
            }

            $leadUnit = (string) ($annual['lead unit'] ?? '');
            $description = $this->annualDescription($annual);
            $rows[] = [
                'workplan_code' => $workplanCode,
                'workplan_title' => $workplanTitle,
                'workplan_level' => 'Corporate',
                'workplan_description' => $subtitle ?: 'Imported annual corporate workplan.',
                'objective_code' => '',
                'target_reference' => 'AWP-' . str_replace('/', '-', $yearRange) . '-' . str_pad((string) $rowNumber, 3, '0', STR_PAD_LEFT),
                'target_title' => $activity,
                'target_type' => $targetType,
                'kpi' => trim((string) ($annual['kpi / success indicator'] ?? '')) ?: $activity,
                'target_value' => (string) $targetValue,
                'actual_value' => '0',
                'unit' => $unit,
                'priority' => trim((string) ($annual['priority'] ?? 'Medium')) ?: 'Medium',
                'weight' => '10',
                'starts_on' => $dates[0]->toDateString(),
                'due_on' => $dates[1]->toDateString(),
                'department_code' => $this->departmentCodeFromLeadUnit($leadUnit),
                'position_code' => '',
                'employee_email' => '',
                'assignment_role' => 'Accountable',
                'required_evidence_type' => trim((string) ($annual['expected deliverable'] ?? '')),
                'quality_standard' => 'Evidence must be dated, attributable, and supervisor-verifiable.',
                'description' => $description,
            ];
        }

        if ($rows === []) {
            $errors[] = 'The Annual Workplan sheet has no importable activity rows.';
        }

        return [$rows, $errors];
    }

    private function templateMonths(string $yearName): array
    {
        [$startYear, $endYear] = array_pad(explode('/', $yearName), 2, (string) ((int) substr($yearName, 0, 4) + 1));

        return [
            ['label' => 'JULY ' . $startYear . ' - FOUNDATION, MARKET VALIDATION AND FIRST PAYING CLIENTS', 'examples' => [$this->exampleRow('Q1', 'July ' . $startYear, 'Validate high-value customer pain points', 'Run executive discovery meetings with priority accounts', 'Discovery notes and qualified opportunity list', 'Commercial Operations', 'High', 'Plan', 'Meet', 'Qualify', 'Review', 'New customer acquisition', 'Enterprise and SME customers', 5, 1000000)]],
            ['label' => 'AUGUST ' . $startYear . ' - SALES PIPELINE BUILDING', 'examples' => [$this->exampleRow('Q1', 'August ' . $startYear, 'Create predictable commercial pipeline', 'Issue proposals and follow up qualified leads', 'Proposal register and follow-up evidence', 'Sales and Marketing', 'High', 'Draft', 'Send', 'Follow up', 'Close', 'Quotation conversion', 'Qualified leads', 8, 1500000)]],
            ['label' => 'SEPTEMBER ' . $startYear . ' - FIRST QUARTER EXECUTION REVIEW', 'examples' => []],
            ['label' => 'OCTOBER ' . $startYear . ' - DELIVERY QUALITY AND CASH COLLECTION', 'examples' => []],
            ['label' => 'NOVEMBER ' . $startYear . ' - CUSTOMER RETENTION AND SUPPORT', 'examples' => []],
            ['label' => 'DECEMBER ' . $startYear . ' - HALF YEAR PERFORMANCE CONTROL', 'examples' => []],
            ['label' => 'JANUARY ' . $endYear . ' - SCALE READY OPERATIONS', 'examples' => []],
            ['label' => 'FEBRUARY ' . $endYear . ' - PRODUCTIVITY AND PROFITABILITY', 'examples' => []],
            ['label' => 'MARCH ' . $endYear . ' - MARKET EXPANSION', 'examples' => []],
            ['label' => 'APRIL ' . $endYear . ' - INVESTMENT READINESS', 'examples' => []],
            ['label' => 'MAY ' . $endYear . ' - ANNUAL CLOSING EXECUTION', 'examples' => []],
            ['label' => 'JUNE ' . $endYear . ' - FINAL REVIEW AND NEXT YEAR BASELINE', 'examples' => []],
        ];
    }

    private function exampleRow(string $quarter, string $month, string $result, string $activity, string $deliverable, string $leadUnit, string $priority, string $w1, string $w2, string $w3, string $w4, string $mechanism, string $market, int $units, int $price): array
    {
        $revenue = $units * $price;
        $cost = (int) round($revenue * 0.35);

        return [
            $quarter, $month, $result, $activity, $deliverable, $leadUnit, $priority,
            $w1, $w2, $w3, $w4, $mechanism, $market, $units, $price,
            $revenue, $cost, $revenue - $cost, 'Growth execution budget',
            'Revenue and evidence recorded in TEMS', 'Pipeline risk mitigated through weekly reviews', 'Planned',
        ];
    }

    private function annualWorkplanSheetPath(\ZipArchive $zip): ?string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookXml === false || $relsXml === false) {
            return $zip->locateName('xl/worksheets/sheet2.xml') !== false ? 'xl/worksheets/sheet2.xml' : null;
        }

        $workbook = simplexml_load_string($workbookXml);
        $rels = simplexml_load_string($relsXml);
        if ($workbook === false || $rels === false) {
            return null;
        }

        $targets = [];
        foreach ($rels->children(self::PACKAGE_NS)->Relationship as $rel) {
            $attrs = $rel->attributes();
            $targets[(string) $attrs['Id']] = (string) $attrs['Target'];
        }

        $fallback = null;
        foreach ($workbook->children(self::MAIN_NS)->sheets->children(self::MAIN_NS)->sheet as $sheet) {
            $attrs = $sheet->attributes();
            $relAttrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $name = strtolower((string) $attrs['name']);
            $rid = (string) $relAttrs['id'];
            $target = $targets[$rid] ?? null;
            if (! $target) {
                continue;
            }
            $path = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/' . ltrim($target, '/');
            if ($fallback === null) {
                $fallback = $path;
            }
            if ($name === 'annual workplan') {
                return $path;
            }
        }

        return $fallback ?: ($zip->locateName('xl/worksheets/sheet2.xml') !== false ? 'xl/worksheets/sheet2.xml' : null);
    }

    private function sharedStrings(\ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $sst = simplexml_load_string($xml);
        if ($sst === false) {
            return [];
        }

        $strings = [];
        foreach ($sst->children(self::MAIN_NS)->si as $si) {
            $strings[] = $this->nodeText($si);
        }

        return $strings;
    }

    private function rowValues(\SimpleXMLElement $row, array $sharedStrings): array
    {
        $values = [];
        foreach ($row->children(self::MAIN_NS)->c as $cell) {
            $attrs = $cell->attributes();
            $column = $this->columnNumber((string) $attrs['r']);
            $type = (string) $attrs['t'];
            $children = $cell->children(self::MAIN_NS);

            if ($type === 's') {
                $values[$column] = $sharedStrings[(int) $children->v] ?? '';
            } elseif ($type === 'inlineStr') {
                $values[$column] = $this->nodeText($children->is);
            } else {
                $values[$column] = trim((string) $children->v);
            }
        }

        return $values;
    }

    private function nodeText(\SimpleXMLElement $node): string
    {
        $text = trim((string) $node->children(self::MAIN_NS)->t);
        foreach ($node->children(self::MAIN_NS)->r as $run) {
            $text .= (string) $run->children(self::MAIN_NS)->t;
        }

        return $text;
    }

    private function headerRowNumber(array $table): ?int
    {
        foreach ($table as $rowNumber => $values) {
            $normalized = array_map(fn ($value): string => strtolower(trim((string) $value)), $values);
            if (in_array('quarter', $normalized, true) && in_array('activity / action', $normalized, true)) {
                return $rowNumber;
            }
        }

        return null;
    }

    private function annualRow(array $headers, array $values): array
    {
        $row = [];
        foreach ($headers as $column => $header) {
            if ($header !== '') {
                $row[$header] = trim((string) ($values[$column] ?? ''));
            }
        }

        return $row;
    }

    private function isIgnorableAnnualRow(array $row): bool
    {
        $joined = strtoupper(implode(' ', array_filter($row)));
        if ($joined === '') {
            return true;
        }

        return str_contains($joined, 'ANNUAL TOTAL') || (str_contains($joined, ' - ') && empty($row['activity / action']));
    }

    private function yearRangeFromText(string $text): string
    {
        if (preg_match('/(20\d{2})\D+(20\d{2})/', $text, $matches)) {
            return $matches[1] . '/' . $matches[2];
        }

        $start = now()->month >= 7 ? now()->year : now()->year - 1;

        return $start . '/' . ($start + 1);
    }

    private function monthDates(string $month, string $yearRange): ?array
    {
        [$startYear, $endYear] = array_map('intval', explode('/', $yearRange));
        $clean = trim(preg_replace('/\s+/', ' ', $month));
        if ($clean === '') {
            return null;
        }

        if (! preg_match('/\b(20\d{2})\b/', $clean, $yearMatch)) {
            $monthNumber = (int) date('n', strtotime('1 ' . preg_replace('/[^A-Za-z]/', ' ', $clean)));
            $year = $monthNumber >= 7 ? $startYear : $endYear;
            $clean .= ' ' . $year;
        }

        try {
            $date = Carbon::parse('first day of ' . $clean);
        } catch (\Throwable) {
            return null;
        }

        return [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
    }

    private function departmentCodeFromLeadUnit(string $leadUnit): string
    {
        $text = strtolower($leadUnit);

        return match (true) {
            str_contains($text, 'finance') => 'FIN',
            str_contains($text, 'human') || str_contains($text, 'hr') => 'HR',
            str_contains($text, 'customer') || str_contains($text, 'support') => 'CS',
            str_contains($text, 'engineer') || str_contains($text, 'technology') || str_contains($text, 'project') => 'ENG',
            str_contains($text, 'commercial') || str_contains($text, 'sales') || str_contains($text, 'marketing') || str_contains($text, 'communication') => 'COMM',
            str_contains($text, 'management') || str_contains($text, 'executive') || str_contains($text, 'board') => 'EXEC',
            default => '',
        };
    }

    private function annualDescription(array $annual): string
    {
        $parts = [];
        foreach ([
            'strategic result' => 'Strategic result',
            'revenue mechanism' => 'Revenue mechanism',
            'target client / market' => 'Target market',
            'budget line supported' => 'Budget line',
            'risk & mitigation' => 'Risk and mitigation',
            'status' => 'Uploaded status',
        ] as $key => $label) {
            $value = trim((string) ($annual[$key] ?? ''));
            if ($value !== '') {
                $parts[] = $label . ': ' . $value;
            }
        }

        $weeks = [];
        foreach (['week 1', 'week 2', 'week 3', 'week 4'] as $week) {
            $value = trim((string) ($annual[$week] ?? ''));
            if ($value !== '') {
                $weeks[] = strtoupper($week) . ' ' . $value;
            }
        }
        if ($weeks !== []) {
            $parts[] = 'Weekly plan: ' . implode(' | ', $weeks);
        }

        return implode("\n", $parts);
    }

    private function number(mixed $value): float
    {
        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private function worksheetXml(array $rows, array $merges, array $rowStyles, array $rowHeights, array $widths): string
    {
        $maxRow = max(array_keys($rows));
        $maxCol = max(array_map(fn ($row): int => count($row), $rows));
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="' . self::MAIN_NS . '" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<dimension ref="A1:' . $this->columnLetter($maxCol) . $maxRow . '"/>'
            . '<sheetViews><sheetView workbookViewId="0"/></sheetViews><sheetFormatPr defaultRowHeight="18"/><cols>';

        foreach ($widths as $index => $width) {
            $column = $index + 1;
            $xml .= '<col min="' . $column . '" max="' . $column . '" width="' . $width . '" customWidth="1"/>';
        }
        $xml .= '</cols><sheetData>';

        foreach ($rows as $rowNumber => $values) {
            $height = $rowHeights[$rowNumber] ?? 22;
            $xml .= '<row r="' . $rowNumber . '" ht="' . $height . '" customHeight="1">';
            foreach ($values as $index => $value) {
                $cell = $this->columnLetter($index + 1) . $rowNumber;
                $style = $rowStyles[$rowNumber] ?? ($rowNumber % 2 === 0 ? 6 : 0);
                $xml .= $this->cellXml($cell, $value, $style);
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData>';
        if ($merges !== []) {
            $xml .= '<mergeCells count="' . count($merges) . '">';
            foreach ($merges as $merge) {
                $xml .= '<mergeCell ref="' . $merge . '"/>';
            }
            $xml .= '</mergeCells>';
        }
        $xml .= '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/></worksheet>';

        return $xml;
    }

    private function cellXml(string $ref, mixed $value, int $style): string
    {
        if (is_string($value) && str_starts_with($value, '=')) {
            return '<c r="' . $ref . '" s="' . $style . '"><f>' . $this->xml(substr($value, 1)) . '</f><v>0</v></c>';
        }

        if (is_int($value) || is_float($value)) {
            return '<c r="' . $ref . '" s="' . $style . '"><v>' . $value . '</v></c>';
        }

        return '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t>' . $this->xml((string) $value) . '</t></is></c>';
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="' . self::MAIN_NS . '" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Instructions" sheetId="1" r:id="rId1"/><sheet name="Annual Workplan" sheetId="2" r:id="rId2"/></sheets></workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="' . self::MAIN_NS . '"><fonts count="4"><font><sz val="10"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FF9FB5D6"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FF000000"/><name val="Calibri"/></font></fonts><fills count="5"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF111820"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF182330"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FF405166"/></left><right style="thin"><color rgb="FF405166"/></right><top style="thin"><color rgb="FF405166"/></top><bottom style="thin"><color rgb="FF405166"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="7"><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment wrapText="1" vertical="top"/></xf><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment wrapText="1" horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment wrapText="1" vertical="top"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private function coreXml(): string
    {
        $now = now()->toAtomString();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>TEMS</dc:creator><cp:lastModifiedBy>TEMS</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified></cp:coreProperties>';
    }

    private function appXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>TEMS</Application></Properties>';
    }

    private function columnNumber(string $cellRef): int
    {
        preg_match('/^([A-Z]+)/i', $cellRef, $matches);
        $letters = strtoupper($matches[1] ?? 'A');
        $number = 0;
        foreach (str_split($letters) as $letter) {
            $number = ($number * 26) + (ord($letter) - 64);
        }

        return $number;
    }

    private function columnLetter(int $number): string
    {
        $letter = '';
        while ($number > 0) {
            $number--;
            $letter = chr(65 + ($number % 26)) . $letter;
            $number = intdiv($number, 26);
        }

        return $letter;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
