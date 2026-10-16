-- New Features Database Schema
-- Run this SQL in phpMyAdmin to add new tables

-- 1. Downloads Table
CREATE TABLE IF NOT EXISTS `downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `description` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Alumni Table
CREATE TABLE IF NOT EXISTS `alumni` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_name` varchar(100) NOT NULL,
  `father_name` varchar(100) NOT NULL,
  `current_job` varchar(150) DEFAULT NULL,
  `job_department` varchar(150) DEFAULT NULL,
  `job_city` varchar(100) DEFAULT NULL,
  `course` varchar(100) NOT NULL,
  `passing_year` int(4) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `mobile_number` varchar(20) NOT NULL,
  `whatsapp_number` varchar(20) DEFAULT NULL,
  `review` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Alumni Reviews (separate submissions)
CREATE TABLE IF NOT EXISTS `alumni_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `passing_year` int(4) DEFAULT NULL,
  `review` text NOT NULL,
  `rating` int(1) DEFAULT 5,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Examination Datesheets Table
CREATE TABLE IF NOT EXISTS `exam_datesheets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_name` varchar(150) NOT NULL,
  `exam_year` int(4) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Datesheet Details (schedule)
CREATE TABLE IF NOT EXISTS `datesheet_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datesheet_id` int(11) NOT NULL,
  `exam_date` date NOT NULL,
  `day_name` varchar(20) NOT NULL,
  `class` varchar(20) NOT NULL,
  `subject` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `datesheet_id` (`datesheet_id`),
  CONSTRAINT `datesheet_details_ibfk_1` FOREIGN KEY (`datesheet_id`) REFERENCES `exam_datesheets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Board Results Table
CREATE TABLE IF NOT EXISTS `board_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `board_type` enum('Matric','Intermediate') NOT NULL,
  `year` int(4) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Notifications Table (for moving notifications)
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
