-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 17, 2025 at 04:22 AM
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
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `senior_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'Mantibugao', '2025-09-16 11:29:19'),
(2, 'tankulan', '2025-09-16 11:30:13'),
(3, 'lunocan', '2025-09-17 01:34:00');

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
  `scope` enum('admin','barangay') NOT NULL DEFAULT 'barangay',
  `barangay` varchar(120) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `event_date`, `event_time`, `scope`, `barangay`, `created_by`, `created_at`) VALUES
(1, 'xwe', 'qwe', '2002-10-22', '03:44:00', 'admin', NULL, 1, '2025-09-16 11:15:57'),
(2, '121', '3131', '2222-12-31', '12:03:00', 'barangay', 'staff', 2, '2025-09-16 11:16:43'),
(3, 'release pension', 'sda', '2025-09-24', '10:00:00', 'admin', NULL, 1, '2025-09-17 01:28:31'),
(4, 'christmas party', 'all seniors must attend', '2025-12-18', '10:00:00', 'admin', NULL, 1, '2025-09-17 01:35:27');

-- --------------------------------------------------------

--
-- Table structure for table `seniors`
--

CREATE TABLE `seniors` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `barangay` varchar(120) NOT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `benefits_received` tinyint(1) NOT NULL DEFAULT 0,
  `life_status` enum('living','deceased') NOT NULL DEFAULT 'living',
  `category` enum('local','national') NOT NULL DEFAULT 'local',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seniors`
--

INSERT INTO `seniors` (`id`, `first_name`, `middle_name`, `last_name`, `age`, `barangay`, `contact`, `benefits_received`, `life_status`, `category`, `created_at`) VALUES
(1, 'John Rushel', 'Rushel', 'Hinoyog', 333, 'tankulan', '909009323', 1, 'deceased', 'national', '2025-09-16 11:15:02'),
(2, 'John Rushel', 'Rushel', 'Hinoyog', 333, 'Mantibugao', '909009323', 0, 'living', 'local', '2025-09-16 11:30:24'),
(3, 'ronel', 'hawdw', 'cinco', 120, 'Mantibugao', NULL, 1, 'living', 'local', '2025-09-17 01:32:38'),
(4, 'ronel', 'hawdw', 'cinco', 60, 'lunocan', '2', 0, 'living', 'national', '2025-09-17 01:44:45'),
(5, 'll', 'ad', 'awdw', 61, 'Mantibugao', '3', 0, 'deceased', 'local', '2025-09-17 01:46:33');

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
(1, 'OSCA Head', 'admin@example.com', '$2y$10$d6.i4lwkQ/bm5d3r12OqQOaHpJ.1Y1eteoMu9g9jURbWliKgsLmLG', 'admin', NULL, 1, '2025-09-16 11:09:08'),
(2, 'mantibugao', 'angelodatoycabana11@gmail.com', '$2y$10$B3Nw3Ctay0meeWWNHPhLpeR.GbUjof6mZ/mRD34n67WrABQsOrfva', 'user', 'staff', 1, '2025-09-16 11:13:51'),
(3, 'Brgy. ALAE', 'anjuuugd@gmail.com', '$2y$10$qHW2wdHj2uCsZFbviwRvwOgOxVhx/Yuv119GuBnsEuqA87/YJyE2S', 'user', 'staff', 1, '2025-09-17 01:38:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`senior_id`,`event_id`),
  ADD KEY `event_id` (`event_id`);

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
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `seniors`
--
ALTER TABLE `seniors`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `barangays`
--
ALTER TABLE `barangays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `seniors`
--
ALTER TABLE `seniors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
