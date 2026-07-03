<?php

namespace App\Support;

class Isk
{
    /**
     * Format a whole-krónur integer as Icelandic currency, e.g. 1250 => "1.250 kr.".
     * Dot thousands separator, no decimals.
     */
    public static function format(int $amount): string
    {
        return number_format($amount, 0, ',', '.').' kr.';
    }
}
