-- LoLaKo initial schema

CREATE TABLE IF NOT EXISTS users (
	id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(100) NOT NULL,
	email VARCHAR(150) NOT NULL UNIQUE,
	password_hash VARCHAR(255) NOT NULL,
	role ENUM('admin','user') NOT NULL DEFAULT 'user',
	barangay VARCHAR(120) NULL,
	active TINYINT(1) NOT NULL DEFAULT 1,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS seniors (
	id INT AUTO_INCREMENT PRIMARY KEY,
	first_name VARCHAR(100) NOT NULL,
	middle_name VARCHAR(100) NULL,
	last_name VARCHAR(100) NOT NULL,
	age INT NOT NULL,
	barangay VARCHAR(120) NOT NULL,
	contact VARCHAR(50) NULL,
	benefits_received TINYINT(1) NOT NULL DEFAULT 0,
	life_status ENUM('living','deceased') NOT NULL DEFAULT 'living',
	category ENUM('local','national') NOT NULL DEFAULT 'local',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Barangays master table
CREATE TABLE IF NOT EXISTS barangays (
	id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(150) NOT NULL UNIQUE,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Optional seed
INSERT INTO barangays (name) VALUES
('Mantibugao')
ON DUPLICATE KEY UPDATE name = VALUES(name);

CREATE TABLE IF NOT EXISTS events (
	id INT AUTO_INCREMENT PRIMARY KEY,
	title VARCHAR(200) NOT NULL,
	description TEXT NULL,
	event_date DATE NOT NULL,
	event_time TIME NULL,
	scope ENUM('admin','barangay') NOT NULL DEFAULT 'barangay',
	barangay VARCHAR(120) NULL,
	created_by INT NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS attendance (
	id INT AUTO_INCREMENT PRIMARY KEY,
	senior_id INT NOT NULL,
	event_id INT NOT NULL,
	marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	UNIQUE KEY unique_attendance (senior_id, event_id),
	FOREIGN KEY (senior_id) REFERENCES seniors(id) ON DELETE CASCADE,
	FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Seed an initial admin user (replace password later)
-- INSERT INTO users (name, email, password_hash, role) VALUES (
--   'OSCA Head', 'admin@example.com', '$2y$10$replace_with_password_hash', 'admin'
-- );


