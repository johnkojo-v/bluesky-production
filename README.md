# Bluesky Production Engine

A production-focused PHP application built from the mock Bluesky engine.

## Current status

This project has moved beyond the mock-state into a structured production foundation with:
- route-based request handling
- database-backed user and campaign records
- queued job processing model
- session management scaffolding
- worker queue simulation for background processing
- environment validation checks

## Features now included

- login / register API
- campaign creation and listing
- job queue creation and processing
- dashboard view for recent campaigns and jobs
- environment validation and session lifecycle support

## Local run

```bash
php -S localhost:8000 -t public
```

Then navigate to:
- `http://localhost:8000/`
- `http://localhost:8000/health`

## Production roadmap

The remaining production work is straightforward:
1. replace simulated job processing with real BlueSky session tokens
2. add secure role-based permissions for admin/users
3. move from SQLite to PostgreSQL or MySQL in production
4. add HTTPS, rate limiting, and logging
5. implement real scheduled workers and monitoring

## Security notes

- keep secrets in `.env`
- never commit production credentials
- use TLS in deployment
- store tokens securely, not in plaintext
