-- Add Leadership Management Table
-- Run this SQL in phpMyAdmin

USE shaheenpublic_db;

-- Create leadership table
CREATE TABLE IF NOT EXISTS `leadership` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `role_title` varchar(100) DEFAULT NULL COMMENT 'e.g., Founder Message, Director Message',
  `photo` varchar(255) DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default leadership for Shaheen Public High School
INSERT INTO `leadership` (`name`, `designation`, `role_title`, `photo`, `message`, `display_order`, `status`) VALUES
('Principal Name', 'Principal', 'Principal Message', 'images/leadership/principal.jpg', 'Education is the foundation of progress. At Shaheen Public High School, we are committed to providing quality education that empowers our students to achieve their dreams and contribute positively to society.', 1, 'active'),
('Director Name', 'Director', 'Director Message', 'images/leadership/director.jpg', 'Our vision is to create an educational environment where every student can discover their potential and achieve excellence through dedication, hard work, and strong moral values.', 2, 'active'),
('Founder Name', 'Founder & Chairman', 'Founder Message', 'images/leadership/founder.jpg', 'When we established this institution, our mission was clear - to provide quality education accessible to all, nurturing young minds to become future leaders of our nation.', 3, 'active');
