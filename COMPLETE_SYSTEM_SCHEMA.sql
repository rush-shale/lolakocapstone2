-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 14, 2025 at 05:35 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lolako`
--

-- --------------------------------------------------------

--
-- Table structure for table `association_info`
--

CREATE TABLE `association_info` (
  `id` int(11) NOT NULL,
  `senior_id` int(11) NOT NULL,
  `association_name` varchar(200) DEFAULT NULL,
  `association_address` text DEFAULT NULL,
  `membership_date` date DEFAULT NULL,
  `is_officer` tinyint(1) NOT NULL DEFAULT 0,
  `position` varchar(100) DEFAULT NULL,
  `date_elected` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `senior_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `senior_id`, `event_id`, `marked_at`) VALUES
(2, 26, 1, '2025-10-14 03:34:04');

-- --------------------------------------------------------

--
-- Table structure for table `barangays`
--

CREATE TABLE `barangays` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barangays`
--

INSERT INTO `barangays` (`id`, `name`, `created_at`) VALUES
(1, 'Tankulan', '2025-10-04 02:39:41'),
(3, 'Dalirig', '2025-10-06 08:38:47');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `exact_location` text DEFAULT NULL,
  `scope` enum('admin','barangay') NOT NULL DEFAULT 'barangay',
  `barangay` varchar(120) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `event_date`, `event_time`, `contact_number`, `exact_location`, `scope`, `barangay`, `created_by`, `created_at`) VALUES
(1, 'christmas party', NULL, '2025-12-18', '10:00:00', NULL, NULL, 'barangay', 'Tankulan', 1, '2025-10-06 13:02:09'),
(2, 'pension', 'asdw', '2025-10-14', '11:35:00', NULL, NULL, 'barangay', 'Tankulan', 1, '2025-10-14 03:33:47');

-- --------------------------------------------------------

--
-- Table structure for table `family_composition`
--

CREATE TABLE `family_composition` (
  `id` int(11) NOT NULL,
  `senior_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `birthday` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `relation` varchar(100) NOT NULL,
  `civil_status` varchar(50) DEFAULT NULL,
  `occupation` varchar(150) DEFAULT NULL,
  `income` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seniors`
--

CREATE TABLE `seniors` (
  `id` int(11) NOT NULL,
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
  `gender` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seniors`
--

INSERT INTO `seniors` (`id`, `first_name`, `middle_name`, `last_name`, `ext_name`, `age`, `date_of_birth`, `sex`, `place_of_birth`, `civil_status`, `educational_attainment`, `occupation`, `annual_income`, `other_skills`, `barangay`, `contact`, `osca_id_no`, `remarks`, `health_condition`, `purok`, `cellphone`, `benefits_received`, `life_status`, `category`, `validation_status`, `validation_date`, `created_at`, `gender`) VALUES
(26, 'admin', 'D', 'cabana', 'jr.', 74, '1950-12-31', 'male', 'mantibugao', 'married', 'college', 'Farmer', NULL, '', 'Tankulan', '', '1342', 'yah', 'iwan', 'purok 4', '09972959221', 0, 'living', 'national', 'Validated', '2025-10-13 09:37:36', '2025-10-13 14:18:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `senior_deaths`
--

CREATE TABLE `senior_deaths` (
  `id` int(11) NOT NULL,
  `senior_id` int(11) NOT NULL,
  `date_of_death` date DEFAULT NULL,
  `time_of_death` time DEFAULT NULL,
  `place_of_death` varchar(255) DEFAULT NULL,
  `cause_of_death` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `senior_transfers`
--

CREATE TABLE `senior_transfers` (
  `id` int(11) NOT NULL,
  `senior_id` int(11) NOT NULL,
  `transfer_reason` varchar(255) NOT NULL,
  `new_address` varchar(255) NOT NULL,
  `effective_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `senior_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `senior_transfers`
--

INSERT INTO `senior_transfers` (`id`, `senior_id`, `transfer_reason`, `new_address`, `effective_date`, `created_at`, `senior_name`) VALUES
(11, 26, 'linog', 'damilag', '2025-10-13', '2025-10-13 14:21:01', 'admin D cabana jr.');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `barangay` varchar(120) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `barangay`, `active`, `created_at`) VALUES
(1, 'Tankulan', 'Tankulan@example.com', '$2y$10$/xmtF0GCTMDPxYenNPi63e35ecbzJLoVq1LB.UapjPuRSl4HBjWGi', 'user', 'Tankulan', 1, '2025-10-04 02:40:15'),
(2, 'Administrator', 'admin@lolako.com', '$2y$10$A7mgu6Xqjww3m3t3wG6gEu0O69BNqXpCcyzphgUKcoRbz20VRXZmC', 'admin', NULL, 1, '2025-10-04 03:28:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `association_info`
--
ALTER TABLE `association_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `senior_id` (`senior_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`senior_id`,`event_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `idx_attendance_senior` (`senior_id`),
  ADD KEY `idx_attendance_event` (`event_id`);

--
-- Indexes for table `barangays`
--
ALTER TABLE `barangays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_events_date` (`event_date`),
  ADD KEY `idx_events_scope` (`scope`);

--
-- Indexes for table `family_composition`
--
ALTER TABLE `family_composition`
  ADD PRIMARY KEY (`id`),
  ADD KEY `senior_id` (`senior_id`);

--
-- Indexes for table `seniors`
--
ALTER TABLE `seniors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_seniors_barangay` (`barangay`),
  ADD KEY `idx_seniors_life_status` (`life_status`),
  ADD KEY `idx_seniors_category` (`category`),
  ADD KEY `idx_seniors_sex` (`sex`);

--
-- Indexes for table `senior_deaths`
--
ALTER TABLE `senior_deaths`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_senior_deaths_senior` (`senior_id`);

--
-- Indexes for table `senior_transfers`
--
ALTER TABLE `senior_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_senior_transfers_senior` (`senior_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `association_info`
--
ALTER TABLE `association_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `barangays`
--
ALTER TABLE `barangays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `family_composition`
--
ALTER TABLE `family_composition`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seniors`
--
ALTER TABLE `seniors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `senior_deaths`
--
ALTER TABLE `senior_deaths`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `senior_transfers`
--
ALTER TABLE `senior_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `association_info`
--
ALTER TABLE `association_info`
  ADD CONSTRAINT `association_info_ibfk_1` FOREIGN KEY (`senior_id`) REFERENCES `seniors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`senior_id`) REFERENCES `seniors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `family_composition`
--
ALTER TABLE `family_composition`
  ADD CONSTRAINT `family_composition_ibfk_1` FOREIGN KEY (`senior_id`) REFERENCES `seniors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `senior_deaths`
--
ALTER TABLE `senior_deaths`
  ADD CONSTRAINT `fk_senior_deaths_senior` FOREIGN KEY (`senior_id`) REFERENCES `seniors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `senior_transfers`
--
ALTER TABLE `senior_transfers`
  ADD CONSTRAINT `fk_senior_transfers_senior` FOREIGN KEY (`senior_id`) REFERENCES `seniors` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
