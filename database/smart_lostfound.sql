-- Database: smart_lostfound
CREATE DATABASE IF NOT EXISTS smart_lostfound CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_lostfound;

-- Users table
CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('student','staff') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lost items
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
);

-- Found items
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
);

-- Matches table
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
);

-- Feedback
CREATE TABLE IF NOT EXISTS feedback (
  feedback_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  message VARCHAR(500) NOT NULL,
  date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Sample users
INSERT INTO users (name,email,password,role) VALUES
('Alice Student','alice@student.kec.edu', MD5('alice123'), 'student'),
('Bob Staff','bob@kec.edu', MD5('bob123'), 'staff')
ON DUPLICATE KEY UPDATE email=email;

-- Sample lost item
INSERT INTO lost_items (user_id,item_name,category,place_lost,date_lost,description,image,status) VALUES
(1,'Black Wallet','Accessories','Library', DATE_SUB(CURDATE(), INTERVAL 2 DAY),'Leather wallet with KEC ID inside',NULL,'Pending')
ON DUPLICATE KEY UPDATE item_name=item_name;

-- Sample found item
INSERT INTO found_items (user_id,item_name,category,place_found,date_found,description,image,status) VALUES
(2,'Wallet Black','Accessories','Library Entrance', DATE_SUB(CURDATE(), INTERVAL 1 DAY),'Found near library gate',NULL,'Pending')
ON DUPLICATE KEY UPDATE item_name=item_name;

