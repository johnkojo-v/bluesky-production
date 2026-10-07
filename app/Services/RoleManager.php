<?php

declare(strict_types=1);

namespace App;

use PDO;

final class DatabaseMigrator
{
    public static function run(PDO $pdo): void
    {
        $driver = strtolower($pdo->getAttribute(PDO::ATTR_DRIVER_NAME));

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'user',
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            );"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS campaigns (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                topic TEXT,
                status TEXT NOT NULL DEFAULT 'draft',
                metrics TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                campaign_id INTEGER NOT NULL,
                action TEXT NOT NULL,
                payload TEXT,
                status TEXT NOT NULL DEFAULT 'queued',
                result TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (campaign_id) REFERENCES campaigns(id)
            );"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                event TEXT NOT NULL,
                details TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            );"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            );"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS user_roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                role_id INTEGER NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (role_id) REFERENCES roles(id)
            );"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            );"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS role_permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                role_id INTEGER NOT NULL,
                permission_id INTEGER NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (role_id) REFERENCES roles(id),
                FOREIGN KEY (permission_id) REFERENCES permissions(id)
            );"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS provider_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                provider TEXT NOT NULL,
                access_token TEXT NOT NULL,
                refresh_token TEXT NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );"
        );

        $counts = $pdo->query("SELECT COUNT(*) as total FROM roles")->fetch();
        if ((int) ($counts['total'] ?? 0) === 0) {
            $pdo->exec("INSERT INTO roles (name) VALUES ('admin'), ('editor'), ('viewer');");
        }

        if ($driver === 'pgsql' || $driver === 'mysql') {
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_campaigns_user_id ON campaigns(user_id);');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_jobs_campaign_id ON jobs(campaign_id);');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_user_roles_user_id ON user_roles(user_id);');
        }
    }
}
