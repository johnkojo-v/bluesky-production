<?php

declare(strict_types=1);

namespace App;

final class Config
{
    private static ?array $config = null;

    public static function load(string $basePath): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        $envFile = $basePath . '/.env';
        if (is_file($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) {
                    continue;
                }

                [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
                $_ENV[trim($key)] = trim($value, " \n\r\t\v\0\"");
            }
        }

        self::$config = [
            'app_name' => getenv('APP_NAME') ?: 'Bluesky Production Engine',
            'app_env' => getenv('APP_ENV') ?: 'production',
            'app_debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN),
            'app_url' => getenv('APP_URL') ?: 'http://localhost',
            'db_connection' => getenv('DB_CONNECTION') ?: 'sqlite',
            'db_path' => getenv('DB_PATH') ?: $basePath . '/storage/app.sqlite',
            'bluesky_enabled' => filter_var(getenv('BLUESKY_ENABLED') ?: true, FILTER_VALIDATE_BOOLEAN),
            'bluesky_pds_url' => getenv('BLUESKY_PDS_URL') ?: 'https://bsky.social',
            'bluesky_api_url' => getenv('BLUESKY_API_URL') ?: 'https://bsky.social',
            'jwt_secret' => getenv('JWT_SECRET') ?: 'replace-this-secret',
            'session_lifetime' => (int) (getenv('SESSION_LIFETIME') ?: 3600),
        ];

        return self::$config;
    }
}
