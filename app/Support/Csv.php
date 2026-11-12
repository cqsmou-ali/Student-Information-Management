<?php

namespace App\Support;

class Csv
{
    /** Stops spreadsheet formula injection: a cell starting with = + - @ is stored as text. */
    public static function safe(mixed $value): string
    {
        $v = (string) $value;

        return $v !== '' && str_contains("=+-@\t\r", $v[0]) ? "'" . $v : $v;
    }
}
