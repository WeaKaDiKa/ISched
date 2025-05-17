-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2025 at 07:57 PM
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
-- Database: `dental_clinic`
--

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `region` varchar(50) NOT NULL,
  `region_name` varchar(100) DEFAULT NULL,
  `province` varchar(50) NOT NULL,
  `province_name` varchar(100) DEFAULT NULL,
  `city` varchar(50) NOT NULL,
  `city_name` varchar(100) DEFAULT NULL,
  `barangay` varchar(50) NOT NULL,
  `barangay_name` varchar(100) DEFAULT NULL,
  `zip_code` varchar(10) NOT NULL,
  `date_of_birth` date NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_img` varchar(255) DEFAULT 'profile-placeholder.jpg',
  `profile_picture` varchar(255) NOT NULL DEFAULT 'profile-placeholder.jpg',
  `role` enum('dentist','dental_helper','user') NOT NULL DEFAULT 'user',
  `otp` varchar(10) DEFAULT NULL,
  `otp_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `first_name`, `middle_name`, `last_name`, `email`, `phone_number`, `region`, `region_name`, `province`, `province_name`, `city`, `city_name`, `barangay`, `barangay_name`, `zip_code`, `date_of_birth`, `password_hash`, `gender`, `created_at`, `profile_img`, `profile_picture`, `role`, `otp`, `otp_expires`) VALUES
(3, 'joseph', 'speed', 'watkinson', 'marcgermine2003@gmail.com', '09776907092', 'NCR', NULL, 'Metro Manila', NULL, 'Quezon City', NULL, 'Batasan Hills', NULL, '1126', '2003-10-29', '$2y$10$8iK/7vt5W1culiBO6YyA8Ohh2TfawU4ncfYuSfsdEUyul8V61kGFK', 'Male', '2025-03-28 15:14:01', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', NULL, NULL),
(4, 'Marc Germine', 'Panizales', 'Ganan', 'marcgermineganan05@gmail.com', '09776907092', 'Region III (Central Luzon)', NULL, 'Pampanga', NULL, 'Angeles City', NULL, 'Balibago', NULL, '1126', '2003-10-29', '$2y$10$fOXGahW2OnUIW2sXN81PgecG1GbwiTJcDwt1AMO/TlxUJBB5r9N9u', 'Male', '2025-03-28 15:49:22', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', NULL, NULL),
(6, 'ichigo', 'pirate', 'bankai', 'marcgermineganan2003@gmail.com', '09776907092', 'Region IV-A (Calabarzon)', NULL, 'Cavite', NULL, 'Dasmariñas', NULL, 'Burol', NULL, '1126', '2003-10-29', '$2y$10$FSl8D4laFLOiAWrJohrI.OlmQ1EEc4Kn1xqpzrsiNqmyfUtrr14sy', 'Male', '2025-04-10 10:17:43', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', NULL, NULL),
(7, 'ichigo', 'pirate', 'bankai', 'marcgermineganan03@gmail.com', '09776907092', 'NCR', NULL, 'Metro Manila', NULL, 'Quezon City', NULL, 'Batasan Hills', NULL, '1126', '2003-10-29', '$2y$10$.P9l00L9Nep7dvP9jNrU5OVlqmLZNJIAeNI4hhefGA1D.ofmasRle', 'Male', '2025-04-10 15:05:49', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', NULL, NULL),
(8, 'Juanito', 'Mendano', 'De La', 'test@gmail.com', '09123456782', 'NCR', NULL, 'Metro Manila', NULL, 'Manila', NULL, 'Barangay uno', NULL, '2341', '2013-02-22', '$2y$10$2yeUvFIVnk.WtJNrB73OdeAMXh0zFlgorJKFzXGZC8cUAwkytIYCi', 'Male', '2025-04-12 19:00:57', 'profile-placeholder.jpg', 'profile_8_1744670552.jpg', 'dentist', NULL, NULL),
(9, 'Juan', 'sad', 'sasf', 'test2@gmail.com', '09123456783', 'NCR', NULL, 'Metro Manila', NULL, 'Manila', NULL, 'Barangay 1', NULL, '1234', '2024-10-30', '$2y$10$PWkn4RlBF14iUPzCgxIPh.PPNFgEppWPlSnrey1dqH2ds.3SkJ0Dy', 'Male', '2025-04-12 19:25:17', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', NULL, NULL),
(10, 'lei', 'mendao', 'De La', 'test3@gmail.com', '09123456783', 'NCR', NULL, 'Metro Manila', NULL, 'Quezon City', NULL, 'Commonwealth', NULL, '2341', '2024-07-10', '$2y$10$gOEi2Q/WoE.6no0XpJnmF.cKuKPG0UnV2T8D0N3x29n7lDhdvalw.', 'Male', '2025-04-12 19:53:19', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', NULL, NULL),
(11, 'bo', 'l', 'bo', 'test4@gmail.com', '09123456782', 'NCR', NULL, '', NULL, 'Quezon City', NULL, 'Commonwealth', NULL, '2341', '2019-05-17', '$2y$10$UXloN3Jiws9t1PSJudFbrOi7GGxVubGPj3A.FdB61EpqugLuZMJiG', 'Female', '2025-04-26 13:04:07', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', NULL, NULL),
(12, 'Yuki', 'happy', 'De La', 'a5kb2ld3wd@ibolinva.com', '09123456783', '2', NULL, '68', NULL, '1395', NULL, '37386', NULL, '2314', '2025-04-02', '$2y$10$PpSja1evmeNdJnbgZtxw3uQ.biEIfUTKrD4n0Mt1soXuxLKhN5d7G', 'Male', '2025-04-28 22:27:51', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', NULL, NULL),
(13, 'leon', 'kenn', 'dey', 'rlz0ez94yo@qacmjeq.com', '09123456783', '3', NULL, '3', NULL, '25', NULL, '570', NULL, '2134', '2025-04-10', '$2y$10$/DnDDyGcFcpblWZqvrB1DOoZ0NjY8y3RJ/Qe7Dtk3sGYQQKUqDFOq', 'Male', '2025-04-28 22:43:03', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', NULL, NULL),
(14, 'kuan', 'nendano', 'dea', 'm2p2zxzaed@bltiwd.com', '09123456782', '2', NULL, '71', NULL, '1430', NULL, '38020', NULL, '1462', '2014-01-30', '$2y$10$mOtT0w8KKCJWr.S4nhbsMODgoStgOlSz279M8MDHqZFt3ac775SSO', 'Male', '2025-04-29 06:37:01', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', NULL, NULL),
(15, 'snej', 'yuki', 'Cruz', 'keemj5avy3@ibolinva.com', '09123456783', '16', NULL, '81', NULL, '1610', NULL, '41660', NULL, '5433', '2025-04-01', '$2y$10$hB3XjsvE.Jy7/hRVku76HubitSa/qXog3VDv25pytxTccA9Fgpy4u', 'Male', '2025-04-29 08:38:56', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'dentist', NULL, NULL),
(16, 'Juanito', 'mendao', 'sada', '113220uobw@wyoxafp.com', '09123456782', '1', NULL, '1', NULL, '1357', NULL, '36685', NULL, '2341', '2004-10-30', '$2y$10$.GSUbiBGAd7do31wwnnN2uyrKdGkS4BV8RwQ9lcMXAB4PJRb5415u', 'Male', '2025-04-30 10:38:22', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', NULL, NULL),
(17, 'Juan', 'Mendano', 'De La', 'vzyne3mif6@qejjyl.com', '09123456783', '1', NULL, '1', NULL, '1354', NULL, '36466', NULL, '2342', '2004-02-29', '$2y$10$x1VkQ.qdNqqNX1wHLweMdOSLYYTvNMSGnGRzmYu0RmDQHdZh0G6.K', 'Male', '2025-04-30 15:44:38', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', NULL, NULL),
(18, 'dfgfdg', 'dfgfdg', 'dfgfdg', 'mikaysomera16@gmail.com', '09123456782', '4', NULL, '6', NULL, '127', NULL, '3275', NULL, '2341', '2002-01-30', '$2y$10$phX/2tJkpQIzafU1L14syeUiecTrFDUTQd0arGPQSn9R9XFhvP/v2', 'Female', '2025-04-30 15:47:41', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'dentist', NULL, NULL),
(19, 'Julio', 'Butaw', 'Tanga', 'y2srj06mdt@daouse.com', '09934566744', '3', NULL, '4', NULL, '61', NULL, '1450', NULL, '5346', '2003-06-20', '$2y$10$9s6WrgFIfhydJPRZptBUyuPX4XugM3UliOJDhU.aU9ME1D5Ew99xG', 'Female', '2025-05-03 06:01:49', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', NULL, NULL),
(22, 'Royce', 'Du', 'Pont', 'sanelop542@magpit.com', '09123456782', '1', 'National Capital Region', '1', 'Metro Manila', '1352', 'Marikina City', '36282', 'Barangka', '1234', '2003-06-19', '$2y$10$2ibYNYIvlqKYwvKKrNZn5eiquCrH6W25B/y7ZdVCXyp/UoED9wiAC', 'Male', '2025-05-11 15:19:07', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', '763215', '2025-05-11 17:35:16'),
(23, 'Royce', 'Yuki', 'Nicka', 'lp1nqd8hvu@mkzaso.com', '09123456783', '2', NULL, '67', NULL, '1367', NULL, '37069', NULL, '2341', '2003-09-24', '$2y$10$jGZWyRrFk3ua0JgOFvAQAORVKOa0ekOiGU3PaB6SEA4HOpPNITAVO', 'Male', '2025-05-11 16:06:00', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', NULL, NULL),
(24, 'Sd', 'Ad', 'Sada', 'pu3puvhjvp@smykwb.com', '09123456783', '2', 'Cordillera Administrative Region', '67', 'Abra', '1367', 'Bangued', '37069', '', '5436', '2004-02-11', '$2y$10$YWnXx33wPs/QxW/LGsIOd.DmyYrVa5VdEAkXVDrdpd3O64sPLOTaq', 'Male', '2025-05-11 16:39:55', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', NULL, NULL),
(25, 'Juan', 'Du', 'Dfgfd', 'gwnq8g49ff@dygovil.com', '09123456782', '2', 'Cordillera Administrative Region', '67', 'Abra', '1369', 'Bucay', '37117', '', '1234', '2003-10-12', '$2y$10$/oFXd64.QHUqarbAGoSKleIAWm5/5vxNp8DOM78lgS3pUQ/YH/4vm', 'Male', '2025-05-11 16:49:32', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', NULL, NULL),
(26, 'A', 'S', 'V', 'icif6sv03j@illubd.com', '09123456782', '7', 'MIMAROPA', '24', 'Occidental Mindoro', '498', 'Calintaan', '12929', '', '1234', '2003-06-13', '$2y$10$mqZjM2X159.TCuhGGHt8G.H.uoS7CVC.TQzKDn//H/GGdlb8zKAh.', 'Male', '2025-05-11 16:57:44', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', NULL, NULL),
(27, 'Ass', 'Fsd', 'Ds', 'sdsxjskw3q@jkotypc.com', '09123456782', '1', 'National Capital Region', '1', 'Metro Manila', '1353', 'Pasig City', 'undefined', 'Adams (Pob.)', '5341', '1990-11-14', '$2y$10$5aunBeRxbMBgNHeLXeXzyee/jypXoih7OXcR2cawKbvy31jY2OeIq', 'Male', '2025-05-11 17:04:43', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', NULL, NULL),
(28, 'Sad', 'Sdfg', 'Sdg', 'ob9lcciylz@smykwb.com', '09123456782', '2', 'Cordillera Administrative Region', '67', 'Abra', '1368', 'Boliney', 'undefined', '', '2341', '2005-06-07', '$2y$10$nO55jZvl0D2gQU9VBbSIQe2RdXycVjs4NicbAjPNu/uEVCLpVUdAy', 'Male', '2025-05-11 17:25:49', 'profile-placeholder.jpg', 'profile-placeholder.jpg', 'user', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_patients_otp_expires` (`otp_expires`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
