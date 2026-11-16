-- Initial schema. Import into a new local database once.
-- This file deliberately does not drop existing tables.
CREATE DATABASE IF NOT EXISTS campus_lost_found
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campus_lost_found;

CREATE TABLE users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE item_reports (
    report_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(200) NOT NULL,
    event_date DATE NOT NULL,
    report_type ENUM('lost', 'found') NOT NULL,
    status ENUM('open', 'reserved', 'returned', 'resolved') NOT NULL DEFAULT 'open',
    photo_path VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_report_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE claims (
    claim_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id INT UNSIGNED NOT NULL,
    claimant_id INT UNSIGNED NOT NULL,
    identifying_detail TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'closed') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_claim_report FOREIGN KEY (report_id)
        REFERENCES item_reports(report_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_claim_user FOREIGN KEY (claimant_id)
        REFERENCES users(user_id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;
