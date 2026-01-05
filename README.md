# Auction Display System

A web-based auction display system built with PHP that allows managers to control what is displayed on multiple client screens.

## Features

- **Manager Interface**: Create, edit, and manage auction items
- **Client Display**: Passive displays that automatically update when items are selected
- **Real-time Updates**: Clients poll the server every 2 seconds for updates
- **Image Management**: Upload and manage item images
- **MySQL Database**: Robust database management system

## Requirements

- PHP 7.0 or higher
- MySQL 5.7 or higher (or MariaDB 10.2+)
- PDO MySQL extension
- Web server (Apache, Nginx, or PHP built-in server)

## Installation

### Automated Setup (Ubuntu 24.04)

The easiest way to install the system is using the provided setup script:

```bash
# Clone the repository (if not already done)
git clone <repository-url>
cd AuctionSystem

# Run the setup script with sudo
sudo ./setup.sh
```

The setup script will:
- Update system packages
- Install Apache, MySQL, PHP and required extensions
- Create and configure the database
- Set up proper file permissions
- Configure Apache virtual host (optional)
- Initialize database tables
- Display all credentials and access information

**Note:** The script will generate random passwords for MySQL. Make sure to save them when displayed!

### Manual Installation

1. **Install Required Packages**
   ```bash
   sudo apt-get update
   sudo apt-get install -y apache2 mysql-server php php-mysql php-pdo libapache2-mod-php
   ```

2. **Configure Database Connection**
   - Edit `config.php` and update the MySQL connection settings:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'auction_system');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     ```

3. **Create MySQL Database**
   - Create the database:
     ```sql
     CREATE DATABASE auction_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     ```
   - Or use the provided `database_setup.sql` file:
     ```bash
     mysql -u your_username -p < database_setup.sql
     ```

4. **Set Permissions**
   - Ensure the `uploads/` directory is writable (will be created automatically)
   - Make sure the web server has read/write permissions

5. **Access the System**
   - Access `index.php` in your web browser
   - The database tables will be created automatically on first access

## Usage

### Manager Mode

1. Click "Manager" on the home page
2. Create items by clicking "Create New Item"
3. Upload an image and enter a name for each item
4. Click "Display" on any item to show it on all client screens
5. Edit or delete items as needed

### Client Mode

1. Click "Client Display" on the home page
2. The screen will automatically display the currently selected item
3. Updates automatically every 2 seconds
4. Open multiple browser windows/tabs to simulate multiple displays

## File Structure

- `index.php` - Home page with mode selection
- `manager.php` - Manager control panel
- `client.php` - Client display screen
- `api.php` - API endpoints for all operations
- `config.php` - Database configuration and initialization
- `config.example.php` - Example configuration file
- `database_setup.sql` - Optional SQL script to create database
- `setup.sh` - Automated setup script for Ubuntu 24.04
- `uploads/` - Directory for uploaded images

## API Endpoints

- `GET api.php?action=get_items` - Get all items
- `GET api.php?action=get_current_display` - Get currently displayed item
- `POST api.php` (action=set_current_display) - Set displayed item
- `POST api.php` (action=create_item) - Create new item
- `POST api.php` (action=update_item) - Update existing item
- `POST api.php` (action=delete_item) - Delete item

## Notes

- The system uses MySQL for robust data management
- Images are stored in the `uploads/` directory
- Clients poll every 2 seconds for updates (configurable in client.php)
- The system is designed to work on a local network
- Database tables are automatically created on first access if they don't exist

