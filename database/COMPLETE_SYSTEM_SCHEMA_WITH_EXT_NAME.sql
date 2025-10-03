-- =====================================================
-- COMPLETE LoLaKo Senior Citizens Management System
-- Database Schema - Full System with ext_name, validation_status, and validation_date columns added
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
-- Description: Senior citizens information with complete registration form fields including ext_name, validation_status, and validation_date
-- =====================================================
CREATE TABLE `seniors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `ext_name` varchar(50) DEFAULT NULL,
  `age` int(11) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `sex` enum('male','female','lgbtq') DEFAULT NULL,
  `place_of_birth` varchar(150) DEFAULT NULL,
  `civil_status` enum('single','married','widowed','divorced','separated') NOT NULL,
  `educational_attainment` enum('no_formal_education','elementary','high_school','vocational','college','graduate','post_graduate') NOT NULL,
  `occupation` varchar(150) DEFAULT NULL,
  `annual_income` decimal(12,2) DEFAULT NULL,
  `other_skills` text NOT NULL,
  `barangay` varchar(120) NOT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `osca_id_no` varchar(50) NOT NULL,
  `remarks` text NOT NULL,
  `health_condition` varchar(255) NOT NULL,
  `purok` varchar(100) NOT NULL,
  `cellphone` varchar(50) DEFAULT NULL,
  `benefits_received` tinyint(1) NOT NULL DEFAULT 0,
  `life_status` enum('living','deceased') NOT NULL DEFAULT 'living',
  `category` enum('local','national','waiting') NOT NULL DEFAULT 'local',
  `validation_status` enum('Validated','Not Validated') DEFAULT 'Validated',
  `validation_date` timestamp NULL DEFAULT NULL,
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
-- Indexes for Performance Optimization
-- =====================================================

CREATE INDEX `idx_seniors_barangay` ON `seniors` (`barangay`);
CREATE INDEX `idx_seniors_life_status` ON `seniors` (`life_status`);
CREATE INDEX `idx_seniors_category` ON `seniors` (`category`);
CREATE INDEX `idx_seniors_sex` ON `seniors` (`sex`);
CREATE INDEX `idx_events_date` ON `events` (`event_date`);
CREATE INDEX `idx_events_scope` ON `events` (`scope`);
CREATE INDEX `idx_attendance_senior` ON `attendance` (`senior_id`);
CREATE INDEX `idx_attendance_event` ON `attendance` (`event_id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- =====================================================
-- END OF COMPLETE SYSTEM SCHEMA WITH ext_name, validation_status, and validation_date COLUMNS
-- =====================================================
