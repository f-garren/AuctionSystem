#!/bin/bash

# Auction Display System Setup Script for Ubuntu 24.04
# This script will install and configure the entire system

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    print_error "Please run as root (use sudo)"
    exit 1
fi

# Get the directory where the script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

print_status "Starting Auction Display System setup..."
print_status "Working directory: $SCRIPT_DIR"

# Update system packages
print_status "Updating system packages..."
apt-get update -y
apt-get upgrade -y

# Install required packages
print_status "Installing required packages..."
apt-get install -y \
    apache2 \
    mysql-server \
    php \
    php-cli \
    php-mysql \
    php-pdo \
    php-mbstring \
    php-xml \
    php-curl \
    php-gd \
    php-zip \
    libapache2-mod-php \
    unzip \
    curl

# Enable Apache modules
print_status "Enabling Apache modules..."
a2enmod rewrite
a2enmod php8.3 2>/dev/null || a2enmod php8.2 2>/dev/null || a2enmod php8.1 2>/dev/null || print_warning "Could not auto-detect PHP version, please enable manually"

# Start and enable services
print_status "Starting services..."
systemctl start apache2
systemctl enable apache2
systemctl start mysql
systemctl enable mysql

# Secure MySQL installation (non-interactive)
print_status "Configuring MySQL..."
# Check if MySQL root password is already set
MYSQL_ROOT_PASSWORD=""
if mysql -u root -e "SELECT 1" 2>/dev/null; then
    print_status "MySQL root access is available without password"
    # Generate a random root password if not set
    MYSQL_ROOT_PASSWORD=$(openssl rand -base64 32)
    print_warning "MySQL root password will be set to: $MYSQL_ROOT_PASSWORD"
    print_warning "Please save this password!"
    
    # Set MySQL root password
    mysql -u root <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASSWORD}';
FLUSH PRIVILEGES;
EOF
else
    print_warning "MySQL root password appears to be set."
    if [ -z "$MYSQL_ROOT_PASSWORD" ]; then
        print_warning "If you want to use a specific password, set MYSQL_ROOT_PASSWORD environment variable before running this script."
        print_warning "Attempting to proceed without root password (may fail if password is required)..."
    fi
fi

# Create database and user
print_status "Creating database and user..."
DB_NAME="auction_system"
DB_USER="auction_user"
DB_PASS=$(openssl rand -base64 24)

if [ -z "$MYSQL_ROOT_PASSWORD" ]; then
    mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF
else
    mysql -u root -p"${MYSQL_ROOT_PASSWORD}" <<EOF
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF
fi

print_status "Database created: ${DB_NAME}"
print_status "Database user created: ${DB_USER}"
print_warning "Database password: ${DB_PASS}"

# Create uploads directory
print_status "Creating uploads directory..."
mkdir -p "${SCRIPT_DIR}/uploads"
chown www-data:www-data "${SCRIPT_DIR}/uploads"
chmod 755 "${SCRIPT_DIR}/uploads"

# Configure config.php
print_status "Configuring config.php..."
if [ ! -f "${SCRIPT_DIR}/config.php" ]; then
    if [ -f "${SCRIPT_DIR}/config.example.php" ]; then
        cp "${SCRIPT_DIR}/config.example.php" "${SCRIPT_DIR}/config.php"
        print_status "Created config.php from example"
    else
        print_error "config.example.php not found!"
        exit 1
    fi
fi

# Update config.php with database credentials
sed -i "s/define('DB_HOST', '.*');/define('DB_HOST', 'localhost');/" "${SCRIPT_DIR}/config.php"
sed -i "s/define('DB_NAME', '.*');/define('DB_NAME', '${DB_NAME}');/" "${SCRIPT_DIR}/config.php"
sed -i "s/define('DB_USER', '.*');/define('DB_USER', '${DB_USER}');/" "${SCRIPT_DIR}/config.php"
sed -i "s/define('DB_PASS', '.*');/define('DB_PASS', '${DB_PASS}');/" "${SCRIPT_DIR}/config.php"

# Set proper permissions
print_status "Setting file permissions..."
chown -R www-data:www-data "${SCRIPT_DIR}"
find "${SCRIPT_DIR}" -type f -exec chmod 644 {} \;
find "${SCRIPT_DIR}" -type d -exec chmod 755 {} \;
chmod 755 "${SCRIPT_DIR}/setup.sh"
chmod 777 "${SCRIPT_DIR}/uploads"

# Create Apache virtual host (optional - for custom domain)
print_status "Checking Apache configuration..."

# Get server IP or hostname
SERVER_IP=$(hostname -I | awk '{print $1}')
SERVER_NAME=$(hostname)

# Check if we should create a virtual host
read -p "Do you want to create an Apache virtual host? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    read -p "Enter domain name or leave blank for IP access: " DOMAIN_NAME
    
    if [ -z "$DOMAIN_NAME" ]; then
        DOMAIN_NAME="$SERVER_IP"
    fi
    
    VHOST_FILE="/etc/apache2/sites-available/auction-system.conf"
    cat > "$VHOST_FILE" <<EOF
<VirtualHost *:80>
    ServerName ${DOMAIN_NAME}
    DocumentRoot ${SCRIPT_DIR}
    
    <Directory ${SCRIPT_DIR}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/auction-system-error.log
    CustomLog \${APACHE_LOG_DIR}/auction-system-access.log combined
</VirtualHost>
EOF
    
    a2ensite auction-system.conf
    a2dissite 000-default.conf 2>/dev/null || true
    systemctl reload apache2
    print_status "Virtual host created and enabled"
else
    # Just set document root to project directory if using default
    print_status "Using default Apache configuration"
    print_warning "You may need to configure Apache DocumentRoot manually"
fi

# Initialize database tables
print_status "Initializing database tables..."
cd "$SCRIPT_DIR"
php -r "
require_once 'config.php';
try {
    initDB();
    echo 'Database tables initialized successfully\n';
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

# Restart Apache
print_status "Restarting Apache..."
systemctl restart apache2

# Display summary
echo ""
echo "=========================================="
print_status "Setup completed successfully!"
echo "=========================================="
echo ""
echo "System Information:"
echo "  - Web Root: ${SCRIPT_DIR}"
echo "  - Database: ${DB_NAME}"
echo "  - Database User: ${DB_USER}"
echo "  - Database Password: ${DB_PASS}"
if [ -n "$MYSQL_ROOT_PASSWORD" ]; then
    echo "  - MySQL Root Password: ${MYSQL_ROOT_PASSWORD}"
else
    echo "  - MySQL Root Password: (not set or already configured)"
fi
echo ""
echo "Access the system:"
if [ -n "$DOMAIN_NAME" ] && [ "$DOMAIN_NAME" != "$SERVER_IP" ]; then
    echo "  http://${DOMAIN_NAME}"
else
    echo "  http://${SERVER_IP}"
    echo "  http://localhost"
fi
echo ""
print_warning "IMPORTANT: Save the database passwords shown above!"
print_warning "You can find them in this script's output or check config.php"
echo ""
print_status "The system is ready to use!"
echo ""

