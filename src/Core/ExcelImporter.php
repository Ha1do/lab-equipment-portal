<?php
declare(strict_types=1);

require_once BASE_PATH . '/src/Core/libs/SimpleXLSX.php';
require_once BASE_PATH . '/src/Core/libs/SimpleXLS.php';

use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLS;

class ExcelImporter
{
    // Columns found by header name
    private const HEADER_MAP = [
        'Č. v SAP' => 'sap_num',
        'P'        => 'category_symbol',
        'Č'        => 'category_num1',
        'R'        => 'category_num2',
        '2r'       => 'category_num3',
        'v r'      => 'write_year',
        'NÁZOV'    => 'name',
        'tr'       => 'triedenie',
        'pol'      => 'shelf',
        'poz'      => 'note',
    ];

    // Columns found by fixed 0-based index (no header in file)
    private const FIXED_COLS = [
        31 => 'personal',         // AF - sk m
        32 => 'sap_position',     // AG - m SAP (room code)
        33 => 'new_sap_position', // AH - nm SAP (stored, not displayed)
        36 => 'closet',           // AK - skr
    ];

    public static function parse(string $filePath): array
    {
        $ext  = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $rows = match($ext) {
            'xlsx'  => self::readXlsx($filePath),
            'xls'   => self::readXls($filePath),
            'csv'   => self::readCsv($filePath),
            default => throw new RuntimeException("Nepodporovaný formát súboru: .$ext"),
        };

        return self::buildItems($rows);
    }

    private static function readXlsx(string $filePath): array
    {
        $xlsx = SimpleXLSX::parse($filePath);
        if (!$xlsx) {
            throw new RuntimeException('Chyba čítania .xlsx: ' . SimpleXLSX::parseError());
        }
        return $xlsx->rows();
    }

    private static function readXls(string $filePath): array
    {
        $xls = SimpleXLS::parse($filePath);
        if (!$xls) {
            throw new RuntimeException('Chyba čítania .xls: ' . SimpleXLS::parseError());
        }
        return $xls->rows();
    }

    private static function readCsv(string $filePath): array
    {
        $rows   = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new RuntimeException('Nepodarilo sa otvoriť CSV súbor.');
        }
        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = array_map('trim', $row);
        }
        fclose($handle);
        return $rows;
    }

    private static function buildItems(array $rawRows): array
    {
        $items  = [];
        $errors = [];

        // Build colIndex → fieldName map from header row
        $headerRow  = array_map('trim', (array)($rawRows[0] ?? []));
        $colToField = [];
        foreach ($headerRow as $colIndex => $label) {
            if (isset(self::HEADER_MAP[$label])) {
                $colToField[$colIndex] = self::HEADER_MAP[$label];
            }
        }

        foreach ($rawRows as $rowIndex => $row) {
            if ($rowIndex === 0) continue;

            $excelRowNum = $rowIndex + 1;

            // Extract named columns
            $data = [];
            foreach ($colToField as $colIndex => $fieldName) {
                $data[$fieldName] = trim((string)($row[$colIndex] ?? ''));
            }

            // Extract fixed columns
            foreach (self::FIXED_COLS as $colIndex => $fieldName) {
                $data[$fieldName] = trim((string)($row[$colIndex] ?? ''));
            }

            // Fill missing fields with empty string
            $allFields = array_unique(array_merge(
                array_values(self::HEADER_MAP),
                array_values(self::FIXED_COLS)
            ));
            foreach ($allFields as $fieldName) {
                if (!isset($data[$fieldName])) {
                    $data[$fieldName] = '';
                }
            }

            $sapNum = $data['sap_num'];
            $name   = $data['name'];

            // No sap_num → end of real data, skip silently
            if ($sapNum === '') continue;

            // sap_num present but name missing → log error
            if ($name === '') {
                $errors[] = [
                    'status' => 'error',
                    'row'    => $excelRowNum,
                    'sap'    => $sapNum,
                    'name'   => '—',
                    'msg'    => "Riadok $excelRowNum (SAP: $sapNum): chýba povinné pole NÁZOV.",
                ];
                continue;
            }

            $data['_row'] = $excelRowNum;
            $items[]      = $data;
        }

        return ['items' => $items, 'errors' => $errors];
    }
}