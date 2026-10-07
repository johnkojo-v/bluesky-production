<?php

declare(strict_types=1);

namespace App\Services;

final class EnvironmentValidator
{
    public static function validate(array $config): array
    {
        $issues = [];

        if (($config['bluesky_enabled'] ?? false) === true) {
            $required = ['BLUESKY_PDS_URL', 'BLUESKY_API_URL'];
            foreach ($required as $key) {
                $value = getenv($key) ?: '';
                if ($value === '') {
                    $issues[] = $key . ' is required when Bluesky is enabled.';
                }
            }
        }

        if (!is_dir(__DIR__ . '/../../storage')) {
            $issues[] = 'Storage directory is missing. Please create /storage before deployment.';
        }

        return [
            'ok' => empty($issues),
            'issues' => $issues,
        ];
    }
}
