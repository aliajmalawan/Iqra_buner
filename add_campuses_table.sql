-- Add Campuses management table
-- Run this SQL in phpMyAdmin

USE shaheenpublic_db;

CREATE TABLE IF NOT EXISTS `campuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `area` varchar(150) NOT NULL COMMENT 'e.g. Green Town, Gujranwala',
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `students` varchar(20) DEFAULT NULL COMMENT 'e.g. 800+',
  `programs` varchar(10) DEFAULT NULL COMMENT 'e.g. 12',
  `since_year` varchar(10) DEFAULT NULL COMMENT 'e.g. 2009',
  `icon` varchar(50) DEFAULT 'fa-building-columns' COMMENT 'Font Awesome class',
  `badge` varchar(50) DEFAULT NULL COMMENT 'e.g. Headquarters, Boys Campus',
  `badge_color` varchar(20) DEFAULT '#0B7275',
  `color_from` varchar(20) DEFAULT '#052E30' COMMENT 'Gradient start hex',
  `color_to` varchar(20) DEFAULT '#0B7275' COMMENT 'Gradient end hex',
  `map_query` varchar(200) DEFAULT NULL COMMENT 'Google Maps query, e.g. Green+Town+Gujranwala+Pakistan',
  `facilities` varchar(500) DEFAULT NULL COMMENT 'Comma separated list',
  `is_main` tinyint(1) DEFAULT 0 COMMENT 'Featured in the Location/Map section',
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed with the campuses previously hardcoded on campuses.php
INSERT INTO `campuses` (`name`, `area`, `phone`, `email`, `students`, `programs`, `since_year`, `icon`, `badge`, `badge_color`, `color_from`, `color_to`, `map_query`, `facilities`, `is_main`, `display_order`, `status`) VALUES
('Main Campus', 'Green Town, Gujranwala', '0300 0642851', 'main@aimsgroupofcolleges.com', '800+', '12', '2009', 'fa-building-columns', 'Headquarters', '#0B7275', '#052E30', '#0B7275', 'Green+Town+Gujranwala+Pakistan', 'Computer Lab,Science Labs,Library,Cafeteria,Sports Ground,Prayer Area', 1, 1, 'active'),
('North Campus', 'Satellite Town, Gujranwala', '0346 4890875', 'north@aimsgroupofcolleges.com', '500+', '8', '2014', 'fa-school', 'Boys Campus', '#7C3AED', '#3B1F6B', '#7C3AED', 'Satellite+Town+Gujranwala+Pakistan', 'Computer Lab,Science Labs,Library,Sports Facility,Prayer Area,Canteen', 0, 2, 'active'),
('Girls Campus', 'Civil Lines, Gujranwala', '0300 1234567', 'girls@aimsgroupofcolleges.com', '600+', '10', '2016', 'fa-graduation-cap', 'Girls Campus', '#D91E8C', '#7A0A4E', '#D91E8C', 'Civil+Lines+Gujranwala+Pakistan', 'Computer Lab,Science Labs,Library,Prayer Area,Cafeteria,Recreation Room', 0, 3, 'active'),
('Lahore Campus', 'Model Town, Lahore', '0321 9876543', 'lahore@aimsgroupofcolleges.com', '400+', '7', '2020', 'fa-city', 'New Campus', '#F0A500', '#7A5200', '#F0A500', 'Model+Town+Lahore+Pakistan', 'Computer Lab,Science Labs,Library,Sports Area,Cafeteria,Smart Classrooms', 0, 4, 'active');
