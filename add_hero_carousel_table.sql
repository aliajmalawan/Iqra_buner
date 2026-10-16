-- Hero Carousel Images Table
-- Run this SQL in phpMyAdmin to create the hero carousel table

CREATE TABLE IF NOT EXISTS `hero_carousel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_path` varchar(255) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `subtitle` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample carousel slides (optional)
INSERT INTO `hero_carousel` (`image_path`, `title`, `subtitle`, `display_order`, `status`) VALUES
('images/hero/hero_1.jpg', 'Welcome to Shaheen Public High School', 'Empowering students with quality education', 1, 'active'),
('images/hero/hero_2.jpg', 'Excellence in Education', 'Building tomorrow\'s leaders today', 2, 'active'),
('images/hero/hero_3.jpg', 'Modern Learning Environment', 'State-of-the-art facilities for holistic development', 3, 'active');
