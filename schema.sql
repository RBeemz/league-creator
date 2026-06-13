-- League Creator Database Schema
-- Import this file via phpMyAdmin or: mysql -u root -p < schema.sql

CREATE DATABASE IF NOT EXISTS league_creator CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE league_creator;

CREATE TABLE IF NOT EXISTS leagues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    format ENUM('1x','2x','custom') NOT NULL DEFAULT '2x',
    rounds INT DEFAULT NULL,
    status ENUM('active','finished','archived') NOT NULL DEFAULT 'active',
    logo_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    league_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    city VARCHAR(100),
    logo_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (league_id) REFERENCES leagues(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    position ENUM('GK','DEF','MID','FWD') NOT NULL DEFAULT 'MID',
    number INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    league_id INT NOT NULL,
    home_team_id INT NOT NULL,
    away_team_id INT NOT NULL,
    round INT NOT NULL,
    match_date DATE DEFAULT NULL,
    status ENUM('scheduled','played','postponed') NOT NULL DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (league_id) REFERENCES leagues(id) ON DELETE CASCADE,
    FOREIGN KEY (home_team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (away_team_id) REFERENCES teams(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS match_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL UNIQUE,
    home_score INT NOT NULL DEFAULT 0,
    away_score INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS player_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    player_id INT NOT NULL,
    goals INT NOT NULL DEFAULT 0,
    assists INT NOT NULL DEFAULT 0,
    yellow_cards INT NOT NULL DEFAULT 0,
    red_cards INT NOT NULL DEFAULT 0,
    UNIQUE KEY unique_player_match (match_id, player_id),
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
);
