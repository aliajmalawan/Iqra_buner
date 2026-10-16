-- Add Fee Structure table for the Admission page's fee cards
-- Run this SQL in phpMyAdmin

USE shaheenpublic_db;

CREATE TABLE IF NOT EXISTS `fee_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `subtitle` varchar(150) DEFAULT NULL,
  `price` varchar(50) NOT NULL COMMENT 'e.g. 5,000 or 7,000-8,000',
  `price_period` varchar(50) DEFAULT 'per month',
  `icon` varchar(50) DEFAULT 'fas fa-tag' COMMENT 'Font Awesome class',
  `color` varchar(20) DEFAULT '#0B7275' COMMENT 'Hex color for card gradient',
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed with the cards previously hardcoded on admission.php
INSERT INTO `fee_structure` (`title`, `subtitle`, `price`, `price_period`, `icon`, `color`, `display_order`, `status`) VALUES
('Matric Programs', '9th & 10th Grade', '5,000', 'per month', 'fas fa-graduation-cap', '#0B7275', 1, 'active'),
('Intermediate', 'FSc / ICS Programs', '6,000', 'per month', 'fas fa-university', '#1565c0', 2, 'active'),
('Entry Test Prep', 'ECAT / MDCAT / IELTS', '7,000-8,000', 'per month', 'fas fa-pencil-alt', '#e65100', 3, 'active'),
('Short Courses', 'IT / Languages / Math', '3,000-5,000', 'per month', 'fas fa-clock', '#7b1fa2', 4, 'active'),
('Admission Fee', 'One-time registration', '2,000', 'one time', 'fas fa-star', '#c62828', 5, 'active');
