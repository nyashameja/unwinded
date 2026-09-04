<?php
declare(strict_types=1);

namespace Unwinded\Support;

/**
 * CSV export helper. Sanitises values to prevent CSV injection.
 * Streams to output or returns as a string.
 */
final class Csv
{
    private const DANGEROUS_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    public static function sanitise(mixed $value): string
    {
        $str = (string) $value;
        if ($str !== '' && in_array($str[0], self::DANGEROUS_PREFIXES, true)) {
            $str = "'" . $str;
        }
        return $str;
    }

    public static function generate(array $headers, array $rows): string
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, array_map([self::class, 'sanitise'], $row));
        }
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);
        return $content;
    }

    public static function download(string $filename, array $headers, array $rows): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
        header('Cache-Control: no-store');
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // BOM for Excel UTF-8 compatibility
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, array_map([self::class, 'sanitise'], $row));
        }
        fclose($out);
        exit;
    }
}
