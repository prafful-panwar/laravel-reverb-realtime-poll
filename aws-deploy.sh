#!/bin/bash
set -e

APP_DIR="/var/www/realtime-poll"
REPO_URL="https://github.com/prafful-panwar/laravel-reverb-realtime-poll.git"

DB_NAME="poll_db"
DB_USER="poll_user"
# Generated per-deploy; save the output shown at end.
DB_PASS="$(openssl rand -hex 16)"

PUBLIC_IP=$(curl -s ifconfig.me)

echo "Server IP: $PUBLIC_IP"

# --------------------------------------------------
# 1. Update System
# --------------------------------------------------
sudo apt update -y
sudo apt upgrade -y

# --------------------------------------------------
# 2. Install Core Packages
# --------------------------------------------------
sudo apt install -y nginx mysql-server redis-server supervisor git unzip curl software-properties-common

sudo systemctl enable nginx
sudo systemctl enable mysql
sudo systemctl enable redis-server
sudo systemctl start redis-server

# --------------------------------------------------
# 3. Install PHP 8.4
# --------------------------------------------------
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update -y

sudo apt install -y php8.4 php8.4-fpm php8.4-mysql php8.4-redis \
php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip

sudo update-alternatives --set php /usr/bin/php8.4

php -v

# --------------------------------------------------
# 4. Install Composer
# --------------------------------------------------
if ! command -v composer &> /dev/null; then
  curl -sS https://getcomposer.org/installer | php
  sudo mv composer.phar /usr/local/bin/composer
fi

# --------------------------------------------------
# 5. Install Node 20
# --------------------------------------------------
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# --------------------------------------------------
# 6. Configure MySQL
# --------------------------------------------------
sudo mysql <<MYSQL_SCRIPT
CREATE DATABASE IF NOT EXISTS ${DB_NAME};
DROP USER IF EXISTS '${DB_USER}'@'localhost';
CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
MYSQL_SCRIPT

# --------------------------------------------------
# 7. Clone Project
# --------------------------------------------------
sudo mkdir -p /var/www
sudo chown -R ubuntu:ubuntu /var/www

cd /var/www
git clone $REPO_URL realtime-poll
cd $APP_DIR

git config --global --add safe.directory $APP_DIR

# --------------------------------------------------
# 8. Install Backend
# --------------------------------------------------
composer install --no-dev --optimize-autoloader

# --------------------------------------------------
# 9. Create .env
# --------------------------------------------------
REVERB_KEY=$(openssl rand -hex 16)
REVERB_SECRET=$(openssl rand -hex 32)

cat > .env <<EOF
APP_NAME="Real-Time Polls"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://${PUBLIC_IP}

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis

HORIZON_PREFIX=poll_horizon:

REVERB_APP_ID=1
REVERB_APP_KEY=${REVERB_KEY}
REVERB_APP_SECRET=${REVERB_SECRET}
REVERB_HOST=${PUBLIC_IP}
REVERB_PORT=80
REVERB_SCHEME=http
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=6001

VITE_REVERB_APP_KEY=${REVERB_KEY}
VITE_REVERB_HOST=${PUBLIC_IP}
VITE_REVERB_PORT=80
VITE_REVERB_SCHEME=http
EOF

php artisan key:generate

# --------------------------------------------------
# 10. Frontend
# --------------------------------------------------
npm install
npm run build

# --------------------------------------------------
# 11. Permissions
# --------------------------------------------------
sudo chown -R www-data:www-data $APP_DIR
sudo chmod -R 775 storage bootstrap/cache

# --------------------------------------------------
# 12. Migrate
# --------------------------------------------------
sudo -u www-data php artisan migrate --force

# Production caches
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# --------------------------------------------------
# 13. Nginx Config
# --------------------------------------------------
sudo tee /etc/nginx/sites-available/default > /dev/null <<EOL
server {
    listen 80;
    server_name _;

    root $APP_DIR/public;
    index index.php;

    # WebSockets (Laravel Reverb) - keep 6001 private, proxy via Nginx.
    location /app/ {
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_pass http://127.0.0.1:6001;
    }

    location /apps/ {
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_pass http://127.0.0.1:6001;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }
}
EOL

sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx

# --------------------------------------------------
# 14. Supervisor
# --------------------------------------------------
sudo tee /etc/supervisor/conf.d/horizon.conf > /dev/null <<EOL
[program:horizon]
command=php $APP_DIR/artisan horizon
directory=$APP_DIR
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=$APP_DIR/storage/logs/horizon.log
EOL

sudo tee /etc/supervisor/conf.d/reverb.conf > /dev/null <<EOL
[program:reverb]
command=php $APP_DIR/artisan reverb:start --host=127.0.0.1 --port=6001
directory=$APP_DIR
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=$APP_DIR/storage/logs/reverb.log
EOL

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all

# --------------------------------------------------
# Done
# --------------------------------------------------
echo "====================================="
echo "Deployment Finished"
echo "Keep port 6001 PRIVATE (use Nginx on 80/443)"
echo "MySQL user: ${DB_USER}"
echo "MySQL password: ${DB_PASS}"
echo "Visit: http://${PUBLIC_IP}"
echo "====================================="
