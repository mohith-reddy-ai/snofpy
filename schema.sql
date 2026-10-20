-- SpoofShield Database Schema

CREATE DATABASE IF NOT EXISTS spoofshield;
USE spoofshield;

-- Investigations table
CREATE TABLE IF NOT EXISTS investigations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('dns', 'email') NOT NULL,
    target VARCHAR(255) NOT NULL, -- Domain name or Email Subject/Sender
    risk_score INT DEFAULT 0,
    severity ENUM('Low', 'Medium', 'High', 'Unknown') DEFAULT 'Unknown',
    summary TEXT,
    raw_data LONGTEXT, -- Full JSON of results
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- DNS Records cache (optional but useful for reports)
CREATE TABLE IF NOT EXISTS dns_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    investigation_id INT,
    record_type VARCHAR(10), -- A, AAAA, MX, NS, TXT, SPF, DMARC
    value TEXT,
    status VARCHAR(50),
    FOREIGN KEY (investigation_id) REFERENCES investigations(id) ON DELETE CASCADE
);

-- Findings table
CREATE TABLE IF NOT EXISTS findings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    investigation_id INT,
    severity ENUM('Low', 'Medium', 'High', 'Info') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    evidence TEXT,
    recommendation TEXT,
    FOREIGN KEY (investigation_id) REFERENCES investigations(id) ON DELETE CASCADE
);

-- Index for history search
CREATE INDEX idx_target ON investigations(target);
CREATE INDEX idx_type ON investigations(type);
CREATE INDEX idx_created_at ON investigations(created_at);
