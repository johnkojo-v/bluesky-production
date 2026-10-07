<?php

declare(strict_types=1);

namespace App\Services;

final class SecurityHardening
{
    public static function enforceProductionDefaults(): void
    {
        $env = getenv('APP_ENV') ?: 'production';
        if ($env !== 'production') {
            return;
        }

        $required = [
            'JWT_SECRET',
            'APP_URL',
            'DB_CONNECTION',
            'BLUESKY_PDS_URL',
        ];

        foreach ($required as $key) {
            $value = getenv($key) ?: '';
            if ($value === '' || $value === 'replace-this-secret') {
                throw new \RuntimeException('Missing or insecure production value: ' . $key);
            }
        }

        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            trigger_error('Production environment should be served over HTTPS.', E_USER_WARNING);
        }
    }
}
