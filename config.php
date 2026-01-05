<?php
// MySQL Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'auction_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Initialize database connection
function getDB() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $db = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $db;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Initialize database tables
function initDB() {
    $db = getDB();
    
    // Items table
    $db->exec("CREATE TABLE IF NOT EXISTS items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        image_path VARCHAR(500) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Current display table (stores which item is currently being displayed)
    $db->exec("CREATE TABLE IF NOT EXISTS current_display (
        id INT PRIMARY KEY,
        item_id INT NULL,
        auction_ended BOOLEAN DEFAULT FALSE,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Add auction_ended column if it doesn't exist (for existing installations)
    try {
        $db->exec("ALTER TABLE current_display ADD COLUMN auction_ended BOOLEAN DEFAULT FALSE");
    } catch (PDOException $e) {
        // Column already exists, ignore error
    }
    
    // Insert default row if it doesn't exist
    $stmt = $db->query("SELECT COUNT(*) FROM current_display");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO current_display (id, item_id) VALUES (1, NULL)");
    }
}
?>

