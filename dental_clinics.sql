-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2025 at 07:13 PM
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
-- Table structure for table `access_requests`
--

CREATE TABLE `access_requests` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `role_requested` varchar(50) NOT NULL,
  `status` enum('Pending','Approved','Denied') DEFAULT 'Pending',
  `submitted_on` datetime DEFAULT current_timestamp(),
  `submitted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_logins`
--

CREATE TABLE `admin_logins` (
  `id` int(11) NOT NULL,
  `admin_id` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logins`
--

INSERT INTO `admin_logins` (`id`, `admin_id`, `email`, `password`, `name`, `first_name`, `last_name`, `age`, `mobile`, `gender`, `created_at`, `profile_photo`) VALUES
(1, 'ADM-001', 'marcgermineganan05@gmail.com', '$2y$10$nDH4.xj0Vt1ABnCzeubiDOAOrFuOrZctSNuMyr4fxuEackSvR/ShG', 'Marc Germine Ganan', 'Marc', 'Ganan', 21, '1234567890', 'Male', '2025-04-30 11:19:14', 'assets/photo/admin_ADM-001_1746725162.jpg'),
(2, 'ADM-002', 'marcgermineganan03@gmail.com', '$2y$10$Ce1EOPVYypFgcjrZ3B5zyONRnT3.1hdjAe/oXi8.6ElSiEnObONoi', 'Mc Andray Ganan', 'Mc Andray', 'Ganan', 18, '09776907092', 'Male', '2025-05-03 17:11:39', NULL),
(3, 'ADM-003', 'marcgermineganan2003@gmail.com', '$2y$10$LBCcvJ/dVoUJYNv4QaWdeuIxg2FU7k4JFkOxH3ODz4Kd/U6qAhffa', 'Lebron James', 'Lebron', 'Jame', 18, '09776907092', 'Male', '2025-05-04 16:42:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `clinic_branch` varchar(255) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` varchar(20) NOT NULL,
  `services` text NOT NULL,
  `status` enum('pending','booked','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `health` varchar(10) DEFAULT NULL,
  `pregnant` varchar(10) DEFAULT NULL,
  `nursing` varchar(10) DEFAULT NULL,
  `birth_control` varchar(10) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `consent` tinyint(1) NOT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `dental_insurance` varchar(100) DEFAULT NULL,
  `previous_dentist` varchar(100) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `parent_appointment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `clinic_branch`, `appointment_date`, `appointment_time`, `services`, `status`, `created_at`, `health`, `pregnant`, `nursing`, `birth_control`, `blood_pressure`, `blood_type`, `medical_history`, `allergies`, `consent`, `religion`, `nationality`, `occupation`, `dental_insurance`, `previous_dentist`, `doctor_id`, `parent_appointment_id`) VALUES
(44, 11, 'Commonwealth Branch', '2025-04-28', '10:00 am', 'Dental Bridges, Dental Check-ups & Consultation', 'booked', '2025-04-27 05:05:02', 'yes', NULL, NULL, NULL, NULL, 'A+', 'Heart Attack, Thyroid Problem, Heart Disease', 'Penicillin', 1, NULL, NULL, NULL, NULL, NULL, 15, NULL),
(46, 8, 'Commonwealth Branch', '2025-04-28', '11:00 am', 'Dental Bridges, Dental Check-ups & Consultation', 'booked', '2025-04-27 05:08:02', 'yes', NULL, NULL, NULL, NULL, 'A-', 'Heart Attack, Thyroid Problem', 'Local Anesthetic, Penicillin', 1, NULL, NULL, NULL, NULL, NULL, 15, NULL),
(48, 8, 'Commonwealth Branch', '2025-04-28', '01:00 pm', 'Dental Bridges, Dental Check-ups & Consultation', 'booked', '2025-04-27 05:40:21', 'yes', NULL, NULL, NULL, NULL, 'A+', 'Thyroid Problem, Heart Disease', 'Local Anesthetic, Penicillin, Sulfa Drugs', 1, NULL, NULL, NULL, NULL, NULL, 15, NULL),
(49, 9, 'Commonwealth Branch', '2025-04-29', '10:00 am', 'Dental Bridges, Dental Check-ups & Consultation', 'cancelled', '2025-04-27 05:41:14', 'yes', NULL, NULL, NULL, NULL, 'A+', 'Thyroid Problem, Heart Disease, Diabetes, Asthma', 'Penicillin, Sulfa Drugs', 1, NULL, NULL, NULL, NULL, NULL, 15, NULL),
(50, 9, 'Commonwealth Branch', '2025-04-28', '06:00 pm', 'Dental Bridges, Dental Check-ups & Consultation', 'booked', '2025-04-27 07:06:19', 'yes', NULL, NULL, NULL, NULL, 'A+', 'Thyroid Problem, Heart Disease', 'Local Anesthetic, Penicillin', 1, NULL, NULL, NULL, NULL, NULL, 15, NULL),
(51, 9, 'Commonwealth Branch', '2025-04-29', '03:00 pm', 'Dental Bridges, Dental Check-ups & Consultation', 'booked', '2025-04-27 09:04:27', 'yes', NULL, NULL, NULL, NULL, 'A+', 'Heart Attack, Thyroid Problem', 'Local Anesthetic, Penicillin, Sulfa Drugs', 1, NULL, NULL, NULL, NULL, NULL, 15, NULL),
(52, 9, 'North Fairview Branch', '2025-04-29', '10:00 am', 'Dental Bridges, Dental Check-ups & Consultation, Dental Emergency Care', 'booked', '2025-04-27 10:14:35', 'yes', NULL, NULL, NULL, NULL, 'A-', 'Heart Attack, Thyroid Problem, Heart Disease', 'Local Anesthetic, Penicillin', 1, NULL, NULL, NULL, NULL, NULL, 12, NULL),
(53, 9, 'Commonwealth Branch', '2025-05-02', '11:00 AM', 'Dental Bridges, Dental Check-ups & Consultation', 'booked', '2025-04-27 10:44:31', '', NULL, NULL, NULL, NULL, 'A+', 'Thyroid Problem, Heart Disease, Diabetes, Asthma', 'Penicillin, Sulfa Drugs', 1, NULL, NULL, NULL, NULL, NULL, 15, 49),
(54, 9, 'Commonwealth Branch', '2025-04-28', '02:00 pm', 'Dental Bridges, Dental Check-ups & Consultation', 'booked', '2025-04-27 10:46:20', 'yes', NULL, NULL, NULL, NULL, 'A-', 'Heart Attack, Thyroid Problem', 'Penicillin, Sulfa Drugs', 1, NULL, NULL, NULL, NULL, NULL, 15, NULL),
(55, 9, 'Commonwealth Branch', '2025-04-29', '01:00 pm', 'Dental Bridges, Dental Check-ups & Consultation, Dental Crown', 'booked', '2025-04-27 14:54:22', 'yes', NULL, NULL, NULL, NULL, 'A+', 'Heart Attack, Thyroid Problem', 'Local Anesthetic, Penicillin', 1, NULL, NULL, NULL, NULL, NULL, 2, NULL),
(56, 9, 'Commonwealth Branch', '2025-04-28', '05:00 pm', 'Dental Bridges, Dental Check-ups & Consultation', 'booked', '2025-04-27 15:33:06', 'yes', NULL, NULL, NULL, NULL, 'A-', 'Heart Attack, Thyroid Problem', 'Local Anesthetic, Penicillin', 1, NULL, NULL, NULL, NULL, NULL, 15, NULL),
(57, 9, 'Commonwealth Branch', '2025-04-29', '01:00 pm', 'Dental Bridges, Dental Check-ups & Consultation', 'booked', '2025-04-27 15:37:03', 'yes', NULL, NULL, NULL, NULL, 'A+', 'Heart Attack, Thyroid Problem', 'Local Anesthetic, Penicillin', 1, NULL, NULL, NULL, NULL, NULL, 15, NULL),
(58, 9, 'Commonwealth Branch', '2025-04-28', '04:00 pm', 'Dental Bridges, Dental Check-ups & Consultation', 'booked', '2025-04-27 15:46:28', 'yes', NULL, NULL, NULL, NULL, 'A-', 'Heart Attack, Thyroid Problem', 'Local Anesthetic, Penicillin', 1, NULL, NULL, NULL, NULL, NULL, 15, NULL),
(60, 9, 'Commonwealth Branch', '2025-04-28', '07:00 pm', 'Dental Bridges, Dental Check-ups & Consultation', 'booked', '2025-04-27 16:08:33', 'yes', NULL, NULL, NULL, NULL, 'A+', 'Heart Attack, Thyroid Problem', 'Local Anesthetic, Penicillin, Sulfa Drugs', 1, NULL, NULL, NULL, NULL, NULL, 15, NULL),
(61, 8, 'Commonwealth Branch', '2025-04-28', '03:00 pm', 'Dental Bridges, Dental Check-ups & Consultation', 'booked', '2025-04-27 16:14:11', 'yes', NULL, NULL, NULL, NULL, 'A-', 'Heart Attack, Thyroid Problem', 'Local Anesthetic, Penicillin', 1, NULL, NULL, NULL, NULL, NULL, 15, NULL),
(62, 8, 'Commonwealth Branch', '2025-04-28', '10:00 am', 'Dental Bridges, Dental Check-ups & Consultation', 'booked', '2025-04-27 16:29:05', 'yes', NULL, NULL, NULL, NULL, 'B+', 'Heart Attack, Thyroid Problem, Heart Disease', 'Penicillin, Sulfa Drugs', 1, NULL, NULL, NULL, NULL, NULL, 2, NULL),
(63, 9, 'Test Branch', '2025-05-01', '10:00 am', 'Test Service', 'cancelled', '2025-04-27 16:31:49', 'yes', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(64, 8, 'North Fairview Branch', '2025-04-28', '11:00 am', 'Dental Bridges, Dental Check-ups & Consultation', 'booked', '2025-04-27 16:35:13', 'yes', NULL, NULL, NULL, NULL, 'A+', 'Thyroid Problem, Heart Disease', 'Local Anesthetic, Penicillin', 1, NULL, NULL, NULL, NULL, NULL, 3, NULL),
(65, 8, 'Commonwealth Branch', '2025-04-29', '11:00 am', 'Dental Bridges, Dental Check-ups & Consultation', 'booked', '2025-04-27 16:40:05', 'yes', NULL, NULL, NULL, NULL, 'A+', 'Heart Attack, Thyroid Problem', 'Local Anesthetic, Penicillin, Sulfa Drugs', 1, NULL, NULL, NULL, NULL, NULL, 15, NULL),
(66, 8, 'Commonwealth Branch', '2025-04-29', '05:00 pm', 'Dental Check-ups & Consultation', 'booked', '2025-04-27 16:52:45', 'yes', NULL, NULL, NULL, NULL, 'A+', 'Thyroid Problem, Heart Disease', 'Penicillin, Sulfa Drugs', 1, NULL, NULL, NULL, NULL, NULL, 15, NULL),
(67, 8, 'Commonwealth Branch', '2025-04-29', '10:00 am', 'Dental Bridges, Dental Check-ups & Consultation', 'booked', '2025-04-27 16:56:34', 'yes', NULL, NULL, NULL, NULL, 'A+', 'Heart Attack, Thyroid Problem, Heart Disease', 'Local Anesthetic, Penicillin, Sulfa Drugs', 1, NULL, NULL, NULL, NULL, NULL, 15, NULL),
(68, 8, 'Commonwealth Branch', '2025-04-30', '02:00 pm', 'Dental Crown', 'booked', '2025-04-27 17:00:31', 'yes', NULL, NULL, NULL, NULL, 'A-', 'Heart Attack, Thyroid Problem, Heart Disease', 'Local Anesthetic, Penicillin', 1, NULL, NULL, NULL, NULL, NULL, 15, NULL),
(69, 8, 'Commonwealth Branch', '2025-04-29', '02:00 pm', 'Dental Bridges, Dental Check-ups & Consultation', 'booked', '2025-04-27 17:27:04', 'yes', NULL, NULL, NULL, NULL, 'A+', 'Heart Attack, Thyroid Problem', 'Local Anesthetic, Penicillin', 1, NULL, NULL, NULL, NULL, NULL, 15, NULL),
(70, 8, 'Commonwealth Branch', '2025-04-29', '02:00 pm', 'Dental Bridges, Dental Check-ups & Consultation', 'booked', '2025-04-28 18:05:14', 'yes', NULL, NULL, NULL, NULL, 'A-', 'Heart Attack, Thyroid Problem, Heart Disease, Diabetes', 'Local Anesthetic, Penicillin', 1, NULL, NULL, NULL, NULL, NULL, 2, NULL),
(71, 9, 'North Fairview Branch', '2025-05-05', '01:00 pm', 'Dental Bridges, Dental Check-ups & Consultation', '', '2025-05-02 16:58:45', NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 12, NULL),
(72, 4, 'Commonwealth Branch', '2025-05-08', '11:00 am', 'Dental Bridges', 'booked', '2025-05-06 15:41:38', NULL, NULL, NULL, NULL, NULL, '', '', '', 0, NULL, NULL, NULL, NULL, NULL, 15, NULL),
(73, 4, 'Commonwealth Branch', '2025-05-08', '03:00 pm', 'Dental Bridges', '', '2025-05-08 06:15:46', NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 15, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `dentists`
--

CREATE TABLE `dentists` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `specialty` varchar(255) DEFAULT NULL,
  `status` enum('online','offline') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `specialization` varchar(100) NOT NULL,
  `clinic_branch` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `first_name`, `last_name`, `specialization`, `clinic_branch`, `is_active`, `created_at`) VALUES
(1, 'Maria', 'Santos', 'General Dentistry', 'Commonwealth Branch', 1, '2025-04-23 08:32:57'),
(2, 'Juan', 'Reyes', 'Orthodontics', 'Commonwealth Branch', 1, '2025-04-23 08:32:57'),
(3, 'Ana', 'Garcia', 'Pediatric Dentistry', 'North Fairview Branch', 1, '2025-04-23 08:32:57'),
(4, 'Pedro', 'Lim', 'Oral Surgery', 'North Fairview Branch', 1, '2025-04-23 08:32:57'),
(5, 'Sofia', 'Cruz', 'Periodontics', 'Maligaya Park Branch', 1, '2025-04-23 08:32:57'),
(6, 'Miguel', 'De Guzman', 'Endodontics', 'Maligaya Park Branch', 1, '2025-04-23 08:32:57'),
(7, 'Camila', 'Tan', 'Prosthodontics', 'San Isidro Branch', 1, '2025-04-23 08:32:57'),
(8, 'Gabriel', 'Morales', 'Cosmetic Dentistry', 'San Isidro Branch', 1, '2025-04-23 08:32:57'),
(9, 'Isabella', 'Navarro', 'General Dentistry', 'Quiapo Branch', 1, '2025-04-23 08:32:57'),
(10, 'Mateo', 'Villanueva', 'Orthodontics', 'Quiapo Branch', 1, '2025-04-23 08:32:57'),
(11, 'Valentina', 'Ramos', 'Pediatric Dentistry', 'Kiko Branch', 1, '2025-04-23 08:32:57'),
(12, 'Daniel', 'Dela Cruz', 'Oral Surgery', 'North Fairview Branch', 1, '2025-04-23 08:32:57'),
(13, 'Olivia', 'Torres', 'Periodontics', 'Bagong Silang Branch', 1, '2025-04-23 08:32:57'),
(14, 'Sebastian', 'Lopez', 'Endodontics', 'Bagong Silang Branch', 1, '2025-04-23 08:32:57'),
(15, 'bobo', 'De La', 'Periodontics', 'Commonwealth Branch', 1, '2025-04-25 16:52:58');

-- --------------------------------------------------------

--
-- Table structure for table `medical_history`
--

CREATE TABLE `medical_history` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `heart_disease` enum('Yes','No') DEFAULT 'No',
  `diabetes` enum('Yes','No') DEFAULT 'No',
  `current_medications` text DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `last_physical_exam` date DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `role` enum('dentist','dental_helper','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_img` varchar(255) DEFAULT 'profile-placeholder.jpg',
  `profile_picture` varchar(255) NOT NULL DEFAULT 'profile-placeholder.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `first_name`, `middle_name`, `last_name`, `email`, `phone_number`, `region`, `region_name`, `province`, `province_name`, `city`, `city_name`, `barangay`, `barangay_name`, `zip_code`, `date_of_birth`, `password_hash`, `gender`, `role`, `created_at`, `profile_img`, `profile_picture`) VALUES
(3, 'joseph', 'speed', 'watkinson', 'marcgermine2003@gmail.com', '09776907092', 'NCR', NULL, 'Metro Manila', NULL, 'Quezon City', NULL, 'Batasan Hills', NULL, '1126', '2003-10-29', '$2y$10$8iK/7vt5W1culiBO6YyA8Ohh2TfawU4ncfYuSfsdEUyul8V61kGFK', 'Male', 'user', '2025-03-28 15:14:01', 'profile-placeholder.jpg', 'profile-placeholder.jpg'),
(4, 'Marc Germine', 'Panizales', 'Ganan', 'marcgermineganan05@gmail.com', '09776907092', 'Region III (Central Luzon)', NULL, 'Pampanga', NULL, 'Angeles City', NULL, 'Balibago', NULL, '1126', '2003-10-29', '$2y$10$fOXGahW2OnUIW2sXN81PgecG1GbwiTJcDwt1AMO/TlxUJBB5r9N9u', 'Male', 'user', '2025-03-28 15:49:22', 'profile-placeholder.jpg', 'user_4.jpg'),
(6, 'ichigo', 'pirate', 'bankai', 'marcgermineganan2003@gmail.com', '09776907092', 'Region IV-A (Calabarzon)', NULL, 'Cavite', NULL, 'Dasmariñas', NULL, 'Burol', NULL, '1126', '2003-10-29', '$2y$10$FSl8D4laFLOiAWrJohrI.OlmQ1EEc4Kn1xqpzrsiNqmyfUtrr14sy', 'Male', 'user', '2025-04-10 10:17:43', 'profile-placeholder.jpg', 'profile-placeholder.jpg'),
(7, 'ichigo', 'pirate', 'bankai', 'marcgermineganan03@gmail.com', '09776907092', 'NCR', NULL, 'Metro Manila', NULL, 'Quezon City', NULL, 'Batasan Hills', NULL, '1126', '2003-10-29', '$2y$10$.P9l00L9Nep7dvP9jNrU5OVlqmLZNJIAeNI4hhefGA1D.ofmasRle', 'Male', 'user', '2025-04-10 15:05:49', 'profile-placeholder.jpg', 'profile-placeholder.jpg'),
(8, 'Juanito', 'Mendano', 'De La', 'test@gmail.com', '09123456782', 'NCR', NULL, 'Metro Manila', NULL, 'Manila', NULL, 'Barangay uno', NULL, '2341', '2013-02-22', '$2y$10$2yeUvFIVnk.WtJNrB73OdeAMXh0zFlgorJKFzXGZC8cUAwkytIYCi', 'Male', 'user', '2025-04-12 19:00:57', 'profile-placeholder.jpg', 'profile_8_1744670552.jpg'),
(9, 'Juan', 'sad', 'sasf', 'test2@gmail.com', '09123456783', 'NCR', NULL, 'Metro Manila', NULL, 'Manila', NULL, 'Barangay 1', NULL, '1234', '2024-10-30', '$2y$10$PWkn4RlBF14iUPzCgxIPh.PPNFgEppWPlSnrey1dqH2ds.3SkJ0Dy', 'Male', 'user', '2025-04-12 19:25:17', 'profile-placeholder.jpg', 'profile-placeholder.jpg'),
(10, 'lei', 'mendao', 'De La', 'test3@gmail.com', '09123456783', 'NCR', NULL, 'Metro Manila', NULL, 'Quezon City', NULL, 'Commonwealth', NULL, '2341', '2024-07-10', '$2y$10$gOEi2Q/WoE.6no0XpJnmF.cKuKPG0UnV2T8D0N3x29n7lDhdvalw.', 'Male', 'user', '2025-04-12 19:53:19', 'profile-placeholder.jpg', 'profile-placeholder.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `patient_profiles`
--

CREATE TABLE `patient_profiles` (
  `patient_id` int(11) NOT NULL,
  `medical_history` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `insurance_info` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_profiles`
--

INSERT INTO `patient_profiles` (`patient_id`, `medical_history`, `created_at`, `insurance_info`) VALUES
(4, NULL, '2025-04-06 17:11:22', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pending_patients`
--

CREATE TABLE `pending_patients` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `region` varchar(255) DEFAULT NULL,
  `region_name` varchar(100) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `province_name` varchar(100) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `city_name` varchar(100) DEFAULT NULL,
  `barangay` varchar(255) DEFAULT NULL,
  `barangay_name` varchar(100) DEFAULT NULL,
  `zip_code` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `role` enum('dentist','dental_helper','user') DEFAULT 'user',
  `otp` varchar(10) DEFAULT NULL,
  `otp_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `remember_me_tokens`
--

CREATE TABLE `remember_me_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `remember_me_tokens`
--

INSERT INTO `remember_me_tokens` (`id`, `user_id`, `token`, `expires`, `created_at`) VALUES
(1, 18, '61d06d33a896137e911582ebbec1c9484429b320a31df1abb673c4ace2337128', '2025-05-30 17:48:00', '2025-04-30 15:48:00');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `text` text NOT NULL,
  `services` text NOT NULL,
  `date` datetime NOT NULL,
  `is_seen` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `patient_id`, `name`, `profile_picture`, `rating`, `text`, `services`, `date`, `is_seen`) VALUES
(7, 11, 'Mikaela Somera', NULL, 5, 'Napakagandang serbisyo! Mabilis at magalang ang staff. Highly recommended!', '[\"Metal Braces \\/ Ceramic Braces\"]', '2025-04-30 17:11:50', 1),
(8, 11, 'Mikaela Somera', NULL, 4, 'Okay naman, pero medyo matagal maghintay. Maayos naman ang treatment', '[\"Teeth Whitening\"]', '2025-04-30 17:12:46', 1),
(9, 11, 'Anonymous', NULL, 3, 'Average lang. Maayos ang dentist pero medyo masakit ang procedure.', '[\"Dental Bonding\"]', '2025-04-30 17:13:29', 1),
(10, 11, 'Anonymous', NULL, 5, 'maganda ang service', '[\"Dental Check-ups & Consultation\"]', '2025-04-30 17:22:36', 1),
(11, 11, 'Mikaela Somera', NULL, 4, 'masakit ang ngipin ko pero maganda ang service', '[\"Tooth Extraction\"]', '2025-04-30 17:24:17', 1),
(12, 11, 'Anonymous', NULL, 2, 'bakit ganyan service niyo!! ang bagal!', '[\"Gum Treatment and Gingivectomy (Periodontal Care)\"]', '2025-05-01 00:03:47', 1),
(13, 4, 'Marc Germine Ganan', NULL, 5, 'grabe ganda', '[\"Tooth Extraction\"]', '2025-05-08 00:26:34', 1);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `price`, `description`, `is_active`, `created_at`) VALUES
(1, 'Dental Check-ups & Consultation', 500.00, 'Regular dental check-up and consultation with our professionals', 1, '2025-04-23 16:32:57'),
(2, 'Dental Crown', 8000.00, 'Custom-made dental crowns to restore damaged teeth', 1, '2025-04-23 16:32:57'),
(3, 'Intraoral X-ray', 300.00, 'Detailed X-ray images of individual teeth', 1, '2025-04-23 16:32:57'),
(4, 'Teeth Cleaning (Oral Prophylaxis)', 1500.00, 'Professional teeth cleaning and plaque removal', 1, '2025-04-23 16:32:57'),
(5, 'Dental Bridges', 12000.00, 'Fixed prosthetic device to replace missing teeth', 1, '2025-04-23 16:32:57'),
(6, 'Panoramic X-ray/Full Mouth X-Ray', 1500.00, 'Complete view of your entire mouth in a single image', 1, '2025-04-23 16:32:57'),
(7, 'Tooth Extraction', 2000.00, 'Removal of damaged or problematic teeth', 1, '2025-04-23 16:32:57'),
(8, 'Dentures (Partial & Full)', 15000.00, 'Removable replacements for missing teeth', 1, '2025-04-23 16:32:57'),
(9, 'TMJ Treatment', 5000.00, 'Treatment for temporomandibular joint disorders', 1, '2025-04-23 16:32:57'),
(10, 'Dental Fillings (Composite)', 1500.00, 'Tooth-colored fillings to repair cavities', 1, '2025-04-23 16:32:57'),
(11, 'Root Canal Treatment', 8000.00, 'Procedure to treat infected tooth pulp', 1, '2025-04-23 16:32:57'),
(12, 'Teeth Whitening', 6000.00, 'Professional teeth whitening treatment', 1, '2025-04-23 16:32:57'),
(13, 'Orthodontic Braces', 40000.00, 'Braces to correct teeth alignment and bite issues', 1, '2025-04-23 16:32:57'),
(14, 'Dental Implant', 50000.00, 'Artificial tooth roots to support replacement teeth', 1, '2025-04-23 16:32:57'),
(15, 'Gum Surgery', 10000.00, 'Surgical procedures to treat gum disease', 1, '2025-04-23 16:32:57'),
(16, 'Wisdom Tooth Extraction', 5000.00, 'Removal of wisdom teeth', 1, '2025-04-23 16:32:57'),
(17, 'Pediatric Dental Care', 1000.00, 'Specialized dental care for children', 1, '2025-04-23 16:32:57'),
(18, 'Dental Veneers', 10000.00, 'Thin shells to improve the appearance of front teeth', 1, '2025-04-23 16:32:57'),
(19, 'Night Guard', 4500.00, 'Custom guard to protect teeth during sleep', 1, '2025-04-23 16:32:57'),
(20, 'Dental Sealants', 800.00, 'Protective coating for teeth to prevent decay', 1, '2025-04-23 16:32:57'),
(21, 'Full Mouth Rehabilitation', 250000.00, 'Complete restoration of all teeth', 1, '2025-04-23 16:32:57'),
(22, 'Sleep Apnea Treatment', 20000.00, 'Dental solutions for sleep apnea', 1, '2025-04-23 16:32:57'),
(23, 'Dental Emergency Care', 1000.00, 'Immediate care for dental emergencies', 1, '2025-04-23 16:32:57');

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int(11) NOT NULL,
  `slot_time` varchar(20) NOT NULL,
  `display_format` varchar(20) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `slot_time`, `display_format`, `is_active`, `created_at`) VALUES
(1, '10:00:00', '10:00 AM', 1, '2025-04-27 01:10:45'),
(2, '11:00:00', '11:00 AM', 1, '2025-04-27 01:10:45'),
(3, '12:00:00', '12:00 PM', 1, '2025-04-27 01:10:45'),
(4, '13:00:00', '1:00 PM', 1, '2025-04-27 01:10:45'),
(5, '14:00:00', '2:00 PM', 1, '2025-04-27 01:10:45'),
(6, '15:00:00', '3:00 PM', 1, '2025-04-27 01:10:45'),
(7, '16:00:00', '4:00 PM', 1, '2025-04-27 01:10:45'),
(8, '17:00:00', '5:00 PM', 1, '2025-04-27 01:10:45'),
(9, '18:00:00', '6:00 PM', 1, '2025-04-27 01:10:45'),
(10, '19:00:00', '7:00 PM', 1, '2025-04-27 01:10:45');

-- --------------------------------------------------------

--
-- Table structure for table `treatments`
--

CREATE TABLE `treatments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `treatment_date` date DEFAULT NULL,
  `treatment_time` time DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment_plan` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_requests`
--
ALTER TABLE `access_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_logins`
--
ALTER TABLE `admin_logins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_id` (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `idx_branch_date` (`clinic_branch`,`appointment_date`),
  ADD KEY `fk_doctor_id` (`doctor_id`),
  ADD KEY `fk_parent_appointment` (`parent_appointment_id`);

--
-- Indexes for table `dentists`
--
ALTER TABLE `dentists`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `medical_history`
--
ALTER TABLE `medical_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `patient_profiles`
--
ALTER TABLE `patient_profiles`
  ADD PRIMARY KEY (`patient_id`);

--
-- Indexes for table `pending_patients`
--
ALTER TABLE `pending_patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_pending_patients_otp_expires` (`otp_expires`);

--
-- Indexes for table `remember_me_tokens`
--
ALTER TABLE `remember_me_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_remember_me_expires` (`expires`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `treatments`
--
ALTER TABLE `treatments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `service_id` (`service_id`);

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
-- AUTO_INCREMENT for table `access_requests`
--
ALTER TABLE `access_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_logins`
--
ALTER TABLE `admin_logins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `dentists`
--
ALTER TABLE `dentists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `medical_history`
--
ALTER TABLE `medical_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `pending_patients`
--
ALTER TABLE `pending_patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `remember_me_tokens`
--
ALTER TABLE `remember_me_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `treatments`
--
ALTER TABLE `treatments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `medical_history`
--
ALTER TABLE `medical_history`
  ADD CONSTRAINT `medical_history_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_profiles`
--
ALTER TABLE `patient_profiles`
  ADD CONSTRAINT `patient_profiles_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `treatments`
--
ALTER TABLE `treatments`
  ADD CONSTRAINT `treatments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `treatments_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
