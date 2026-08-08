<?php

declare(strict_types=1);
use Illuminate\Support\Str;

if (! function_exists('format_bytes')) {
    function format_bytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $pow = floor(log($bytes, 1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1024 ** $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}

if (! function_exists('generate_public_code')) {
    function generate_public_code(int $length = 10): string
    {
        return Str::lower(Str::random($length));
    }
}

if (! function_exists('base32_decode')) {
    function base32_decode(string $input): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        foreach (str_split(strtoupper($input)) as $char) {
            $pos = strpos($alphabet, $char);

            if ($pos === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $pos;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $output .= chr(($buffer >> ($bitsLeft - 8)) & 0xFF);
                $bitsLeft -= 8;
            }
        }

        return $output;
    }
}

if (! function_exists('base32_encode')) {
    function base32_encode(string $input): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $buffer = 0;
        $bitsLeft = 0;

        foreach (str_split($input) as $byte) {
            $buffer = ($buffer << 8) | ord($byte);
            $bitsLeft += 8;

            while ($bitsLeft >= 5) {
                $output .= $alphabet[($buffer >> ($bitsLeft - 5)) & 0x1F];
                $bitsLeft -= 5;
            }
        }

        if ($bitsLeft > 0) {
            $output .= $alphabet[($buffer << (5 - $bitsLeft)) & 0x1F];
        }

        return $output;
    }
}
