# Local Development Setup Guide

Step-by-step guide to run solidtime locally on macOS.

## Prerequisites

- **Docker** runtime (via [Colima](https://github.com/abapGit/colima), Docker Desktop, or OrbStack)
- **Composer** (PHP package manager)
- **Node.js** 20+ and npm
- **Git**

## 1. Clone and configure

```bash
git clone https://github.com/solidtime-io/solidtime.git
cd solidtime
cp .env.example .env
```

Add your user/group IDs to `.env` so the Sail container runs with correct permissions:

```bash
echo "" >> .env
echo "# Sail user/group" >> .env
echo "WWWUSER=$(id -u)" >> .env
echo "WWWGROUP=$(id -g)" >> .env
```

## 2. Start your Docker runtime

If using Colima:

```bash
colima start
```

## 3. Create the Traefik network

solidtime's Docker setup expects a Traefik reverse proxy network. Create it:

```bash
docker network create reverse-proxy-docker-traefik_routing
```

## 4. Set up Traefik

Create a directory for Traefik anywhere outside the project:

```bash
mkdir -p ~/traefik
```

Create `~/traefik/docker-compose.yml`:

```yaml
services:
  traefik:
    image: traefik:v3
    command:
      - "--api.insecure=true"
      - "--providers.docker=true"
      - "--providers.docker.exposedbydefault=false"
      - "--entrypoints.web.address=:80"
      - "--entrypoints.websecure.address=:443"
    ports:
      - "80:80"
      - "443:443"
      - "8080:8080"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
    networks:
      - routing
    restart: unless-stopped

networks:
  routing:
    name: reverse-proxy-docker-traefik_routing
    external: true
```

Start Traefik:

```bash
docker compose -f ~/traefik/docker-compose.yml up -d
```

## 5. Add hosts entries

```bash
echo '127.0.0.1 solidtime.test vite.solidtime.test mail.solidtime.test storage.solidtime.test storage-management.solidtime.test playwright.solidtime.test' | sudo tee -a /etc/hosts
```

This maps the local domains to your machine. You'll be prompted for your password.

## 6. Build the Laravel Sail Docker image

The PHP container image needs to be built locally:

```bash
WWWGROUP=$(id -g) WWWUSER=$(id -u) docker compose build laravel.test
```

This takes a few minutes the first time.

## 7. Start all services

```bash
docker compose up -d
```

This starts:

| Service | Domain / Port | Purpose |
|---------|---------------|---------|
| laravel.test | `solidtime.test` | Laravel app |
| pgsql | port 54329 | Main PostgreSQL database |
| pgsql_test | internal | Test database |
| mailpit | `mail.solidtime.test` | Email catcher |
| minio | `storage.solidtime.test` | S3-compatible file storage |
| gotenberg | internal | PDF generation |

Verify everything is running:

```bash
docker compose ps
```

Wait until `pgsql` shows `(healthy)` before proceeding.

## 8. Install dependencies

```bash
docker compose exec laravel.test composer install
docker compose exec laravel.test npm install
```

## 9. Initialize the application

```bash
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan passport:keys
docker compose exec laravel.test php artisan migrate --seed
```

## 10. Build frontend assets

The app requires a production build to generate the Vite manifest (`public/build/manifest.json`). Without it, the page loads blank because the `@vite` Blade directive cannot resolve entry points.

```bash
docker compose exec laravel.test npm run build
```

## 11. Start the Vite dev server

For hot-reload during development:

```bash
docker compose exec laravel.test npm run dev
```

Keep this running in a terminal tab.

## 12. Open the app

Visit **http://solidtime.test** in your browser.

Click **Register** (or go to http://solidtime.test/register) to create an account. The account is stored locally in the PostgreSQL container.

## Stopping and restarting

```bash
# Stop everything
docker compose down

# Stop Traefik
docker compose -f ~/traefik/docker-compose.yml down

# Stop Colima (if using)
colima stop
```

To restart later, you only need:

```bash
colima start                    # if using Colima
docker compose -f ~/traefik/docker-compose.yml up -d
docker compose up -d
docker compose exec laravel.test npm run dev
```

No need to re-run install, migrate, or build — data persists in Docker volumes.

## Troubleshooting

### 502 Bad Gateway

The Laravel container's PHP process may have crashed. Restart it:

```bash
docker compose restart laravel.test
```

### Blank page (no CSS/JS)

The Vite manifest is missing. Run the build:

```bash
docker compose exec laravel.test npm run build
```

Then hard-refresh the browser (Cmd+Shift+R).

### WWWGROUP/WWWUSER warnings

Add these to your `.env`:

```
WWWUSER=<your user id>
WWWGROUP=<your group id>
```

Find your IDs with `id -u` and `id -g`.

### Database connection errors

Wait for PostgreSQL to be healthy:

```bash
docker compose ps
```

If `pgsql` isn't healthy, restart it:

```bash
docker compose restart pgsql
```

### Docker Compose v5: `extra_hosts must be a mapping`

If you see this error, your Docker Compose version requires mapping syntax for `extra_hosts`. Convert from:

```yaml
extra_hosts:
    - "host.docker.internal:host-gateway"
```

To:

```yaml
extra_hosts:
    host.docker.internal: host-gateway
```

Apply this to all `extra_hosts` sections in `docker-compose.yml`.
