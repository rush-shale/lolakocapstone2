-- =====================================================
-- COMPLETE LoLaKo Senior Citizens Management System
-- Database Schema - Full System
-- =====================================================
-- Created: January 2025
-- Version: 3.0
-- Compatibility: MySQL 5.7+, MariaDB 10.3+
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =====================================================
-- Database Creation
-- =====================================================
CREATE DATABASE IF NOT EXISTS `lolako` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `lolako`;

-- =====================================================
-- Table: users
-- Description: System users (admin and barangay users)
-- =====================================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `barangay` varchar(120) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Table: barangays
-- Description: List of barangays in the system
-- =====================================================
CREATE TABLE `barangays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Table: seniors
-- Description: Senior citizens information with complete registration form fields
-- =====================================================
CREATE TABLE `seniors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `sex` enum('male','female','lgbtq') DEFAULT NULL,
  `place_of_birth` varchar(150) DEFAULT NULL,
  `civil_status` enum('single','married','widowed','divorced','separated') DEFAULT NULL,
  `educational_attainment` enum('no_formal_education','elementary','high_school','vocational','college','graduate','post_graduate') DEFAULT NULL,
  `occupation` varchar(150) DEFAULT NULL,
  `annual_income` decimal(12,2) DEFAULT NULL,
  `other_skills` text DEFAULT NULL,
  `barangay` varchar(120) NOT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `benefits_received` tinyint(1) NOT NULL DEFAULT 0,
  `life_status` enum('living','deceased') NOT NULL DEFAULT 'living',
  `category` enum('local','national') NOT NULL DEFAULT 'local',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Table: events
-- Description: Events with enhanced information (contact and location)
-- =====================================================
CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `exact_location` text DEFAULT NULL,
  `scope` enum('admin','barangay') NOT NULL DEFAULT 'barangay',
  `barangay` varchar(120) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Table: attendance
-- Description: Event attendance tracking
-- =====================================================
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `senior_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`senior_id`,`event_id`),
  KEY `event_id` (`event_id`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`senior_id`) REFERENCES `seniors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Table: family_composition
-- Description: Family members information for each senior
-- =====================================================
CREATE TABLE `family_composition` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `senior_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `birthday` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `relation` varchar(100) NOT NULL,
  `civil_status` varchar(50) DEFAULT NULL,
  `occupation` varchar(150) DEFAULT NULL,
  `income` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `senior_id` (`senior_id`),
  CONSTRAINT `family_composition_ibfk_1` FOREIGN KEY (`senior_id`) REFERENCES `seniors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Table: association_info
-- Description: Association membership information for seniors
-- =====================================================
CREATE TABLE `association_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `senior_id` int(11) NOT NULL,
  `association_name` varchar(200) DEFAULT NULL,
  `association_address` text DEFAULT NULL,
  `membership_date` date DEFAULT NULL,
  `is_officer` tinyint(1) NOT NULL DEFAULT 0,
  `position` varchar(100) DEFAULT NULL,
  `date_elected` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `senior_id` (`senior_id`),
  CONSTRAINT `association_info_ibfk_1` FOREIGN KEY (`senior_id`) REFERENCES `seniors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Sample Data Insertion
-- =====================================================

-- Insert sample users
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `barangay`, `active`, `created_at`) VALUES
(1, 'OSCA Head', 'admin@example.com', '$2y$10$d6.i4lwkQ/bm5d3r12OqQOaHpJ.1Y1eteoMu9g9jURbWliKgsLmLG', 'admin', NULL, 1, '2025-09-16 11:09:08'),
(2, 'Mantibugao Staff', 'mantibugao@example.com', '$2y$10$B3Nw3Ctay0meeWWNHPhLpeR.GbUjof6mZ/mRD34n67WrABQsOrfva', 'user', 'Mantibugao', 1, '2025-09-16 11:13:51'),
(3, 'Tankulan Staff', 'tankulan@example.com', '$2y$10$qHW2wdHj2uCsZFbviwRvwOgOxVhx/Yuv119GuBnsEuqA87/YJyE2S', 'user', 'Tankulan', 1, '2025-09-17 01:38:51');

-- Insert sample barangays
INSERT INTO `barangays` (`id`, `name`, `created_at`) VALUES
(1, 'Mantibugao', '2025-09-16 11:29:19'),
(2, 'Tankulan', '2025-09-16 11:30:13'),
(3, 'Lunocan', '2025-09-17 01:34:00');

-- Insert sample seniors with new fields
INSERT INTO `seniors` (`id`, `first_name`, `middle_name`, `last_name`, `age`, `date_of_birth`, `sex`, `place_of_birth`, `civil_status`, `educational_attainment`, `occupation`, `annual_income`, `other_skills`, `barangay`, `contact`, `benefits_received`, `life_status`, `category`, `created_at`) VALUES
(1, 'Juan', 'Santos', 'Dela Cruz', 72, '1952-03-15', 'male', 'Manila', 'married', 'college', 'Retired Teacher', 240000.00, 'Teaching, Writing', 'Mantibugao', '09123456789', 1, 'living', 'local', '2025-09-16 11:15:02'),
(2, 'Maria', 'Garcia', 'Santos', 68, '1956-07-22', 'female', 'Cebu', 'widowed', 'high_school', 'Retired Nurse', 180000.00, 'Nursing, Cooking', 'Tankulan', '09234567890', 0, 'living', 'national', '2025-09-16 11:30:24'),
(3, 'Pedro', 'Lopez', 'Reyes', 75, '1949-12-10', 'male', 'Davao', 'single', 'elementary', 'Farmer', 120000.00, 'Farming, Carpentry', 'Lunocan', '09345678901', 1, 'living', 'local', '2025-09-17 01:32:38'),
(4, 'Ana', 'Cruz', 'Mendoza', 71, '1953-05-18', 'female', 'Iloilo', 'married', 'vocational', 'Retired Secretary', 150000.00, 'Administrative, Typing', 'Mantibugao', '09456789012', 0, 'living', 'local', '2025-09-17 01:44:45'),
(5, 'Carlos', 'Rivera', 'Gonzales', 69, '1955-09-03', 'lgbtq', 'Baguio', 'single', 'college', 'Retired Social Worker', 200000.00, 'Counseling, Community Work', 'Tankulan', '09567890123', 1, 'living', 'national', '2025-09-17 01:46:33');

-- Insert sample events with new fields
INSERT INTO `events` (`id`, `title`, `description`, `event_date`, `event_time`, `contact_number`, `exact_location`, `scope`, `barangay`, `created_by`, `created_at`) VALUES
(1, 'Monthly Pension Distribution', 'Regular monthly distribution of senior citizen benefits', '2025-02-15', '09:00:00', '09123456789', 'Mantibugao Barangay Hall, Ground Floor', 'admin', NULL, 1, '2025-01-15 10:00:00'),
(2, 'Health Check-up Program', 'Free medical check-up for senior citizens', '2025-02-20', '08:00:00', '09234567890', 'Tankulan Health Center, Main Building', 'admin', NULL, 1, '2025-01-16 11:00:00'),
(3, 'Christmas Party 2024', 'Annual Christmas celebration for senior citizens', '2024-12-18', '10:00:00', '09345678901', 'Lunocan Community Center, Multi-purpose Hall', 'admin', NULL, 1, '2025-09-17 01:35:27'),
(4, 'Barangay Assembly', 'Monthly barangay assembly meeting', '2025-01-25', '14:00:00', '09456789012', 'Mantibugao Barangay Hall, Conference Room', 'barangay', 'Mantibugao', 2, '2025-01-20 15:30:00');

-- Insert sample attendance records
INSERT INTO `attendance` (`id`, `senior_id`, `event_id`, `marked_at`) VALUES
(1, 1, 1, '2025-01-15 09:15:00'),
(2, 2, 1, '2025-01-15 09:20:00'),
(3, 4, 1, '2025-01-15 09:25:00'),
(4, 1, 2, '2025-01-20 08:10:00'),
(5, 2, 2, '2025-01-20 08:15:00'),
(6, 5, 2, '2025-01-20 08:20:00'),
(7, 1, 3, '2024-12-18 10:05:00'),
(8, 2, 3, '2024-12-18 10:10:00'),
(9, 3, 3, '2024-12-18 10:15:00'),
(10, 4, 3, '2024-12-18 10:20:00'),
(11, 5, 3, '2024-12-18 10:25:00');

-- Insert sample family composition
INSERT INTO `family_composition` (`id`, `senior_id`, `name`, `birthday`, `age`, `relation`, `civil_status`, `occupation`, `income`, `created_at`) VALUES
(1, 1, 'Carmen Dela Cruz', '1955-08-12', 69, 'Spouse', 'married', 'Retired Teacher', 240000.00, '2025-01-15 10:00:00'),
(2, 1, 'Jose Dela Cruz', '1980-03-20', 44, 'Son', 'single', 'Engineer', 600000.00, '2025-01-15 10:00:00'),
(3, 2, 'Roberto Santos', '1950-11-05', 74, 'Late Husband', 'deceased', 'Retired Engineer', 0.00, '2025-01-15 10:00:00'),
(4, 2, 'Elena Santos', '1982-07-15', 42, 'Daughter', 'married', 'Teacher', 400000.00, '2025-01-15 10:00:00'),
(5, 4, 'Ricardo Mendoza', '1952-01-30', 73, 'Spouse', 'married', 'Retired Driver', 180000.00, '2025-01-15 10:00:00');

-- Insert sample association information
INSERT INTO `association_info` (`id`, `senior_id`, `association_name`, `association_address`, `membership_date`, `is_officer`, `position`, `date_elected`, `created_at`) VALUES
(1, 1, 'Mantibugao Senior Citizens Association', 'Mantibugao Barangay Hall, 2nd Floor', '2020-01-15', 1, 'Secretary', '2023-01-15', '2025-01-15 10:00:00'),
(2, 2, 'Tankulan Senior Citizens Club', 'Tankulan Community Center', '2019-06-20', 0, NULL, NULL, '2025-01-15 10:00:00'),
(3, 5, 'LGBTQ Senior Citizens Alliance', 'City Hall, Community Affairs Office', '2021-03-10', 1, 'Treasurer', '2023-03-10', '2025-01-15 10:00:00');

-- =====================================================
-- Indexes for Performance Optimization
-- =====================================================

-- Additional indexes for better performance
CREATE INDEX `idx_seniors_barangay` ON `seniors` (`barangay`);
CREATE INDEX `idx_seniors_life_status` ON `seniors` (`life_status`);
CREATE INDEX `idx_seniors_category` ON `seniors` (`category`);
CREATE INDEX `idx_seniors_sex` ON `seniors` (`sex`);
CREATE INDEX `idx_events_date` ON `events` (`event_date`);
CREATE INDEX `idx_events_scope` ON `events` (`scope`);
CREATE INDEX `idx_attendance_senior` ON `attendance` (`senior_id`);
CREATE INDEX `idx_attendance_event` ON `attendance` (`event_id`);

-- =====================================================
-- Views for Common Queries
-- =====================================================

-- View: Active seniors with their barangay information
CREATE VIEW `v_active_seniors` AS
SELECT 
    s.id,
    CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name) AS full_name,
    s.age,
    s.sex,
    s.barangay,
    s.category,
    s.life_status,
    s.created_at,
    COUNT(a.id) as events_attended
FROM seniors s
LEFT JOIN attendance a ON s.id = a.senior_id
WHERE s.life_status = 'living'
GROUP BY s.id, s.first_name, s.middle_name, s.last_name, s.age, s.sex, s.barangay, s.category, s.life_status, s.created_at;

-- View: Events with attendance counts
CREATE VIEW `v_events_with_attendance` AS
SELECT 
    e.id,
    e.title,
    e.event_date,
    e.event_time,
    e.contact_number,
    e.exact_location,
    e.scope,
    e.barangay,
    COUNT(a.id) as attendance_count,
    u.name as created_by_name
FROM events e
LEFT JOIN attendance a ON e.id = a.event_id
LEFT JOIN users u ON e.created_by = u.id
GROUP BY e.id, e.title, e.event_date, e.event_time, e.contact_number, e.exact_location, e.scope, e.barangay, u.name;

-- =====================================================
-- Stored Procedures for Common Operations
-- =====================================================

DELIMITER //

-- Procedure: Get seniors by barangay
CREATE PROCEDURE `sp_get_seniors_by_barangay`(IN p_barangay VARCHAR(120))
BEGIN
    SELECT 
        s.*,
        COUNT(DISTINCT a.event_id) as events_attended,
        GROUP_CONCAT(DISTINCT fc.relation) as family_relations,
        ai.association_name,
        ai.is_officer
    FROM seniors s
    LEFT JOIN attendance a ON s.id = a.senior_id
    LEFT JOIN family_composition fc ON s.id = fc.senior_id
    LEFT JOIN association_info ai ON s.id = ai.senior_id
    WHERE s.barangay = p_barangay
    GROUP BY s.id
    ORDER BY s.last_name, s.first_name;
END //

-- Procedure: Get event statistics
CREATE PROCEDURE `sp_get_event_statistics`(IN p_event_id INT)
BEGIN
    SELECT 
        e.title,
        e.event_date,
        COUNT(a.id) as total_attendance,
        COUNT(DISTINCT s.barangay) as barangays_represented,
        COUNT(CASE WHEN s.sex = 'male' THEN 1 END) as male_count,
        COUNT(CASE WHEN s.sex = 'female' THEN 1 END) as female_count,
        COUNT(CASE WHEN s.sex = 'lgbtq' THEN 1 END) as lgbtq_count
    FROM events e
    LEFT JOIN attendance a ON e.id = a.event_id
    LEFT JOIN seniors s ON a.senior_id = s.id
    WHERE e.id = p_event_id
    GROUP BY e.id, e.title, e.event_date;
END //

DELIMITER ;

-- =====================================================
-- Triggers for Data Integrity
-- =====================================================

-- Trigger: Update senior age when date_of_birth changes
DELIMITER //
CREATE TRIGGER `tr_update_senior_age` 
BEFORE UPDATE ON `seniors`
FOR EACH ROW
BEGIN
    IF NEW.date_of_birth IS NOT NULL AND (OLD.date_of_birth IS NULL OR NEW.date_of_birth != OLD.date_of_birth) THEN
        SET NEW.age = YEAR(CURDATE()) - YEAR(NEW.date_of_birth) - 
                     (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(NEW.date_of_birth, '%m%d'));
    END IF;
END //
DELIMITER ;

-- Trigger: Log attendance changes
DELIMITER //
CREATE TRIGGER `tr_log_attendance` 
AFTER INSERT ON `attendance`
FOR EACH ROW
BEGIN
    -- You can add logging logic here if needed
    -- For example, insert into an audit table
END //
DELIMITER ;

-- =====================================================
-- System Configuration
-- =====================================================

-- Set proper timezone
SET time_zone = '+08:00';

-- =====================================================
-- Final Commit
-- =====================================================
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- =====================================================
-- END OF COMPLETE SYSTEM SCHEMA
-- =====================================================

-- =====================================================
-- USAGE INSTRUCTIONS:
-- =====================================================
-- 1. Run this entire script to create the complete database
-- 2. Default admin credentials:
--    Email: admin@example.com
--    Password: admin123 (you should change this in production)
-- 3. Sample data is included for testing
-- 4. All tables, indexes, views, procedures, and triggers are created
-- 5. The system is ready for use with all features enabled
-- =====================================================
