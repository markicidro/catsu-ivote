-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 23, 2025 at 05:53 AM
-- Server version: 10.4.22-MariaDB
-- PHP Version: 8.0.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ivote_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `main_org_candidates`
--

CREATE TABLE `main_org_candidates` (
  `id` int(11) NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `nickname` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `college` varchar(50) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `partylist` varchar(100) DEFAULT NULL,
  `permanent_address` text DEFAULT NULL,
  `temporary_address` text DEFAULT NULL,
  `period_of_residency` varchar(100) DEFAULT NULL,
  `semester_year` varchar(100) DEFAULT NULL,
  `certificate_of_candidacy` varchar(255) DEFAULT NULL,
  `comelec_form_1` varchar(255) DEFAULT NULL,
  `certificate_of_recommendation` varchar(255) DEFAULT NULL,
  `prospectus` varchar(255) DEFAULT NULL,
  `clearance` varchar(255) DEFAULT NULL,
  `coe` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `filing_date` datetime NOT NULL DEFAULT current_timestamp(),
  `comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `main_org_candidates`
--

INSERT INTO `main_org_candidates` (`id`, `profile_pic`, `last_name`, `first_name`, `middle_name`, `nickname`, `age`, `gender`, `dob`, `college`, `year`, `program`, `phone`, `email`, `position`, `partylist`, `permanent_address`, `temporary_address`, `period_of_residency`, `semester_year`, `certificate_of_candidacy`, `comelec_form_1`, `certificate_of_recommendation`, `prospectus`, `clearance`, `coe`, `created_at`, `status`, `filing_date`, `comment`) VALUES
(1, 'uploads/Image 1.jpg', 'trinidad', 'mark', 'tevar', 'mark', 21, 'Male', '2003-12-08', 'CICT', 4, 'BPA', '09488753949', 'trinidadjonheljm@catsu.edu.ph', 'President', 'none', 'zdgzjzfh', 'DHzndd', 'DBNDF', 'fBDFb', 'uploads/Image 2.jpg', 'uploads/Image 3.jpg', 'uploads/Image 4.jpg', 'uploads/Image 5.jpg', 'uploads/Image 6.jpg', 'uploads/Image 7.jpg', '2025-09-18 16:42:08', 'Accepted', '2025-09-19 10:29:21', ''),
(2, 'uploads/Image 1.jpg', 'rivera', 'mark', 'tioxon', 'mark', 10, 'Male', '2015-02-20', 'CICT', 4, 'ABEcon', '09488753949', 'trinidadjonhel@catsu.edu.ph', 'President', 'none', ',mvmgh', 'xhrzjnhfd', 'nfgx', 'gnxfn', 'uploads/Image 2.jpg', 'uploads/Image 3.jpg', 'uploads/Image 6.jpg', 'uploads/Image 5.jpg', 'uploads/Image 11.jpg', 'uploads/Image 9.jpg', '2025-09-18 16:47:59', 'Accepted', '2025-09-19 10:29:21', ''),
(3, 'uploads/Image 1.jpg', 'rivera', 'jonhel', 'tioxon', 'mark', 21, 'Male', '2003-11-20', 'CICT', 4, 'BSA', '09708643103', 'trinidadjonhel@catsu.edu.ph', 'Vice President', 'none', 'efawe', 'gearga', 'GESGvfa', 'SFvSDF', 'uploads/Image 6.jpg', 'uploads/Image 4.jpg', 'uploads/Image 3.jpg', 'uploads/Image 9.jpg', 'uploads/Image 2.jpg', 'uploads/Image 10.jpg', '2025-09-19 00:39:43', 'Accepted', '2025-09-19 10:29:21', ''),
(4, 'uploads/Image 5.jpg', 'trinidad', 'jonhel', 'tioxon', 'jm', 21, 'Male', '2003-12-08', 'CICT', 4, 'BSFisheries', '09488753949', 'trinidadjonhel12345@catsu.edu.ph', 'President', 'none', 'dQR', 'ERWRAQT', 'qwrt4t', 'weqt4h', 'uploads/Image 8.jpg', 'uploads/Image 4.jpg', 'uploads/Image 5.jpg', 'uploads/Image 2.jpg', 'uploads/Image 5.jpg', 'uploads/Image 1.jpg', '2025-09-19 00:52:32', 'Pending', '2025-09-19 10:29:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sub_org_candidates`
--

CREATE TABLE `sub_org_candidates` (
  `id` int(11) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `block_address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `filing_date` datetime NOT NULL DEFAULT current_timestamp(),
  `comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sub_org_candidates`
--

INSERT INTO `sub_org_candidates` (`id`, `last_name`, `first_name`, `middle_name`, `year`, `block_address`, `created_at`, `status`, `filing_date`, `comment`) VALUES
(1, 'Rivera', 'Mark', 'Tevar', 5, 'cmnfn', '2025-09-18 16:49:09', 'Rejected', '2025-09-19 10:29:40', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('voter','candidate','admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `student_id`, `password`, `role`) VALUES
(2, 'admin@catsu.edu.ph', '2022-00001', 'admin123', 'admin'),
(18, 'marktevar15@gmail.com', '2022-61416', '12345678', 'voter'),
(27, 'trinidadjonhel@catsu.edu.ph', '2022-123456', '12-08-2003', 'voter');

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `voted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `voting_schedule`
--

CREATE TABLE `voting_schedule` (
  `id` int(11) NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'closed',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `voting_schedule`
--

INSERT INTO `voting_schedule` (`id`, `status`, `start_date`, `end_date`, `description`, `created_at`, `updated_at`) VALUES
(1, 'closed', '2025-09-23 03:45:00', '2025-09-23 03:45:00', 'Voting closed by admin', '2025-09-23 01:43:24', '2025-09-23 03:03:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `main_org_candidates`
--
ALTER TABLE `main_org_candidates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sub_org_candidates`
--
ALTER TABLE `sub_org_candidates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_vote` (`user_id`);

--
-- Indexes for table `voting_schedule`
--
ALTER TABLE `voting_schedule`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `main_org_candidates`
--
ALTER TABLE `main_org_candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sub_org_candidates`
--
ALTER TABLE `sub_org_candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `votes`
--
ALTER TABLE `votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `voting_schedule`
--
ALTER TABLE `voting_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
