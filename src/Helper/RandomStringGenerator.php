<?php

namespace App\Helper;

/**
 * Based on https://github.com/SymfonyCasts/reset-password-bundle.
 */
class RandomStringGenerator
{
    public static function create(int $length): string
    {
        $string = '';
        while (($len = strlen($string)) < $length) {
            $size = 20 - $len;
            $bytes = random_bytes($size);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }
}
