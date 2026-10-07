# Bluesky Production Engine

A production-focused PHP application built from the existing mock Bluesky engine.

## Current status

This project is being upgraded from a local demo into a real deployment-ready product foundation.

The current app includes:
- secure environment-based configuration
- SQLite/PostgreSQL-ready database layer
- health endpoint
- basic campaign flow
- extensible structure for real Bluesky API integration

## Repo privacy

To make the repository private:
1. Open the repo on GitHub
2. Go to Settings
3. Open "General"
4. Change "Repository visibility" to "Private"

## Requirements

- PHP 8.2+
- Web server with document root pointed to `public/`
- SQLite or PostgreSQL for persistence

## Local development

```bash
php -S localhost:8000 -t public
```

Then visit:
- `http://localhost:8000/`
- `http://localhost:8000/health`

## Production notes

- Keep secrets in `.env`
- Never commit live credentials
- Add a queue worker for publishing workflows
- Use TLS/HTTPS in production
- Replace mock actions with real Bluesky API calls
