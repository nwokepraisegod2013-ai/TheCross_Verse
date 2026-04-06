-- ============================================
--  EDUVERSE PORTAL – DATABASE SETUP SQL
--  Run this file once to create all tables
-- ============================================

CREATE DATABASE IF NOT EXISTS eduverse_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE eduverse_db;

-- ---- SCHOOLS TABLE ----
CREATE TABLE IF NOT EXISTS schools (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  school_key VARCHAR(50) UNIQUE NOT NULL,
  name       VARCHAR(150) NOT NULL,
  motto      VARCHAR(255),
  description TEXT,
  mascot     VARCHAR(10),
  features   JSON,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO schools (school_key, name, motto, description, mascot, features) VALUES
('brightstar', 'BrightStar Academy', '✨ "Where Every Star Shines"',
 'A premier institution focusing on STEM excellence, arts, and holistic development.',
 '🦁',
 '["🔬 STEM Excellence Program","🎭 Arts & Drama Studio","⚽ Sports Academy","💻 Digital Innovation Lab"]'),
('moonrise', 'Moonrise Institute', '🌙 "Reach Beyond the Stars"',
 'Nurturing creativity, environmental consciousness, and academic excellence.',
 '🦅',
 '["🎵 Music & Performing Arts","🌿 Eco & Nature Studies","📖 Advanced Literature","🤖 Robotics & AI Club"]');

-- ---- AGE GROUPS TABLE ----
CREATE TABLE IF NOT EXISTS age_groups (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  group_key   VARCHAR(50) UNIQUE NOT NULL,
  icon        VARCHAR(10) NOT NULL,
  name        VARCHAR(100) NOT NULL,
  min_age     TINYINT UNSIGNED NOT NULL,
  max_age     TINYINT UNSIGNED NOT NULL,
  level_label VARCHAR(100),
  description TEXT,
  sort_order  TINYINT DEFAULT 0,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO age_groups (group_key, icon, name, min_age, max_age, level_label, description, sort_order) VALUES
('tiny',     '🌱', 'Tiny Sprouts',      3,  5,  'Nursery',      'Play-based learning, sensory exploration, and early literacy adventures', 1),
('junior',   '🌿', 'Junior Explorers',  6,  8,  'Primary 1–2',  'Reading, writing, numbers and creative problem-solving for curious minds', 2),
('discover', '🌳', 'Discoverers',       9,  11, 'Primary 3–5',  'Science experiments, coding basics, and deeper academic foundations', 3),
('pioneer',  '🚀', 'Pioneers',          12, 14, 'Junior High',  'Critical thinking, leadership skills, and advanced STEM pathways', 4),
('champion', '🏆', 'Champions',         15, 18, 'Senior High',  'University prep, career pathways, and elite academic competitions', 5);

-- ---- USERS TABLE ----
CREATE TABLE IF NOT EXISTS users (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  first_name   VARCHAR(100) NOT NULL,
  last_name    VARCHAR(100) NOT NULL,
  username     VARCHAR(100) UNIQUE NOT NULL,
  password     VARCHAR(255) NOT NULL,    -- bcrypt hashed
  email        VARCHAR(200),
  role         ENUM('student','parent','teacher','admin') DEFAULT 'student',
  school_key   VARCHAR(50),
  age_group_key VARCHAR(50),
  status       ENUM('active','inactive') DEFAULT 'active',
  last_login   DATETIME,
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (school_key) REFERENCES schools(school_key) ON DELETE SET NULL,
  FOREIGN KEY (age_group_key) REFERENCES age_groups(group_key) ON DELETE SET NULL
);

-- Default admin user (password: admin123)
INSERT IGNORE INTO users (first_name, last_name, username, password, email, role, school_key, status)
VALUES ('Admin', 'User', 'admin',
        '$2y$12$3VxFQ2GIYBDq8NKbG5VqXuXv0MBzlPZhKF1c.qQ7YhqS2TkNpf1Sm',
        'admin@eduverse.edu', 'admin', NULL, 'active');

-- ---- REGISTRATIONS TABLE ----
CREATE TABLE IF NOT EXISTS registrations (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  school_key        VARCHAR(50) NOT NULL,
  first_name        VARCHAR(100) NOT NULL,
  last_name         VARCHAR(100) NOT NULL,
  date_of_birth     DATE,
  gender            ENUM('male','female','other',''),
  age_group_key     VARCHAR(50),
  parent_name       VARCHAR(200),
  parent_email      VARCHAR(200),
  phone             VARCHAR(50),
  address           TEXT,
  emergency_name    VARCHAR(200),
  emergency_phone   VARCHAR(50),
  medical_notes     TEXT,
  interests         JSON,
  notes             TEXT,
  status            ENUM('pending','approved','rejected') DEFAULT 'pending',
  created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
  reviewed_at       DATETIME,
  reviewed_by       INT,
  FOREIGN KEY (school_key) REFERENCES schools(school_key),
  FOREIGN KEY (age_group_key) REFERENCES age_groups(group_key) ON DELETE SET NULL,
  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ---- ANNOUNCEMENTS TABLE ----
CREATE TABLE IF NOT EXISTS announcements (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(255) NOT NULL,
  body        TEXT NOT NULL,
  target      ENUM('all','brightstar','moonrise') DEFAULT 'all',
  priority    ENUM('normal','urgent','info') DEFAULT 'normal',
  posted_by   INT,
  posted_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  expires_at  DATETIME,
  FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT IGNORE INTO announcements (title, body, target, priority) VALUES
('🎉 Welcome Back, Students!', 'New term starts soon. Please check the schedule.', 'all', 'info'),
('🚨 Registration Closes Soon', 'Registration for the new term closes March 25th. Register now!', 'brightstar', 'urgent');

-- ---- SESSIONS TABLE (optional - can use PHP sessions instead) ----
CREATE TABLE IF NOT EXISTS sessions (
  id         VARCHAR(128) PRIMARY KEY,
  user_id    INT NOT NULL,
  data       TEXT,
  expires_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---- SETTINGS TABLE ----
CREATE TABLE IF NOT EXISTS settings (
  setting_key   VARCHAR(100) PRIMARY KEY,
  setting_value TEXT
);

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('portal_name', 'EduVerse Portal'),
('registration_open', '1'),
('admin_email', 'admin@eduverse.edu'),
('theme_color', '#6BCBF7');

-- ---- Indexes for performance ----
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_school ON users(school_key);
CREATE INDEX IF NOT EXISTS idx_regs_school ON registrations(school_key);
CREATE INDEX IF NOT EXISTS idx_regs_status ON registrations(status);