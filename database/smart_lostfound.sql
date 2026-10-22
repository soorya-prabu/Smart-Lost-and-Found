-- Smart Lost and Found Database Schema & Initial Data
CREATE DATABASE IF NOT EXISTS smart_lostfound CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_lostfound;

-- 1. Users table (students, staff, admin)
CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('student','staff','admin') NOT NULL DEFAULT 'student',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Lost items table
CREATE TABLE IF NOT EXISTS lost_items (
  lost_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  item_name VARCHAR(150) NOT NULL,
  category VARCHAR(100) NOT NULL,
  place_lost VARCHAR(150) NOT NULL,
  date_lost DATE NOT NULL,
  description TEXT,
  image VARCHAR(255),
  status ENUM('Pending','Matched','Returned') DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Found items table
CREATE TABLE IF NOT EXISTS found_items (
  found_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  item_name VARCHAR(150) NOT NULL,
  category VARCHAR(100) NOT NULL,
  place_found VARCHAR(150) NOT NULL,
  date_found DATE NOT NULL,
  description TEXT,
  image VARCHAR(255),
  status ENUM('Pending','Matched','Returned') DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Matches table
CREATE TABLE IF NOT EXISTS matches (
  match_id INT AUTO_INCREMENT PRIMARY KEY,
  lost_id INT NOT NULL,
  found_id INT NOT NULL,
  status ENUM('Pending','Contacted','Resolved') DEFAULT 'Pending',
  notified ENUM('No','Yes') DEFAULT 'No',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_pair (lost_id, found_id),
  FOREIGN KEY (lost_id) REFERENCES lost_items(lost_id) ON DELETE CASCADE,
  FOREIGN KEY (found_id) REFERENCES found_items(found_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Feedback table
CREATE TABLE IF NOT EXISTS feedback (
  feedback_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  message VARCHAR(500) NOT NULL,
  date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Activity log table
CREATE TABLE IF NOT EXISTS activity_log (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  user_role VARCHAR(20),
  action VARCHAR(100) NOT NULL,
  item_type VARCHAR(20),
  item_id INT,
  details TEXT,
  ip_address VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance indexes
CREATE INDEX idx_lost_items_status ON lost_items(status);
CREATE INDEX idx_found_items_status ON found_items(status);
CREATE INDEX idx_matches_status ON matches(status);
CREATE INDEX idx_activity_user ON activity_log(user_id);
CREATE INDEX idx_activity_created ON activity_log(created_at);
CREATE INDEX idx_activity_action ON activity_log(action);

-- Default / Seed Users (Password for student: alice123, staff: bob123, admin: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Alice Student', 'alice@student.kec.edu', '$2y$10$c5UwOBFFhfaPUpJ1rCnG9.OrX119hYDJrPUQ0hrd7eWm6DMkEEe.G', 'student'),
('Bob Staff', 'bob@kec.edu', '$2y$10$O7TKGqG71HtfK13hplMQEOpUHgkaBQyRLJzxZw5c5AQ/QTdJpQzXa', 'staff'),
('College Admin', 'admin@kec.edu', '$2y$10$2meIyv3/nDddaxtQGsv5veh6gjDIrvnyjB3xg7baoq3VIFLUuM7gS', 'admin')
ON DUPLICATE KEY UPDATE email=email;

-- Sample lost item
INSERT INTO lost_items (user_id, item_name, category, place_lost, date_lost, description, image, status) VALUES
(1, 'Black Wallet', 'Accessories', 'Library', DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Leather wallet with KEC ID inside', NULL, 'Pending')
ON DUPLICATE KEY UPDATE item_name=item_name;

-- Sample found item
INSERT INTO found_items (user_id, item_name, category, place_found, date_found, description, image, status) VALUES
(2, 'Wallet Black', 'Accessories', 'Library Entrance', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Found near library gate', NULL, 'Pending')
ON DUPLICATE KEY UPDATE item_name=item_name;
