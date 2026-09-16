# MarketFlow Deployment on torob.adlr.ir

## Overview
MarketFlow is deployed at `https://torob.adlr.ir` using the nginx-certbot infrastructure.

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     ArvanCloud CDN                          │
│                   (torob.adlr.ir)                          │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼ HTTPS :443
┌─────────────────────────────────────────────────────────────┐
│                   nginx-certbot (nginx)                    │
│                   Port 80/443 mapped                        │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼ Proxy to :8000
┌─────────────────────────────────────────────────────────────┐
│               marketflow_backend (PHP 8.3)                 │
│              Laravel 13 + MarketplaceProvider               │
│                    Port 8000                                │
└─────────────────────────────────────────────────────────────┘
                              │
                    ┌─────────┴─────────┐
                    ▼                   ▼
           ┌──────────────┐    ┌──────────────┐
           │  PostgreSQL  │    │    Redis     │
           │   (db)       │    │   (redis)   │
           └──────────────┘    └──────────────┘
```

## Services

| Service | Container | Port | Purpose |
|---------|-----------|------|---------|
| Backend | marketflow_backend | 8000 | Laravel API & TorobProcessor |
| Database | marketflow_db | 5432 | PostgreSQL for persistence |
| Redis | marketflow_redis | 6379 | Cache & price history |
| Nginx | nginx-certbot | 80/443 | Reverse proxy & SSL |

## Configuration Files

- **Nginx Config**: `/root/nginx-certbot/data/nginx/sub/sub.torob.adlr.ir.conf`
- **Docker Compose**: `/root/nginx-certbot/websites/marketflow/docker-compose.yml`
- **Backend Dockerfile**: `/root/nginx-certbot/websites/marketflow/backend/Dockerfile`

## Deployment Commands

### Start Services
```bash
cd /root/nginx-certbot/websites/marketflow
docker compose up -d db redis
```

### Run Backend
```bash
docker run -d \
  --name marketflow_backend \
  --network sail \
  -v /root/nginx-certbot/websites/marketflow/backend:/var/www/html \
  -p 8001:8000 \
  php:8.3-fpm-alpine \
  php artisan serve --host=0.0.0.0 --port=8000
```

### Run Tests
```bash
docker exec marketflow_backend php artisan test
```

### Restart Backend
```bash
docker restart marketflow_backend
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /up | Health check |
| POST | /api/register | User registration |
| POST | /api/login | User login |
| POST | /api/logout | User logout |
| GET | /api/user | Get authenticated user |

## SSL Certificate

Currently using a self-signed certificate for testing. To obtain a proper SSL certificate:

1. Configure ArvanCloud to allow HTTP ACME challenges
2. Or use DNS-01 challenge with certbot
3. Or disable CDN temporarily for the domain

```bash
# Generate Let's Encrypt certificate
docker run --rm \
  -v /root/nginx-certbot/data/certbot/conf:/etc/letsencrypt \
  -v /root/nginx-certbot/data/certbot/www:/var/www/certbot \
  certbot/certbot certonly --webroot \
  -w /var/www/certbot \
  -d torob.adlr.ir
```

## Network Configuration

The marketflow_backend is connected to the `sail` network to communicate with nginx-certbot:

```yaml
networks:
  - sail  # External network for nginx communication
  - default  # Internal network for db/redis
```

## Troubleshooting

### Check backend logs
```bash
docker logs marketflow_backend
```

### Test connectivity from nginx
```bash
docker exec nginx-certbot-nginx-1 wget -qO- http://marketflow_backend:8000/up
```

### Check nginx logs
```bash
docker exec nginx-certbot-nginx-1 tail -f /var/log/nginx/access.log
```

### Reload nginx configuration
```bash
docker exec nginx-certbot-nginx-1 nginx -s reload
```

## Environment Variables

Required in `/root/nginx-certbot/websites/marketflow/backend/.env`:
- `DB_HOST` - PostgreSQL host
- `DB_PORT` - PostgreSQL port
- `DB_NAME` - Database name
- `DB_USER` - Database user
- `DB_PASSWORD` - Database password
- `REDIS_HOST` - Redis host
- `REDIS_PORT` - Redis port
- `PUSHER_APP_ID` - Pusher for WebSockets
- `PUSHER_APP_KEY` - Pusher key
- `PUSHER_APP_SECRET` - Pusher secret

## Test Coverage

All tests pass (15 total):
- 2 Example tests
- 5 TorobProcessor tests
- 8 BDD workflow tests

```bash
docker exec marketflow_backend php artisan test
```

## Health Check

```bash
curl -sk https://torob.adlr.ir/up
```
