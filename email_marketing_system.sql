-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Dec 05, 2025 at 04:28 PM
-- Server version: 8.0.40
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `email_marketing_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `Admins`
--

CREATE TABLE `Admins` (
  `admin_id` int NOT NULL,
  `admin_email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('SuperAdmin','Support','Auditor') DEFAULT 'Support',
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Audit_Trail`
--

CREATE TABLE `Audit_Trail` (
  `audit_id` bigint NOT NULL,
  `member_id` int DEFAULT NULL,
  `admin_id` int DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_description` text,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `action_time` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Audit_Trail`
--

INSERT INTO `Audit_Trail` (`audit_id`, `member_id`, `admin_id`, `action_type`, `action_description`, `table_name`, `record_id`, `action_time`) VALUES
(1, 2, NULL, 'MEMBER_REGISTERED', NULL, NULL, NULL, '2025-11-26 06:43:53'),
(2, 3, NULL, 'MEMBER_REGISTERED', NULL, NULL, NULL, '2025-11-26 06:45:26'),
(3, 3, NULL, 'CREATE_CAMPAIGN', NULL, NULL, NULL, '2025-11-26 09:55:29'),
(4, 4, NULL, 'MEMBER_REGISTERED', NULL, NULL, NULL, '2025-12-01 05:38:19'),
(5, 4, NULL, 'CREATE_CAMPAIGN', NULL, NULL, NULL, '2025-12-01 05:39:01'),
(6, 4, NULL, 'CREATE_TEMPLATE', NULL, NULL, NULL, '2025-12-01 05:39:55'),
(7, 4, NULL, 'CREATE_GROUP', NULL, NULL, NULL, '2025-12-01 05:41:33'),
(8, 4, NULL, 'UPDATE_GROUP', NULL, NULL, NULL, '2025-12-01 05:41:47'),
(9, 4, NULL, 'UPDATE_SMTP_SETTINGS', NULL, NULL, NULL, '2025-12-02 05:54:05'),
(10, 4, NULL, 'CREATE_CONTACT', NULL, NULL, NULL, '2025-12-02 06:02:03'),
(11, 4, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-02 06:02:27'),
(12, 4, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-02 06:02:27'),
(13, 4, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-02 06:04:03'),
(14, 4, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-02 06:04:03'),
(15, 4, NULL, 'CREATE_CAMPAIGN', NULL, NULL, NULL, '2025-12-02 06:04:30'),
(16, 4, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-02 06:04:36'),
(17, 4, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-02 06:04:36'),
(18, 4, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-02 06:08:53'),
(19, 4, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-02 06:08:53'),
(20, 4, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-03 06:04:15'),
(21, 4, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-03 06:04:15'),
(22, 4, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-03 06:22:33'),
(23, 4, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-03 06:22:33'),
(24, 4, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-04 05:55:32'),
(25, 4, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-04 05:55:32'),
(26, 4, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-04 06:06:07'),
(27, 4, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-04 06:06:07'),
(28, 4, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-04 06:34:43'),
(29, 4, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-04 06:34:43'),
(30, 4, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-04 06:37:15'),
(31, 4, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-04 06:37:15'),
(32, 4, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-04 06:37:56'),
(33, 4, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-04 06:37:56'),
(34, 4, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-04 06:41:04'),
(35, 4, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-04 06:41:04'),
(36, 4, NULL, 'CREATE_CAMPAIGN', NULL, NULL, NULL, '2025-12-04 06:41:41'),
(37, 4, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-04 06:41:47'),
(38, 4, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-04 06:41:47'),
(39, 4, NULL, 'CREATE_CONTACT', NULL, NULL, NULL, '2025-12-04 06:43:52'),
(40, 4, NULL, 'REMOVE_CONTACT_FROM_GROUP', NULL, NULL, NULL, '2025-12-04 06:43:58'),
(41, 4, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-04 06:44:14'),
(42, 4, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-04 06:44:14'),
(43, 4, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-04 06:44:55'),
(44, 4, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-04 06:44:55'),
(45, 4, NULL, 'UPDATE_SMTP_SETTINGS', NULL, NULL, NULL, '2025-12-04 06:46:26'),
(46, 4, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-04 06:46:33'),
(47, 4, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-04 06:51:33'),
(48, 4, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-04 06:51:33'),
(49, 4, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-04 06:56:34'),
(50, 5, NULL, 'MEMBER_REGISTERED', NULL, NULL, NULL, '2025-12-04 06:58:07'),
(51, 5, NULL, 'CREATE_CONTACT', NULL, NULL, NULL, '2025-12-04 06:58:49'),
(52, 5, NULL, 'CREATE_CAMPAIGN', NULL, NULL, NULL, '2025-12-04 06:59:06'),
(53, 5, NULL, 'CREATE_TEMPLATE', NULL, NULL, NULL, '2025-12-04 06:59:18'),
(54, 5, NULL, 'CREATE_GROUP', NULL, NULL, NULL, '2025-12-04 06:59:38'),
(55, 5, NULL, 'UPDATE_SMTP_SETTINGS', NULL, NULL, NULL, '2025-12-04 07:00:36'),
(56, 5, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-04 07:00:42'),
(57, 5, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-04 07:00:48'),
(58, 5, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-04 07:00:48'),
(59, 5, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-04 07:00:52'),
(60, 5, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-04 07:01:19'),
(61, 5, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-04 07:01:25'),
(62, 5, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-04 07:52:38'),
(63, 5, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-04 07:52:44'),
(64, 5, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-04 07:52:44'),
(65, 5, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-04 07:52:48'),
(66, 5, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-04 07:53:04'),
(67, 5, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-04 07:53:08'),
(68, 5, NULL, 'SEND_CAMPAIGN_START', NULL, NULL, NULL, '2025-12-04 07:55:33'),
(69, 5, NULL, 'SEND_CAMPAIGN_COMPLETE', NULL, NULL, NULL, '2025-12-04 07:55:45'),
(70, 5, NULL, 'REMOVE_CONTACT_FROM_GROUP', NULL, NULL, NULL, '2025-12-04 08:02:17');

-- --------------------------------------------------------

--
-- Table structure for table `Campaigns`
--

CREATE TABLE `Campaigns` (
  `campaign_id` int NOT NULL,
  `member_id` int NOT NULL,
  `campaign_name` varchar(150) NOT NULL,
  `campaign_description` text,
  `campaign_status` enum('Draft','Active','Paused','Completed','Archived') DEFAULT 'Draft',
  `schedule_time` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Campaigns`
--

INSERT INTO `Campaigns` (`campaign_id`, `member_id`, `campaign_name`, `campaign_description`, `campaign_status`, `schedule_time`, `created_at`) VALUES
(1, 3, 'Cold Mail', 'sagsahas', 'Active', '2025-11-12 09:54:00', '2025-11-26 17:55:29'),
(2, 4, 'Cold Mail', 'dsgsdghsdh', 'Paused', '2025-12-05 05:38:00', '2025-12-01 13:39:01'),
(3, 4, 'New campaign', 'sdghsdhsdhsd', 'Active', '2025-12-02 06:04:00', '2025-12-02 14:04:30'),
(4, 4, 'New campaign 2', 'DSGHSDH', 'Active', '2025-12-04 06:41:00', '2025-12-04 14:41:41'),
(5, 5, 'New campaign', '[pihojnhkjlnm', 'Active', '2025-12-04 06:59:00', '2025-12-04 14:59:06');

-- --------------------------------------------------------

--
-- Table structure for table `Campaign_Groups`
--

CREATE TABLE `Campaign_Groups` (
  `campaign_id` int NOT NULL,
  `group_id` int NOT NULL,
  `linked_on` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Campaign_Groups`
--

INSERT INTO `Campaign_Groups` (`campaign_id`, `group_id`, `linked_on`) VALUES
(2, 1, '2025-12-02 05:54:34'),
(3, 1, '2025-12-02 06:04:36'),
(4, 1, '2025-12-04 06:41:47'),
(5, 2, '2025-12-04 07:00:42');

-- --------------------------------------------------------

--
-- Table structure for table `ContactGroups`
--

CREATE TABLE `ContactGroups` (
  `group_id` int NOT NULL,
  `member_id` int NOT NULL,
  `group_name` varchar(150) NOT NULL,
  `group_description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ContactGroups`
--

INSERT INTO `ContactGroups` (`group_id`, `member_id`, `group_name`, `group_description`, `created_at`) VALUES
(1, 4, 'Cloud', 'dsagbhsdh', '2025-12-01 13:41:33'),
(2, 5, 'Iplex', 'treiuhfcn', '2025-12-04 14:59:38');

-- --------------------------------------------------------

--
-- Table structure for table `Contacts`
--

CREATE TABLE `Contacts` (
  `contact_id` int NOT NULL,
  `member_id` int NOT NULL,
  `honorifics` varchar(10) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `contact_status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Contacts`
--

INSERT INTO `Contacts` (`contact_id`, `member_id`, `honorifics`, `first_name`, `middle_name`, `last_name`, `email`, `contact_status`, `created_at`, `updated_at`) VALUES
(2, 4, 'Mr.', 'Hamid', 'Hazmid', 'dshdsfjhdf', 'hamid@gmail.com', 'Active', '2025-12-02 14:02:03', '2025-12-02 14:02:03'),
(3, 4, 'Mr.', 'AYAN', 'LIAQAT', 'dshdsfjhdf', 'rajaayan.iplex@gmail.com', 'Active', '2025-12-04 14:43:52', '2025-12-04 14:43:52'),
(4, 5, 'Ms.', 'Paul', 'Hazmid', 'brooks', 'rajaayan.iplex@gmail.com', 'Active', '2025-12-04 14:58:49', '2025-12-04 14:58:49');

-- --------------------------------------------------------

--
-- Table structure for table `Email_Log`
--

CREATE TABLE `Email_Log` (
  `email_log_id` bigint NOT NULL,
  `campaign_id` int NOT NULL,
  `group_id` int NOT NULL,
  `template_id` int NOT NULL,
  `contact_id` int NOT NULL,
  `delivery_status` enum('Queued','Sent','Failed','Opened','Clicked','Bounced') DEFAULT 'Queued',
  `sent_on` datetime DEFAULT NULL,
  `opened_on` datetime DEFAULT NULL,
  `clicked_on` datetime DEFAULT NULL,
  `error_message` text,
  `batch_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Email_Log`
--

INSERT INTO `Email_Log` (`email_log_id`, `campaign_id`, `group_id`, `template_id`, `contact_id`, `delivery_status`, `sent_on`, `opened_on`, `clicked_on`, `error_message`, `batch_id`) VALUES
(1, 2, 1, 1, 2, 'Queued', '2025-12-02 14:02:27', NULL, NULL, 'PHPMailer not installed. Email queued for sending.', NULL),
(2, 2, 1, 1, 2, 'Queued', '2025-12-02 14:04:03', NULL, NULL, 'PHPMailer not installed. Email queued for sending.', NULL),
(3, 3, 1, 1, 2, 'Queued', '2025-12-02 14:04:36', NULL, NULL, 'PHPMailer not installed. Email queued for sending.', NULL),
(4, 2, 1, 1, 2, 'Queued', '2025-12-02 14:08:53', NULL, NULL, 'PHPMailer not installed (vendor/autoload.php missing). Email queued but not actually sent.', NULL),
(5, 3, 1, 1, 2, 'Queued', '2025-12-03 14:04:15', NULL, NULL, 'PHPMailer not installed (vendor/autoload.php missing). Email queued but not actually sent.', NULL),
(6, 3, 1, 1, 2, 'Queued', '2025-12-03 14:22:33', NULL, NULL, 'PHPMailer not installed (vendor/autoload.php missing). Email queued but not actually sent.', NULL),
(7, 2, 1, 1, 2, 'Queued', '2025-12-04 13:55:32', NULL, NULL, 'PHPMailer not installed (vendor/autoload.php missing). Email queued but not actually sent.', NULL),
(8, 2, 1, 1, 2, 'Queued', '2025-12-04 14:06:07', NULL, NULL, 'PHPMailer not installed (vendor/autoload.php missing). Email queued but not actually sent.', NULL),
(9, 2, 1, 1, 2, 'Queued', '2025-12-04 14:34:43', NULL, NULL, 'PHPMailer not installed (vendor/autoload.php missing). Email queued but not actually sent.', NULL),
(10, 3, 1, 1, 2, 'Failed', '2025-12-04 14:37:15', NULL, NULL, 'Email send error: Invalid address:  (From): ce0818ec40ad04', NULL),
(11, 3, 1, 1, 2, 'Failed', '2025-12-04 14:37:56', NULL, NULL, 'Email send error: Invalid address:  (From): ce0818ec40ad04', NULL),
(12, 4, 1, 1, 3, 'Failed', '2025-12-04 14:51:33', NULL, NULL, 'Email send error: SMTP Error: Could not connect to SMTP host. Failed to connect to server', NULL),
(13, 4, 1, 1, 3, 'Failed', '2025-12-04 14:56:34', NULL, NULL, 'Email send error: SMTP Error: Could not connect to SMTP host. Failed to connect to server', NULL),
(14, 5, 2, 2, 4, 'Sent', '2025-12-04 15:00:48', NULL, NULL, NULL, NULL),
(15, 5, 2, 2, 4, 'Failed', '2025-12-04 15:00:52', NULL, NULL, 'Email send error: SMTP Error: data not accepted.', NULL),
(16, 5, 2, 2, 4, 'Sent', '2025-12-04 15:01:25', NULL, NULL, NULL, NULL),
(17, 5, 2, 2, 4, 'Sent', '2025-12-04 15:52:44', NULL, NULL, NULL, NULL),
(18, 5, 2, 2, 4, 'Failed', '2025-12-04 15:52:48', NULL, NULL, 'Email send error: SMTP Error: data not accepted.', NULL),
(19, 5, 2, 2, 4, 'Sent', '2025-12-04 15:53:08', NULL, NULL, NULL, NULL),
(20, 5, 2, 2, 4, 'Sent', '2025-12-04 15:55:45', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `Email_Queue_Settings`
--

CREATE TABLE `Email_Queue_Settings` (
  `queue_id` int NOT NULL,
  `member_id` int NOT NULL,
  `max_emails_per_batch` int DEFAULT '10',
  `max_batch_per_hour` int DEFAULT '5'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Group_Members`
--

CREATE TABLE `Group_Members` (
  `group_id` int NOT NULL,
  `contact_id` int NOT NULL,
  `added_on` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Group_Members`
--

INSERT INTO `Group_Members` (`group_id`, `contact_id`, `added_on`) VALUES
(1, 3, '2025-12-04 14:43:59'),
(2, 4, '2025-12-04 16:02:33');

-- --------------------------------------------------------

--
-- Table structure for table `Members`
--

CREATE TABLE `Members` (
  `member_id` int NOT NULL,
  `member_name` varchar(150) NOT NULL,
  `member_email` varchar(150) NOT NULL,
  `member_status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Members`
--

INSERT INTO `Members` (`member_id`, `member_name`, `member_email`, `member_status`, `created_at`, `updated_at`) VALUES
(2, 'john', 'john@gmail.com', 'Active', '2025-11-26 14:43:53', '2025-11-26 14:43:53'),
(3, 'johnn', 'johnn@gmail.com', 'Active', '2025-11-26 14:45:26', '2025-11-26 14:45:26'),
(4, 'john', 'johnny@gmail.com', 'Active', '2025-12-01 13:38:19', '2025-12-01 13:38:19'),
(5, 'Paul', 'paul.brooks@gmail.com', 'Active', '2025-12-04 14:58:07', '2025-12-04 14:58:07');

-- --------------------------------------------------------

--
-- Table structure for table `Member_SMTP_Settings`
--

CREATE TABLE `Member_SMTP_Settings` (
  `smtp_id` int NOT NULL,
  `member_id` int NOT NULL,
  `smtp_host` varchar(255) NOT NULL,
  `smtp_port` int NOT NULL,
  `smtp_user` varchar(150) NOT NULL,
  `smtp_password` varchar(255) NOT NULL,
  `encryption` enum('SSL','TLS','None') DEFAULT 'TLS',
  `default_from_email` varchar(150) DEFAULT NULL,
  `default_from_name` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Member_SMTP_Settings`
--

INSERT INTO `Member_SMTP_Settings` (`smtp_id`, `member_id`, `smtp_host`, `smtp_port`, `smtp_user`, `smtp_password`, `encryption`, `default_from_email`, `default_from_name`, `created_at`, `updated_at`) VALUES
(1, 4, 'sandbox.smtp.mailtrap.io', 25, 'ce0818ec40ad04', '4318d0f1f3e4bd', 'TLS', 'kingbooster093@gmail.com', 'Iplex', '2025-12-02 13:54:05', '2025-12-04 14:46:26'),
(2, 5, 'sandbox.smtp.mailtrap.io', 465, 'ce0818ec40ad04', '4318d0f1f3e4bd', 'TLS', 'kingbooster093@gmail.com', 'Iplex', '2025-12-04 15:00:36', '2025-12-04 15:00:36');

-- --------------------------------------------------------

--
-- Table structure for table `System_Settings`
--

CREATE TABLE `System_Settings` (
  `setting_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `value` varchar(100) NOT NULL,
  `description` text,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Templates`
--

CREATE TABLE `Templates` (
  `template_id` int NOT NULL,
  `campaign_id` int NOT NULL,
  `template_name` varchar(150) NOT NULL,
  `template_subject` varchar(255) DEFAULT NULL,
  `template_content` mediumtext,
  `template_status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Templates`
--

INSERT INTO `Templates` (`template_id`, `campaign_id`, `template_name`, `template_subject`, `template_content`, `template_status`, `created_at`) VALUES
(1, 2, 'Dark theme', 'gsdgsdahsd', 'dsahfdsafdshdfs', 'Active', '2025-12-01 13:39:55'),
(2, 5, 'Dark theme', 'gsdgsdahsd', ';ljmnlk;ml\'', 'Active', '2025-12-04 14:59:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Admins`
--
ALTER TABLE `Admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `admin_email` (`admin_email`);

--
-- Indexes for table `Audit_Trail`
--
ALTER TABLE `Audit_Trail`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `Campaigns`
--
ALTER TABLE `Campaigns`
  ADD PRIMARY KEY (`campaign_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `Campaign_Groups`
--
ALTER TABLE `Campaign_Groups`
  ADD PRIMARY KEY (`campaign_id`,`group_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `ContactGroups`
--
ALTER TABLE `ContactGroups`
  ADD PRIMARY KEY (`group_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `Contacts`
--
ALTER TABLE `Contacts`
  ADD PRIMARY KEY (`contact_id`),
  ADD UNIQUE KEY `unique_contact_email_per_member` (`member_id`,`email`);

--
-- Indexes for table `Email_Log`
--
ALTER TABLE `Email_Log`
  ADD PRIMARY KEY (`email_log_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `template_id` (`template_id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `idx_campaign_contact` (`campaign_id`,`contact_id`);

--
-- Indexes for table `Email_Queue_Settings`
--
ALTER TABLE `Email_Queue_Settings`
  ADD PRIMARY KEY (`queue_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `Group_Members`
--
ALTER TABLE `Group_Members`
  ADD PRIMARY KEY (`group_id`,`contact_id`),
  ADD KEY `contact_id` (`contact_id`);

--
-- Indexes for table `Members`
--
ALTER TABLE `Members`
  ADD PRIMARY KEY (`member_id`),
  ADD UNIQUE KEY `member_email` (`member_email`);

--
-- Indexes for table `Member_SMTP_Settings`
--
ALTER TABLE `Member_SMTP_Settings`
  ADD PRIMARY KEY (`smtp_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `System_Settings`
--
ALTER TABLE `System_Settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `Templates`
--
ALTER TABLE `Templates`
  ADD PRIMARY KEY (`template_id`),
  ADD KEY `campaign_id` (`campaign_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Admins`
--
ALTER TABLE `Admins`
  MODIFY `admin_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Audit_Trail`
--
ALTER TABLE `Audit_Trail`
  MODIFY `audit_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `Campaigns`
--
ALTER TABLE `Campaigns`
  MODIFY `campaign_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ContactGroups`
--
ALTER TABLE `ContactGroups`
  MODIFY `group_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `Contacts`
--
ALTER TABLE `Contacts`
  MODIFY `contact_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `Email_Log`
--
ALTER TABLE `Email_Log`
  MODIFY `email_log_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `Email_Queue_Settings`
--
ALTER TABLE `Email_Queue_Settings`
  MODIFY `queue_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Members`
--
ALTER TABLE `Members`
  MODIFY `member_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `Member_SMTP_Settings`
--
ALTER TABLE `Member_SMTP_Settings`
  MODIFY `smtp_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `System_Settings`
--
ALTER TABLE `System_Settings`
  MODIFY `setting_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Templates`
--
ALTER TABLE `Templates`
  MODIFY `template_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Audit_Trail`
--
ALTER TABLE `Audit_Trail`
  ADD CONSTRAINT `audit_trail_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `Members` (`member_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `audit_trail_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `Admins` (`admin_id`) ON DELETE SET NULL;

--
-- Constraints for table `Campaigns`
--
ALTER TABLE `Campaigns`
  ADD CONSTRAINT `campaigns_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `Members` (`member_id`) ON DELETE CASCADE;

--
-- Constraints for table `Campaign_Groups`
--
ALTER TABLE `Campaign_Groups`
  ADD CONSTRAINT `campaign_groups_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `Campaigns` (`campaign_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `campaign_groups_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `ContactGroups` (`group_id`) ON DELETE CASCADE;

--
-- Constraints for table `ContactGroups`
--
ALTER TABLE `ContactGroups`
  ADD CONSTRAINT `contactgroups_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `Members` (`member_id`) ON DELETE CASCADE;

--
-- Constraints for table `Contacts`
--
ALTER TABLE `Contacts`
  ADD CONSTRAINT `contacts_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `Members` (`member_id`) ON DELETE CASCADE;

--
-- Constraints for table `Email_Log`
--
ALTER TABLE `Email_Log`
  ADD CONSTRAINT `email_log_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `Campaigns` (`campaign_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `email_log_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `ContactGroups` (`group_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `email_log_ibfk_3` FOREIGN KEY (`template_id`) REFERENCES `Templates` (`template_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `email_log_ibfk_4` FOREIGN KEY (`contact_id`) REFERENCES `Contacts` (`contact_id`) ON DELETE CASCADE;

--
-- Constraints for table `Email_Queue_Settings`
--
ALTER TABLE `Email_Queue_Settings`
  ADD CONSTRAINT `email_queue_settings_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `Members` (`member_id`) ON DELETE CASCADE;

--
-- Constraints for table `Group_Members`
--
ALTER TABLE `Group_Members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `ContactGroups` (`group_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`contact_id`) REFERENCES `Contacts` (`contact_id`) ON DELETE CASCADE;

--
-- Constraints for table `Member_SMTP_Settings`
--
ALTER TABLE `Member_SMTP_Settings`
  ADD CONSTRAINT `member_smtp_settings_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `Members` (`member_id`) ON DELETE CASCADE;

--
-- Constraints for table `Templates`
--
ALTER TABLE `Templates`
  ADD CONSTRAINT `templates_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `Campaigns` (`campaign_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
