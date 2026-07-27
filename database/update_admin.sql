-- Update database to support admin role
USE smart_lostfound;

-- Update users table to include admin role
ALTER TABLE users MODIFY role ENUM('student','staff','admin') NOT NULL;

-- Create activity log table for tracking all activities
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
);

-- Create default admin account (password: admin123)
INSERT INTO users (name, email, password, role) VALUES
('College Admin', 'admin@kec.edu', MD5('admin123'), 'admin')
ON DUPLICATE KEY UPDATE email=email;

-- Index for better performance
CREATE INDEX idx_activity_user ON activity_log(user_id);
CREATE INDEX idx_activity_created ON activity_log(created_at);
CREATE INDEX idx_activity_action ON activity_log(action);

