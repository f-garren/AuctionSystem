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
    # Configure default Apache site to point to project directory
    print_status "Configuring default Apache site..."
    DEFAULT_SITE="/etc/apache2/sites-available/000-default.conf"
    if [ -f "$DEFAULT_SITE" ]; then
        # Backup original
        cp "$DEFAULT_SITE" "${DEFAULT_SITE}.backup"
        # Update DocumentRoot
        sed -i "s|DocumentRoot .*|DocumentRoot ${SCRIPT_DIR}|" "$DEFAULT_SITE"
        # Add directory configuration if not present
        if ! grep -q "<Directory ${SCRIPT_DIR}>" "$DEFAULT_SITE"; then
            sed -i "/<\/VirtualHost>/i\\
    <Directory ${SCRIPT_DIR}>\\
        Options -Indexes +FollowSymLinks\\
        AllowOverride All\\
        Require all granted\\
    </Directory>\\
" "$DEFAULT_SITE"
        fi
        systemctl reload apache2
        print_status "Default Apache site configured to use: ${SCRIPT_DIR}"
    else
        print_warning "Default Apache site configuration not found"
        print_warning "You may need to configure Apache DocumentRoot manually"
    fi
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

# Set proper permissions (must be done after all configuration)
print_status "Setting file permissions..."
chown -R www-data:www-data "${SCRIPT_DIR}"
find "${SCRIPT_DIR}" -type f -exec chmod 644 {} \;
find "${SCRIPT_DIR}" -type d -exec chmod 755 {} \;
chmod 755 "${SCRIPT_DIR}/setup.sh" 2>/dev/null || true
chmod 777 "${SCRIPT_DIR}/uploads"
# Ensure .git directory is accessible if it exists
if [ -d "${SCRIPT_DIR}/.git" ]; then
    chown -R www-data:www-data "${SCRIPT_DIR}/.git" 2>/dev/null || true
fi

# Restart Apache
print_status "Restarting Apache..."
systemctl restart apache2

# Save credentials to file
CREDENTIALS_FILE="${SCRIPT_DIR}/.credentials"
print_status "Saving credentials to ${CREDENTIALS_FILE}..."
cat > "$CREDENTIALS_FILE" <<EOF
# Auction Display System - Database Credentials
# Generated on: $(date)
# 
# IMPORTANT: Keep this file secure and do not commit it to version control!

# Database Configuration
DATABASE_NAME=${DB_NAME}
DATABASE_USER=${DB_USER}
DATABASE_PASSWORD=${DB_PASS}

# MySQL Root Password
EOF

if [ -n "$MYSQL_ROOT_PASSWORD" ]; then
    echo "MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}" >> "$CREDENTIALS_FILE"
else
    echo "MYSQL_ROOT_PASSWORD=(not set or already configured)" >> "$CREDENTIALS_FILE"
fi

cat >> "$CREDENTIALS_FILE" <<EOF

# System Information
WEB_ROOT=${SCRIPT_DIR}
SERVER_IP=${SERVER_IP}
EOF

if [ -n "$DOMAIN_NAME" ] && [ "$DOMAIN_NAME" != "$SERVER_IP" ]; then
    echo "DOMAIN_NAME=${DOMAIN_NAME}" >> "$CREDENTIALS_FILE"
    echo "ACCESS_URL=http://${DOMAIN_NAME}" >> "$CREDENTIALS_FILE"
else
    echo "ACCESS_URL=http://${SERVER_IP} or http://localhost" >> "$CREDENTIALS_FILE"
fi

# Set secure permissions on credentials file
chmod 600 "$CREDENTIALS_FILE"
chown root:root "$CREDENTIALS_FILE" 2>/dev/null || chown $SUDO_USER:$SUDO_USER "$CREDENTIALS_FILE" 2>/dev/null || true

# Final permissions check and fix (exclude credentials file and .git)
print_status "Performing final permissions check..."
chown -R www-data:www-data "${SCRIPT_DIR}"
# Exclude credentials file and .git from permission changes
find "${SCRIPT_DIR}" -type f ! -path "*/.git/*" ! -name ".credentials" -exec chmod 644 {} \;
find "${SCRIPT_DIR}" -type d ! -path "*/.git/*" -exec chmod 755 {} \;
chmod 777 "${SCRIPT_DIR}/uploads"
# Keep setup script executable for owner
chmod 755 "${SCRIPT_DIR}/setup.sh" 2>/dev/null || true
# Restore credentials file permissions
chmod 600 "$CREDENTIALS_FILE" 2>/dev/null || true
chown root:root "$CREDENTIALS_FILE" 2>/dev/null || chown $SUDO_USER:$SUDO_USER "$CREDENTIALS_FILE" 2>/dev/null || true

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
print_status "Credentials have been saved to: ${CREDENTIALS_FILE}"
print_warning "IMPORTANT: This file contains sensitive information. Keep it secure!"
echo ""
print_status "The system is ready to use!"
echo ""

