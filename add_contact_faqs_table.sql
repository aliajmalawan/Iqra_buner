-- Add Contact page FAQs table
-- Run this SQL in phpMyAdmin

USE shaheenpublic_db;

CREATE TABLE IF NOT EXISTS `contact_faqs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed with the FAQs previously hardcoded on contact.php
INSERT INTO `contact_faqs` (`question`, `answer`, `display_order`, `status`) VALUES
('What are the admission requirements?', 'Requirements vary by program. Generally needed: previous educational certificates, CNIC/B-Form, passport photos, and admission fee. Visit our Admission page for full details.', 1, 'active'),
('How can I apply for admission?', 'Apply online through our Admission page, or visit our campus in person. Fill the form, submit documents, and pay the admission fee.', 2, 'active'),
('What is the fee structure?', 'Fees range from Rs. 3,000/month (short courses) to Rs. 8,000/month (entry test prep). Matric: Rs. 5,000/mo. Intermediate: Rs. 6,000/mo. One-time admission fee: Rs. 2,000.', 3, 'active'),
('Do you offer scholarships?', 'Yes! We offer merit-based and need-based scholarships. Sibling discounts and installment plans are also available. Contact our admission office for details.', 4, 'active'),
('How can I contact a specific teacher?', 'Visit our campus during office hours or call us. We will connect you with the right faculty member or department directly.', 5, 'active');
