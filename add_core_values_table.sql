-- Add Core Values Management Table
-- Run this SQL in phpMyAdmin

USE shaheenpublic_db;

CREATE TABLE IF NOT EXISTS `core_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(50) DEFAULT 'fas fa-star' COMMENT 'Font Awesome class, e.g. fas fa-star',
  `color` varchar(20) DEFAULT '#0B7275' COMMENT 'Hex color for icon and accent',
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed with the values previously hardcoded on core-values.php
INSERT INTO `core_values` (`title`, `description`, `icon`, `color`, `display_order`, `status`) VALUES
('Excellence', 'We strive for the highest standards in teaching, learning, and all our educational programs.', 'fas fa-star', '#0B7275', 1, 'active'),
('Integrity', 'We uphold honesty, transparency, and ethical conduct in all our interactions.', 'fas fa-handshake', '#2e7d32', 2, 'active'),
('Innovation', 'We embrace new ideas, modern teaching methods, and continuous improvement.', 'fas fa-lightbulb', '#F0A500', 3, 'active'),
('Respect', 'We value diversity, dignity, and mutual respect among all members.', 'fas fa-heart', '#e53935', 4, 'active'),
('Community', 'We foster a supportive learning environment and positive relationships.', 'fas fa-users', '#7b1fa2', 5, 'active'),
('Social Responsibility', 'We prepare students to be responsible citizens who contribute positively.', 'fas fa-globe', '#00695c', 6, 'active');
