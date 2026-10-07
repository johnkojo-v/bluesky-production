<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class TokenEncryption
{
    public static function encrypt(string $value, string $key): string
    {
        $iv = random_bytes(16);
        $cipher = 'aes-256-cbc';
        $encrypted = openssl_encrypt($value, $cipher, hash('sha256', $key, true), 0, $iv);
        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed.');
        }

        return bin2hex($iv) . ':' . bin2hex((string) $encrypted);
    }

    public static function decrypt(string $payload, string $key): string
    {
        [$ivHex, $encHex] = array_pad(explode(':', $payload, 2), 2, '');
        $iv = hex2bin($ivHex);
        $enc = hex2bin($encHex);

        if ($iv === false || $enc === false) {
            throw new \RuntimeException('Invalid encrypted payload.');
        }

        $decrypted = openssl_decrypt($enc, 'aes-256-cbc', hash('sha256', $key, true), 0, $iv);
        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed.');
        }

        return $decrypted;
    }
}
