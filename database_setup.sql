-- Auction Display System Database Setup
-- Run this script to create the database and tables manually
-- Or let the system create them automatically on first access

CREATE DATABASE IF NOT EXISTS auction_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE auction_system;

-- Items table
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Current display table (stores which item is currently being displayed)
CREATE TABLE IF NOT EXISTS current_display (
    id INT PRIMARY KEY,
    item_id INT NULL,
    auction_ended BOOLEAN DEFAULT FALSE,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add auction_ended column if it doesn't exist (for existing installations)
ALTER TABLE current_display ADD COLUMN IF NOT EXISTS auction_ended BOOLEAN DEFAULT FALSE;

-- Insert default row for current_display
INSERT INTO current_display (id, item_id) VALUES (1, NULL)
ON DUPLICATE KEY UPDATE id=id;

