# Docker deployment — ArdhiLens Backend

Run the Laravel API in Docker on a VM. Containers **start when the VM boots** and **stop when the VM shuts down**.

## What runs

| Service | Role |
|---------|------|
| `app` | Nginx + PHP-FPM (Laravel API) on port 80 |
| `mysql` | MySQL 8 database |

Both use `restart: unless-stopped` so they come back after a reboot.

## Quick start (VM)

### 1. Install Docker on Ubuntu

```bash
sudo apt update
sudo apt install -y docker.io docker-compose-plugin git
sudo systemctl enable docker
sudo systemctl start docker
sudo usermod -aG docker $USER
# Log out and back in so docker group applies
```

### 2. Clone and configure

```bash
sudo mkdir -p /opt/ardhilens
sudo chown $USER:$USER /opt/ardhilens
cd /opt/ardhilens
git clone https://github.com/TINAH-SHIO03/ArdhiLens-backend.git
cd ArdhiLens-backend
cp .env.docker.example .env
nano .env   # set APP_URL, DB_PASSWORD, GEMINI_API_KEY, mail settings
```

### 3. Build and start

```bash
docker compose build
docker compose up -d
```

API will be available at `http://YOUR_VM_IP` (port 80).

### 4. Seed demo data (optional)

```bash
docker compose exec app php artisan db:seed --class=LandDemoSeeder
```

## Auto-start on VM boot / stop on shutdown

### Option A — Docker restart policy (default)

`docker-compose.yml` sets `restart: unless-stopped`. After you run `docker compose up -d` once:

- **VM on** → Docker starts → containers start automatically  
- **VM off** → containers stop  

Ensure Docker starts on boot:

```bash
sudo systemctl enable docker
```

### Option B — Systemd service (recommended for production)

```bash
sudo cp docker/systemd/ardhilens-backend.service /etc/systemd/system/
# Edit WorkingDirectory if you did not use /opt/ardhilens/ArdhiLens-backend
sudo nano /etc/systemd/system/ardhilens-backend.service

sudo systemctl daemon-reload
sudo systemctl enable ardhilens-backend
sudo systemctl start ardhilens-backend
```

Check status:

```bash
sudo systemctl status ardhilens-backend
docker compose ps
```

## Connect the Flutter APK

Point the app to your VM URL in `app_config.dart` or build with:

```bash
flutter build apk --release --dart-define=API_BASE_URL=http://YOUR_VM_IP
```

Use HTTPS in production (reverse proxy + Let's Encrypt in front of port 80).

## Useful commands

```bash
# View logs
docker compose logs -f app

# Run migrations manually
docker compose exec app php artisan migrate --force

# Restart after code update
git pull
docker compose build --no-cache app
docker compose up -d

# Stop everything
docker compose down

# Stop but keep database data
docker compose stop
```

## Firewall

```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp   # if using HTTPS proxy
sudo ufw enable
```

## Data persistence

- **MySQL data** → Docker volume `mysql_data`
- **Uploads, certificates, logs** → Docker volume `app_storage`

These survive container restarts and `docker compose down` (without `-v`).

## Troubleshooting

| Problem | Fix |
|---------|-----|
| App can't reach DB | Check `.env` `DB_*` matches `docker-compose.yml` mysql env |
| 502 / blank page | `docker compose logs app` |
| Permission errors | `docker compose exec app chown -R www-data:www-data storage bootstrap/cache` |
| Port 80 in use | Set `APP_PORT=8080` in `.env` and open firewall for 8080 |
