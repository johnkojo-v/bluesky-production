#!/bin/bash

set -e

echo "Setting up Bluesky Production Engine deployment..."

# Check required environment variables
required_vars=("DB_USERNAME" "DB_PASSWORD" "JWT_SECRET" "ADMIN_EMAIL")
for var in "${required_vars[@]}"; do
    if [ -z "${!var}" ]; then
        echo "Error: $var is not set"
        exit 1
    fi
done

echo "Creating storage directory..."
mkdir -p storage
chmod 755 storage

echo "Generating self-signed certificate (use Let's Encrypt for production)..."
mkdir -p certs
if [ ! -f "certs/fullchain.pem" ]; then
    openssl req -x509 -newkey rsa:4096 -keyout certs/privkey.pem -out certs/fullchain.pem -days 365 -nodes -subj "/CN=localhost"
    echo "Certificate generated at certs/fullchain.pem"
fi

echo "Starting Docker Compose services..."
docker-compose -f docker-compose-production.yml up -d

echo "Waiting for database to be ready..."
sleep 10

echo "Running database migrations..."
docker-compose -f docker-compose-production.yml exec -T php php -r "
require_once '/var/www/app/Config.php';
require_once '/var/www/app/Database.php';
require_once '/var/www/app/DatabaseMigrator.php';
require_once '/var/www/app/Bootstrap.php';
require_once '/var/www/app/Bootstrap/SchemaBoot.php';

use App\\Config;
use App\\Database;
use App\\DatabaseMigrator;
use App\\Bootstrap;
use App\\Bootstrap\\SchemaBoot;

\$config = Config::load('/var/www');
\$db = new Database(\$config);
\$pdo = \$db->connection();
DatabaseMigrator::run(\$pdo);
Bootstrap::ensureSchema(\$pdo);
SchemaBoot::ensureRoleSchema(\$pdo);

echo 'Database migrations completed.';
" || true

echo "Deployment complete!"
echo "Application is running at https://localhost"
echo "Health check: curl https://localhost/health"
