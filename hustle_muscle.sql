-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 01, 2026 at 10:25 PM
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
-- Database: `hustle_muscle`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `coach_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `time_slot` time NOT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `coach_id`, `booking_date`, `time_slot`, `status`, `created_at`) VALUES
(1, 1, 1, '2026-05-25', '19:00:00', 'confirmed', '2026-05-24 19:05:03'),
(3, 1, 3, '2026-05-25', '09:00:00', 'confirmed', '2026-05-24 19:12:53'),
(4, 3, 3, '2026-06-12', '13:00:00', 'pending', '2026-06-01 16:39:14'),
(6, 3, 1, '2026-06-02', '15:00:00', 'confirmed', '2026-06-01 18:09:50'),
(7, 5, 2, '2026-06-12', '13:00:00', 'confirmed', '2026-06-01 18:38:37'),
(8, 3, 1, '2026-06-20', '11:00:00', 'confirmed', '2026-06-01 19:47:17');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `thread_id`, `sender_id`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 1, 'hello coach', 1, '2026-06-01 18:07:20'),
(2, 1, 5, 'ahln yaam', 1, '2026-06-01 18:08:36'),
(3, 2, 3, 'i want to do a plan', 1, '2026-06-01 18:13:18'),
(4, 2, 5, 'okay sure!', 0, '2026-06-01 18:15:12'),
(5, 4, 5, 'hi coach are u ready?', 1, '2026-06-01 18:39:35'),
(6, 1, 3, 'are u guys here', 0, '2026-06-01 19:48:55'),
(7, 7, 3, 'hhiiii', 1, '2026-06-01 20:10:52'),
(8, 7, 3, 'dsf', 1, '2026-06-01 20:10:55'),
(9, 7, 3, 'asda', 1, '2026-06-01 20:10:57'),
(10, 7, 6, 'hhdhdh', 1, '2026-06-01 20:11:16');

-- --------------------------------------------------------

--
-- Table structure for table `chat_threads`
--

CREATE TABLE `chat_threads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `coach_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_threads`
--

INSERT INTO `chat_threads` (`id`, `user_id`, `coach_id`, `booking_id`, `class_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, NULL, '2026-06-01 18:02:36', '2026-06-01 19:48:55'),
(2, 3, 1, 6, NULL, '2026-06-01 18:12:44', '2026-06-01 18:15:12'),
(3, 5, 1, NULL, NULL, '2026-06-01 18:37:25', '2026-06-01 18:37:25'),
(4, 5, 2, 7, NULL, '2026-06-01 18:39:17', '2026-06-01 18:39:35'),
(5, 3, 3, 4, NULL, '2026-06-01 19:48:30', '2026-06-01 19:48:30'),
(6, 3, 1, 8, NULL, '2026-06-01 19:50:51', '2026-06-01 19:50:51'),
(7, 3, 2, NULL, 6, '2026-06-01 19:57:59', '2026-06-01 20:11:16');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `instructor` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `schedule` datetime NOT NULL,
  `duration_min` int(11) DEFAULT 60,
  `capacity` int(11) DEFAULT 20,
  `enrolled` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `coach_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `name`, `category`, `instructor`, `description`, `schedule`, `duration_min`, `capacity`, `enrolled`, `image`, `coach_id`) VALUES
(1, 'Boxing Fundamentals', 'Boxing', 'Omar Khaled', 'Beginner-friendly boxing class focused on footwork, punches, defense, and conditioning.', '2026-05-31 18:00:00', 60, 15, 0, NULL, 2),
(2, 'Zumba Energy', 'Zumba', 'Mariam Adel', 'High-energy dance fitness class designed for fat loss, endurance, and fun group training.', '2026-06-01 19:00:00', 60, 20, 0, NULL, 3),
(3, 'CrossFit Conditioning', 'CrossFit', 'Youssef Samir', 'Full-body strength and conditioning workout using functional movements and high intensity circuits.', '2026-06-02 17:00:00', 75, 12, 0, NULL, 4),
(4, 'HIIT Fat Burn', 'HIIT', 'Ahmed Hassan', 'Short intense workout focused on calorie burning, stamina, and bodyweight conditioning.', '2026-06-03 20:00:00', 45, 18, 1, NULL, 1),
(5, 'Yoga & Mobility', 'Yoga', 'Mariam Adel', 'Recovery-focused session for flexibility, breathing, mobility, and injury prevention.', '2026-06-04 10:00:00', 60, 16, 1, NULL, 3),
(6, 'Kickboxing Power', 'Kickboxing', 'Omar Khaled', 'Combination of boxing, kicks, cardio, and strength drills for power and endurance.', '2026-06-05 18:00:00', 60, 14, 1, NULL, 2);

-- --------------------------------------------------------

--
-- Table structure for table `coaches`
--

CREATE TABLE `coaches` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `specialty` varchar(100) NOT NULL,
  `bio` text DEFAULT NULL,
  `achievements` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `price_per_session` decimal(10,2) NOT NULL DEFAULT 0.00,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coaches`
--

INSERT INTO `coaches` (`id`, `name`, `specialty`, `bio`, `achievements`, `image`, `price_per_session`, `user_id`) VALUES
(1, 'Ahmed Hassan', 'Strength & Conditioning', 'Specialized in muscle building, strength training, and athletic performance.', 'National powerlifting competitor. 7 years coaching experience.', NULL, 350.00, 5),
(2, 'Omar Khaled', 'Boxing & Fat Loss', 'Focused on boxing, HIIT, conditioning, and weight loss programs.', 'Former amateur boxing champion. Certified boxing coach.', NULL, 300.00, 6),
(3, 'Mariam Adel', 'Zumba & Functional Fitness', 'Expert in group fitness, Zumba, mobility, and beginner-friendly training.', 'Certified Zumba instructor. 5 years group training experience.', NULL, 250.00, 7),
(4, 'Youssef Samir', 'CrossFit & Endurance', 'Specialized in CrossFit, stamina improvement, and full-body conditioning.', 'CrossFit Level 1 trainer. Regional fitness competitor.', NULL, 400.00, 8);

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `user_id`, `class_id`, `enrolled_at`) VALUES
(1, 1, 4, '2026-05-29 22:04:22'),
(2, 1, 5, '2026-05-29 22:04:41'),
(3, 3, 6, '2026-06-01 19:45:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `plan` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('user','admin','coach') DEFAULT 'user',
  `phone` varchar(30) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `username`, `password`, `plan`, `created_at`, `role`, `phone`, `profile_image`, `reset_token`, `reset_expires`) VALUES
(1, 'Fady Ashraf', 'fady@yahoo.com', 'fady99', '$2y$10$p/l.e7R1vr8YTKEXsL6jZu6RD.EAPfPrhCY7MK3Hijhei42/GxidG', '6months', '2026-05-18 15:44:42', 'admin', '01222246247', 'uploads/profiles/user_1_1780165748.png', 'a1349af611455682adf50d0ae68d79889c7c90a60118b267cd256656229f3a6c', '2026-05-30 21:44:46'),
(2, 'joyce hany', 'joyce@yahoo.com', 'joyce18', '$2y$10$3sV6Wys1nFN6oVP/mkArX.wiz3R1JnfC9BvSC8mr2YXBSFCDIbGx6', '1month', '2026-05-18 16:08:43', 'user', NULL, NULL, NULL, NULL),
(3, 'Rowida Ahmed', 'rowida@yahoo.com', 'rowida22', '$2y$10$7FLkVukAMIj4cy/YURvIzO8iULjzvcuV1kYadZpJ/c83wztGqMM3K', '1year', '2026-05-18 23:44:28', 'admin', '', NULL, 'c6a1abcdc1b8618bb5e381d52f29daa36ae4ba359039fb41ad3a53a28e21fca2', '2026-06-01 23:44:11'),
(4, 'Reem Akram', 'reem@yahoo.com', 'reem22', '$2y$10$eSebTHbNbOEB.26Mic6zKuMxDdFeZ26FLogr./qHE.zmeyK4JOpj2', '1year', '2026-05-30 18:34:40', 'admin', NULL, NULL, NULL, NULL),
(5, 'Ahmed Hassan', 'coach1@test.com', 'coach_ahmed', '$2y$10$VxBup8A5Hmr/bpyqfU/XqOcri/l3Rgnd7UOBpYzlP/VMTlVDe4AvO', '1month', '2026-06-01 16:58:05', 'coach', NULL, NULL, NULL, NULL),
(6, 'Omar Khaled', 'omar.coach@hustlemuscle.local', 'coach_omar', '$2y$10$VxBup8A5Hmr/bpyqfU/XqOcri/l3Rgnd7UOBpYzlP/VMTlVDe4AvO', NULL, '2026-06-01 19:18:26', 'coach', NULL, NULL, NULL, NULL),
(7, 'Mariam Adel', 'mariam.coach@hustlemuscle.local', 'coach_mariam', '$2y$10$VxBup8A5Hmr/bpyqfU/XqOcri/l3Rgnd7UOBpYzlP/VMTlVDe4AvO', NULL, '2026-06-01 19:18:26', 'coach', NULL, NULL, NULL, NULL),
(8, 'Youssef Samir', 'youssef.coach@hustlemuscle.local', 'coach_youssef', '$2y$10$VxBup8A5Hmr/bpyqfU/XqOcri/l3Rgnd7UOBpYzlP/VMTlVDe4AvO', NULL, '2026-06-01 19:18:26', 'coach', NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_coach_slot` (`coach_id`,`booking_date`,`time_slot`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `thread_id` (`thread_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `chat_threads`
--
ALTER TABLE `chat_threads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_class_user_chat` (`class_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `coach_id` (`coach_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_class_coach` (`coach_id`);

--
-- Indexes for table `coaches`
--
ALTER TABLE `coaches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_coach_user` (`user_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_double_enroll` (`user_id`,`class_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `chat_threads`
--
ALTER TABLE `chat_threads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `coaches`
--
ALTER TABLE `coaches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`coach_id`) REFERENCES `coaches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `chat_threads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_threads`
--
ALTER TABLE `chat_threads`
  ADD CONSTRAINT `chat_threads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_threads_ibfk_2` FOREIGN KEY (`coach_id`) REFERENCES `coaches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_threads_ibfk_3` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `chat_threads_ibfk_4` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `fk_class_coach` FOREIGN KEY (`coach_id`) REFERENCES `coaches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `coaches`
--
ALTER TABLE `coaches`
  ADD CONSTRAINT `fk_coach_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
