-- Add Themes Table for Theme Management System
-- Run this SQL in phpMyAdmin if themes table doesn't exist

USE shaheenpublic_db;

-- Create themes table
CREATE TABLE IF NOT EXISTS `themes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `theme_name` varchar(100) NOT NULL,
  `primary_color` varchar(7) NOT NULL DEFAULT '#0B4DA2',
  `secondary_color` varchar(7) NOT NULL DEFAULT '#FFFFFF',
  `accent_color` varchar(7) NOT NULL DEFAULT '#F9C900',
  `logo_path` varchar(255) DEFAULT 'images/logo.png',
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default themes for Shaheen Public High School
INSERT INTO `themes` (`theme_name`, `primary_color`, `secondary_color`, `accent_color`, `logo_path`, `is_active`) VALUES
('Default - Blue & Yellow', '#0B4DA2', '#FFFFFF', '#F9C900', 'images/logo.png', 1),
('Green Theme', '#1B5E20', '#FFFFFF', '#FDD835', 'images/logo.png', 0),
('Purple Theme', '#4A148C', '#FFFFFF', '#FFD740', 'images/logo.png', 0),
('Red Theme', '#B71C1C', '#FFFFFF', '#FFD700', 'images/logo.png', 0),
('Teal Theme', '#00695C', '#FFFFFF', '#FFB300', 'images/logo.png', 0),
('Navy Blue', '#1A237E', '#FFFFFF', '#FFC107', 'images/logo.png', 0);

-- Update settings for Shaheen Public High School
UPDATE `settings` SET `setting_value` = 'SHAHEEN PUBLIC HIGH SCHOOL' WHERE `setting_key` = 'site_name';
UPDATE `settings` SET `setting_value` = 'sphs.pk.148@gmail.com' WHERE `setting_key` = 'site_email';
UPDATE `settings` SET `setting_value` = '03006700956' WHERE `setting_key` = 'site_phone';
UPDATE `settings` SET `setting_value` = 'Rahim Yar Khan, Punjab, Pakistan' WHERE `setting_key` = 'site_address';
UPDATE `settings` SET `setting_value` = 'Affiliated By Board of Intermediate & Secondary Education Bahawalpur' WHERE `setting_key` = 'affiliation';
UPDATE `settings` SET `setting_value` = 'Rahim Yar Khan' WHERE `setting_key` = 'city';
