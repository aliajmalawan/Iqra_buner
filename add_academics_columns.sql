-- Add missing columns so Courses & Faculty content matches what courses.php / faculty.php actually render
-- Run this SQL in phpMyAdmin

USE shaheenpublic_db;

ALTER TABLE courses ADD COLUMN category VARCHAR(20) DEFAULT 'regular' COMMENT 'regular, test, or short' AFTER fee;
ALTER TABLE faculty ADD COLUMN icon VARCHAR(50) DEFAULT 'fa-chalkboard-teacher' COMMENT 'Font Awesome class fallback avatar icon' AFTER photo;
