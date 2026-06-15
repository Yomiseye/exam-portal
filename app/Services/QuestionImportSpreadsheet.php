<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class QuestionImportSpreadsheet
{
    /**
     * List worksheets inside an XLSX file.
     *
     * @return array<int, array{index: int, name: string, path: string}>
     */
    public function sheets(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('The uploaded Excel file could not be opened.');
        }

        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relationshipsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relationshipsXml === false) {
            $hasFirstWorksheet = $zip->getFromName('xl/worksheets/sheet1.xml') !== false;
            $zip->close();

            return $hasFirstWorksheet
                ? [['index' => 0, 'name' => 'Sheet 1', 'path' => 'xl/worksheets/sheet1.xml']]
                : [];
        }

        $workbook = simplexml_load_string($workbookXml);
        $relationships = simplexml_load_string($relationshipsXml);

        if (! $workbook instanceof SimpleXMLElement || ! $relationships instanceof SimpleXMLElement) {
            $zip->close();

            return [];
        }

        $targets = [];

        foreach ($relationships->Relationship as $relationship) {
            $id = (string) $relationship['Id'];
            $target = (string) $relationship['Target'];

            if ($id !== '' && str_contains($target, 'worksheets/')) {
                $targets[$id] = $this->worksheetPath($target);
            }
        }

        $sheets = [];

        foreach ($workbook->sheets->sheet as $index => $sheet) {
            $relationshipAttributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $relationshipId = (string) ($relationshipAttributes['id'] ?? '');
            $worksheetPath = $targets[$relationshipId] ?? null;

            if ($worksheetPath && $zip->getFromName($worksheetPath) !== false) {
                $sheets[] = [
                    'index' => (int) $index,
                    'name' => (string) $sheet['name'],
                    'path' => $worksheetPath,
                ];
            }
        }

        $zip->close();

        return $sheets;
    }

    /**
     * Read a worksheet from an XLSX file into normalized associative rows.
     *
     * @return array<int, array<string, string>>
     */
    public function rows(string $path, int $sheetIndex = 0): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('The uploaded Excel file could not be opened.');
        }

        $sharedStrings = $this->sharedStrings($zip);
        $sheets = $this->sheetsFromOpenZip($zip);
        $selectedSheet = collect($sheets)->firstWhere('index', $sheetIndex);
        $worksheetPath = $selectedSheet['path'] ?? 'xl/worksheets/sheet1.xml';
        $worksheetXml = $zip->getFromName($worksheetPath);
        $zip->close();

        if ($worksheetXml === false) {
            throw new RuntimeException('The selected Excel worksheet could not be found.');
        }

        $worksheet = simplexml_load_string($worksheetXml);

        if (! $worksheet instanceof SimpleXMLElement) {
            throw new RuntimeException('The uploaded Excel worksheet could not be read.');
        }

        $rows = [];

        foreach ($worksheet->sheetData->row as $row) {
            $values = [];

            foreach ($row->c as $cell) {
                $reference = (string) $cell['r'];
                $column = $this->columnIndex($reference);
                $values[$column] = $this->cellValue($cell, $sharedStrings);
            }

            if ($values !== []) {
                $rows[] = $values;
            }
        }

        if ($rows === []) {
            return [];
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader($header), $rows[0]);
        $importRows = [];

        foreach (array_slice($rows, 1) as $row) {
            $mapped = [];

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $mapped[$header] = trim($row[$index] ?? '');
            }

            if (collect($mapped)->filter(fn ($value) => $value !== '')->isNotEmpty()) {
                $importRows[] = $mapped;
            }
        }

        return $importRows;
    }

    /**
     * @return array<int, string>
     */
    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $sharedStrings = simplexml_load_string($xml);

        if (! $sharedStrings instanceof SimpleXMLElement) {
            return [];
        }

        $strings = [];

        foreach ($sharedStrings->si as $string) {
            if (isset($string->t)) {
                $strings[] = (string) $string->t;

                continue;
            }

            $parts = [];

            foreach ($string->r as $run) {
                $parts[] = (string) $run->t;
            }

            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     */
    private function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];

        if ($type === 's') {
            return $sharedStrings[(int) $cell->v] ?? '';
        }

        if ($type === 'inlineStr') {
            return (string) ($cell->is->t ?? '');
        }

        return (string) ($cell->v ?? '');
    }

    private function columnIndex(string $reference): int
    {
        preg_match('/^[A-Z]+/i', $reference, $matches);
        $letters = strtoupper($matches[0] ?? 'A');
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function normalizeHeader(string $header): string
    {
        return str($header)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }

    private function worksheetPath(string $target): string
    {
        $target = ltrim(str_replace('\\', '/', $target), '/');

        return str_starts_with($target, 'xl/')
            ? $target
            : 'xl/'.$target;
    }

    /**
     * @return array<int, array{index: int, name: string, path: string}>
     */
    private function sheetsFromOpenZip(ZipArchive $zip): array
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relationshipsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relationshipsXml === false) {
            return [['index' => 0, 'name' => 'Sheet 1', 'path' => 'xl/worksheets/sheet1.xml']];
        }

        $workbook = simplexml_load_string($workbookXml);
        $relationships = simplexml_load_string($relationshipsXml);

        if (! $workbook instanceof SimpleXMLElement || ! $relationships instanceof SimpleXMLElement) {
            return [['index' => 0, 'name' => 'Sheet 1', 'path' => 'xl/worksheets/sheet1.xml']];
        }

        $targets = [];

        foreach ($relationships->Relationship as $relationship) {
            $id = (string) $relationship['Id'];
            $target = (string) $relationship['Target'];

            if ($id !== '' && str_contains($target, 'worksheets/')) {
                $targets[$id] = $this->worksheetPath($target);
            }
        }

        $sheets = [];

        foreach ($workbook->sheets->sheet as $index => $sheet) {
            $relationshipAttributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $relationshipId = (string) ($relationshipAttributes['id'] ?? '');
            $worksheetPath = $targets[$relationshipId] ?? null;

            if ($worksheetPath) {
                $sheets[(int) $index] = [
                    'index' => (int) $index,
                    'name' => (string) $sheet['name'],
                    'path' => $worksheetPath,
                ];
            }
        }

        return $sheets ?: [['index' => 0, 'name' => 'Sheet 1', 'path' => 'xl/worksheets/sheet1.xml']];
    }
}
