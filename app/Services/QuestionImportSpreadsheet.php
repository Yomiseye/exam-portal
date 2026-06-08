<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class QuestionImportSpreadsheet
{
    /**
     * Read the first worksheet from an XLSX file into normalized associative rows.
     *
     * @return array<int, array<string, string>>
     */
    public function rows(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('The uploaded Excel file could not be opened.');
        }

        $sharedStrings = $this->sharedStrings($zip);
        $worksheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($worksheetXml === false) {
            throw new RuntimeException('The uploaded Excel file does not contain a first worksheet.');
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
}
