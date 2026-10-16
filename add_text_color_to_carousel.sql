-- Add text_color column to hero_carousel table
-- Run this SQL in phpMyAdmin

ALTER TABLE `hero_carousel`
ADD COLUMN `text_color` varchar(20) DEFAULT '#ffffff' AFTER `subtitle`;

-- Update existing slides to have white text color
UPDATE `hero_carousel` SET `text_color` = '#ffffff';
