-- Update seniors table to include registration form fields
-- Run this migration to add new fields to the seniors table

-- Add new personal information fields
ALTER TABLE `seniors` 
ADD COLUMN `date_of_birth` DATE NULL AFTER `age`,
ADD COLUMN `sex` ENUM('male', 'female', 'lgbtq') NULL AFTER `date_of_birth`,
ADD COLUMN `place_of_birth` VARCHAR(150) NULL AFTER `sex`,
ADD COLUMN `civil_status` ENUM('single', 'married', 'widowed', 'divorced', 'separated') NULL AFTER `place_of_birth`,
ADD COLUMN `educational_attainment` ENUM('no_formal_education', 'elementary', 'high_school', 'vocational', 'college', 'graduate', 'post_graduate') NULL AFTER `civil_status`,
ADD COLUMN `occupation` VARCHAR(150) NULL AFTER `educational_attainment`,
ADD COLUMN `annual_income` DECIMAL(12,2) NULL AFTER `occupation`,
ADD COLUMN `other_skills` TEXT NULL AFTER `annual_income`;

-- Create family_composition table
CREATE TABLE `family_composition` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `senior_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `birthday` date NULL,
  `age` int(11) NULL,
  `relation` varchar(100) NOT NULL,
  `civil_status` varchar(50) NULL,
  `occupation` varchar(150) NULL,
  `income` decimal(12,2) NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `senior_id` (`senior_id`),
  CONSTRAINT `family_composition_ibfk_1` FOREIGN KEY (`senior_id`) REFERENCES `seniors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create association_info table
CREATE TABLE `association_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `senior_id` int(11) NOT NULL,
  `association_name` varchar(200) NULL,
  `association_address` text NULL,
  `membership_date` date NULL,
  `is_officer` tinyint(1) NOT NULL DEFAULT 0,
  `position` varchar(100) NULL,
  `date_elected` date NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `senior_id` (`senior_id`),
  CONSTRAINT `association_info_ibfk_1` FOREIGN KEY (`senior_id`) REFERENCES `seniors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
1