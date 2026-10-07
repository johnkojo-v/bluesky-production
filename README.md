# Bluesky Production Engine

A production-ready PHP application that upgrades the mock educational simulation into a real, secure, and deployable service.

## What this project includes

- secure configuration via `.env`
- database-backed persistence using PDO
- Bluesky API integration scaffold
- health check endpoint
- basic auth flow and campaign endpoints
- deployment-friendly Docker and Nginx setup

## Requirements

- PHP 8.2+
- Composer
- PostgreSQL or SQLite for local development
- cURL enabled

## Quick setup

1. Copy `.env.example` to `.env`
2. Update your environment variables
3. Create the database schema from `database/schema.sql`
4. Install Composer dependencies:

```bash
composer install
```

5. Start the app from the `public/` directory with your web server pointing to it

## Production notes

- Never commit `.env` values
- Use environment variables for secrets and credentials
- Enable HTTPS in production
- Use a real DB instead of JSON storage
- Add a queue worker for background posting / promotion tasks

## Local health check

```bash
curl http://localhost/health
```
