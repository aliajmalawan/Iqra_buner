-- Premium brand palette for Bahawal College of Health Sciences
-- Run once on the live (cPanel) database via phpMyAdmin.
-- Primary Navy #102A56 · Medical Cyan #0BA6C9 (Gold #D9A62E and the rest live in css/style.css)
UPDATE `themes`
SET `primary_color` = '#102A56', `accent_color` = '#0BA6C9', `updated_at` = NOW()
WHERE `theme_name` = 'BCHS Navy & Cyan Theme';

-- OPTIONAL: homepage hero, slide 1 heading (only applies if the slide still has its old text;
-- skip this if you have customised the slide in AdminCP)
UPDATE `hero_carousel`
SET `title` = 'Shaping the Next Generation of Healthcare Professionals',
    `subtitle` = 'Degree programs that combine academic foundations with hands-on clinical training, preparing you for a meaningful career in healthcare.'
WHERE `title` = 'Bahawal College of Health Sciences' AND `display_order` = 1;
