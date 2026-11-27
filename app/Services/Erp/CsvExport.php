<?php

declare(strict_types=1);

namespace App\Services\Erp;

use Symfony\Component\HttpFoundation\StreamedResponse;

/** Spreadsheet-friendly CSV (UTF-8 with BOM so Excel reads Bangla names). Cells that could run as formulas are defused. */
class CsvExport
{
    /** @param list<string> $header @param iterable<list<scalar|null>> $rows */
    public static function download(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, array_map([self::class, 'cell'], $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    public static function cell(mixed $v): string
    {
        $s = (string) $v;

        return $s !== '' && in_array($s[0], ['=', '+', '-', '@', "\t", "\r"], true) && ! is_numeric($s) ? "'".$s : $s;
    }
}
