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
-- Table structure for table `refregion`
--

CREATE TABLE `refregion` (
  `region_id` int(11) NOT NULL,
  `region_description` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `refregion`
--

INSERT INTO `refregion` (`region_id`, `region_description`) VALUES
(1, 'National Capital Region'),
(2, 'Cordillera Administrative Region'),
(3, 'Ilocos Region'),
(4, 'Cagayan Valley'),
(5, 'Central Luzon'),
(6, 'CALABARZON'),
(7, 'MIMAROPA'),
(8, 'Bicol Region'),
(9, 'Western Visayas'),
(10, 'Central Visayas'),
(11, 'Eastern Visayas'),
(12, 'Zamboanga Peninsula'),
(13, 'Northern Mindanao'),
(14, 'Davao Region'),
(15, 'SOCCSKSARGEN'),
(16, 'CARAGA'),
(17, 'Autonomous Region in Muslim Mindanao');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `refregion`
--
ALTER TABLE `refregion`
  ADD PRIMARY KEY (`region_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
