<?php
declare(strict_types=1);

namespace TwoQuick\Api\Helper;

class StringHelper
{
    /**
     * конвертирует пробелы в строке в %20 для вставки в урл
     * @param string $value
     * @return string
     */
    public static function convertWhitespaceUri(string $value): string
    {
        return str_replace(' ', '%20', $value);
    }
}
