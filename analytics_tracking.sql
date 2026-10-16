-- Fort Education System - Website Analytics Tracking
-- This SQL file creates the necessary table for tracking website traffic

-- Drop table if exists (for fresh installation)
DROP TABLE IF EXISTS `website_analytics`;

-- Create website analytics tracking table
CREATE TABLE `website_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visitor_ip` varchar(45) NOT NULL,
  `page_url` varchar(255) NOT NULL,
  `page_title` varchar(255) DEFAULT NULL,
  `referrer` varchar(255) DEFAULT NULL,
  `user_agent` text,
  `device_type` enum('Mobile','Tablet','Desktop','Unknown') DEFAULT 'Unknown',
  `browser` varchar(100) DEFAULT NULL,
  `operating_system` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `session_id` varchar(100) NOT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_visitor_ip` (`visitor_ip`),
  KEY `idx_page_url` (`page_url`),
  KEY `idx_visit_date` (`visit_date`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create table for unique visitor tracking
CREATE TABLE `unique_visitors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visitor_ip` varchar(45) NOT NULL,
  `session_id` varchar(100) NOT NULL,
  `first_visit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_visit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `total_visits` int(11) DEFAULT 1,
  `user_agent` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_session` (`session_id`),
  KEY `idx_visitor_ip` (`visitor_ip`),
  KEY `idx_last_visit` (`last_visit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create table for active users tracking (online users)
CREATE TABLE `active_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(100) NOT NULL,
  `visitor_ip` varchar(45) NOT NULL,
  `current_page` varchar(255) DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_session` (`session_id`),
  KEY `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data insertion is not needed as data will be collected automatically
-- when visitors browse the website

-- Instructions:
-- 1. Import this SQL file into your forteducation_db database
-- 2. Run: mysql -u root forteducation_db < analytics_tracking.sql
-- OR import via phpMyAdmin
