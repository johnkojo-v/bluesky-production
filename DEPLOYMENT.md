# Bluesky Production Engine - Production Deployment Guide

## Architecture

This application is designed to run in a containerized production environment with:
- **PHP 8.1+** application server
- **PostgreSQL 15** database backend
- **Nginx** reverse proxy with TLS/HTTPS termination
- **Docker Compose** orchestration

## Deployment Steps

### 1. Prerequisites

- Docker and Docker Compose installed
- Valid SSL/TLS certificate (use Let's Encrypt for production)
- PostgreSQL credentials
- Strong JWT secret

### 2. Environment Configuration

Copy `.env.production` to `.env` and fill in production values:

```bash
cp .env.production .env
```

Update these critical values:
- `APP_URL`: Your production domain
- `DB_PASSWORD`: Strong database password
- `JWT_SECRET`: Cryptographically secure random secret (min 32 chars)
- `ADMIN_EMAIL`: Your admin email address

### 3. SSL/TLS Certificates

For production, use Let's Encrypt certificates:

```bash
mkdir -p certs
# Copy your fullchain.pem and privkey.pem to certs/
cp /path/to/fullchain.pem certs/
cp /path/to/privkey.pem certs/
chmod 600 certs/privkey.pem
```

### 4. Deploy

Run the deployment script:

```bash
chmod +x deploy.sh
./deploy.sh
```

Or manually start services:

```bash
docker-compose -f docker-compose-production.yml up -d
```

### 5. Verify Deployment

Check health status:

```bash
curl https://your-domain.com/health
```

Expected response:
```json
{"status":"ok","service":"bluesky-production","timestamp":"2026-10-07T22:57:00Z"}
```

## Security Hardening

### Network Security
- All traffic is HTTPS-only (HTTP redirects to HTTPS)
- TLS 1.2+ protocols enforced
- Strong cipher suites configured
- X-Frame-Options and other security headers via Nginx

### Application Security
- JWT-signed sessions
- Encrypted token storage for Bluesky auth
- Role-based access control (admin/editor/viewer)
- Password hashing with PHP's `PASSWORD_DEFAULT`
- SQL prepared statements throughout

### Database Security
- PostgreSQL password-protected
- No direct database exposure
- Database runs in isolated Docker network

### Environment Secrets
- All secrets in `.env` (never committed to Git)
- Production validation ensures required secrets are set
- Failed deployment if insecure defaults detected

## Monitoring and Health Checks

### Health Endpoint

The application exposes a health check endpoint at `/health`:

```bash
curl https://your-domain.com/health
```

### Docker Health Checks

Services include health checks:
- PHP: Waits for database connectivity
- PostgreSQL: Runs `pg_isready` every 10 seconds
- Nginx: Validates SSL configuration

### Logging

Access logs and error logs are available via Docker:

```bash
# PHP logs
docker-compose -f docker-compose-production.yml logs php

# Nginx logs
docker-compose -f docker-compose-production.yml logs nginx

# Database logs
docker-compose -f docker-compose-production.yml logs postgres
```

## Scaling and Updates

### Update Application Code

```bash
git pull origin main
docker-compose -f docker-compose-production.yml restart php
```

### Database Backups

```bash
docker-compose -f docker-compose-production.yml exec postgres pg_dump -U $DB_USERNAME bluesky_prod > backup.sql
```

### Restore Database

```bash
docker-compose -f docker-compose-production.yml exec -T postgres psql -U $DB_USERNAME bluesky_prod < backup.sql
```

## Troubleshooting

### Database Connection Issues

Verify database is running:
```bash
docker-compose -f docker-compose-production.yml ps postgres
```

### SSL/TLS Certificate Errors

Verify certificate paths in `nginx-production.conf`:
```bash
ls -la certs/
```

### Application Errors

Check PHP logs:
```bash
docker-compose -f docker-compose-production.yml logs php --tail=50
```

## Production Checklist

- [ ] Environment variables set in `.env`
- [ ] JWT_SECRET is strong and unique
- [ ] Database password is strong and random
- [ ] SSL/TLS certificates installed and valid
- [ ] Nginx HTTPS redirect is working
- [ ] Health check endpoint responds with 200 OK
- [ ] Database migrations completed successfully
- [ ] Bluesky authentication tested
- [ ] User roles and permissions verified
- [ ] Backup strategy implemented
- [ ] Monitoring and alerts configured

## Support and Maintenance

For production issues or feature requests, open an issue in the repository.
