-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 26, 2026 at 07:31 AM
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
-- Database: `gov_finance`
--

-- --------------------------------------------------------

--
-- Table structure for table `finances`
--

CREATE TABLE `finances` (
  `id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `revenue` decimal(12,2) NOT NULL,
  `expenditure` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `finances`
--

INSERT INTO `finances` (`id`, `year`, `month`, `revenue`, `expenditure`, `created_at`) VALUES
(1, 2024, 1, 154.00, 190.00, '2026-04-26 04:27:01'),
(2, 2024, 2, 120.00, 90.00, '2026-04-26 04:27:01'),
(3, 2024, 3, 110.00, 200.00, '2026-04-26 04:27:01'),
(4, 2024, 4, 130.00, 100.00, '2026-04-26 04:27:01'),
(5, 2024, 5, 125.00, 98.00, '2026-04-26 04:27:01'),
(6, 2024, 6, 140.00, 105.00, '2026-04-26 04:27:01'),
(7, 2024, 7, 135.00, 102.00, '2026-04-26 04:27:01'),
(8, 2024, 8, 150.00, 110.00, '2026-04-26 04:27:01'),
(9, 2024, 9, 145.00, 108.00, '2026-04-26 04:27:01'),
(10, 2024, 10, 160.00, 115.00, '2026-04-26 04:27:01'),
(11, 2024, 11, 155.00, 112.00, '2026-04-26 04:27:01'),
(12, 2024, 12, 170.00, 120.00, '2026-04-26 04:27:01'),
(13, 2025, 1, 180.00, 130.00, '2026-04-26 04:46:21'),
(14, 2025, 2, 185.00, 135.00, '2026-04-26 04:46:21'),
(15, 2025, 3, 190.00, 140.00, '2026-04-26 04:46:21'),
(16, 2025, 4, 200.00, 150.00, '2026-04-26 05:25:27'),
(17, 2025, 5, 195.00, 145.00, '2026-04-26 05:25:27'),
(18, 2025, 6, 210.00, 155.00, '2026-04-26 05:25:27'),
(19, 2025, 7, 205.00, 150.00, '2026-04-26 05:25:27'),
(20, 2025, 8, 220.00, 160.00, '2026-04-26 05:25:27'),
(21, 2025, 9, 215.00, 158.00, '2026-04-26 05:25:27'),
(22, 2025, 10, 230.00, 170.00, '2026-04-26 05:25:27'),
(23, 2025, 11, 225.00, 168.00, '2026-04-26 05:25:27'),
(24, 2025, 12, 240.00, 180.00, '2026-04-26 05:25:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `finances`
--
ALTER TABLE `finances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_month_year` (`year`,`month`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `finances`
--
ALTER TABLE `finances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
