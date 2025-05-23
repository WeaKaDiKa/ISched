-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2025 at 07:56 PM
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
-- Table structure for table `refprovince`
--

CREATE TABLE `refprovince` (
  `province_id` int(11) NOT NULL,
  `region_id` int(11) DEFAULT NULL,
  `province_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `refprovince`
--

INSERT INTO `refprovince` (`province_id`, `region_id`, `province_name`) VALUES
(0, NULL, ''),
(1, 1, 'Metro Manila'),
(2, 3, 'Ilocos Norte'),
(3, 3, 'Ilocos Sur'),
(4, 3, 'La Union'),
(5, 3, 'Pangasinan'),
(6, 4, 'Batanes'),
(7, 4, 'Cagayan'),
(8, 4, 'Isabela'),
(9, 4, 'Nueva Vizcaya'),
(10, 4, 'Quirino'),
(11, 5, 'Bataan'),
(12, 5, 'Bulacan'),
(13, 5, 'Nueva Ecija'),
(14, 5, 'Pampanga'),
(15, 5, 'Tarlac'),
(16, 5, 'Zambales'),
(17, 5, 'Aurora'),
(18, 6, 'Batangas'),
(19, 6, 'Cavite'),
(20, 6, 'Laguna'),
(21, 6, 'Quezon'),
(22, 6, 'Rizal'),
(23, 7, 'Marinduque'),
(24, 7, 'Occidental Mindoro'),
(25, 7, 'Oriental Mindoro'),
(26, 7, 'Palawan'),
(27, 7, 'Romblon'),
(28, 8, 'Albay'),
(29, 8, 'Camarines Norte'),
(30, 8, 'Camarines Sur'),
(31, 8, 'Catanduanes'),
(32, 8, 'Masbate'),
(33, 8, 'Sorsogon'),
(34, 9, 'Aklan'),
(35, 9, 'Antique'),
(36, 9, 'Capiz'),
(37, 9, 'Iloilo'),
(38, 9, 'Negros Occidental'),
(39, 9, 'Guimaras'),
(40, 10, 'Bohol'),
(41, 10, 'Cebu'),
(42, 10, 'Negros Oriental'),
(43, 10, 'Siquijor'),
(44, 11, 'Eastern Samar'),
(45, 11, 'Leyte'),
(46, 11, 'Northern Samar'),
(47, 11, 'Samar'),
(48, 11, 'Southern Leyte'),
(49, 11, 'Biliran'),
(50, 12, 'Zamboanga del Norte'),
(51, 12, 'Zamboanga del Sur'),
(52, 12, 'Zamboanga Sibugay'),
(53, 13, 'Bukidnon'),
(54, 13, 'Camiguin'),
(55, 13, 'Lanao del Norte'),
(56, 13, 'Misamis Occidental'),
(57, 13, 'Misamis Oriental'),
(58, 14, 'Davao del Norte'),
(59, 14, 'Davao del Sur'),
(60, 14, 'Davao Oriental'),
(61, 14, 'Davao de Oro'),
(62, 14, 'Davao Occidental'),
(63, 15, 'Cotabato'),
(64, 15, 'South Cotabato'),
(65, 15, 'Sultan Kudarat'),
(66, 15, 'Sarangani'),
(67, 2, 'Abra'),
(68, 2, 'Benguet'),
(69, 2, 'Ifugao'),
(70, 2, 'Kalinga'),
(71, 2, 'Mountain Province'),
(72, 2, 'Apayao'),
(73, 17, 'Basilan'),
(74, 17, 'Lanao del Sur'),
(75, 17, 'Maguindanao'),
(76, 17, 'Sulu'),
(77, 17, 'Tawi-Tawi'),
(78, 16, 'Agusan del Norte'),
(79, 16, 'Agusan del Sur'),
(80, 16, 'Surigao del Norte'),
(81, 16, 'Surigao del Sur'),
(82, 16, 'Dinagat Islands');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `refprovince`
--
ALTER TABLE `refprovince`
  ADD PRIMARY KEY (`province_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
