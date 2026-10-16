# SHAHEEN PUBLIC HIGH SCHOOL - Website Setup Instructions

## School Information
- **Name:** SHAHEEN PUBLIC HIGH SCHOOL
- **Established:** 1984
- **Affiliation:** Board of Intermediate & Secondary Education Bahawalpur
- **City:** Rahim Yar Khan, Punjab, Pakistan
- **Contact:** 03006700956
- **Email:** sphs.pk.148@gmail.com

---

## Database Setup

### Step 1: Create Database
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named: `shaheenpublic_db`
3. Import all necessary tables (already configured if you created them)

### Step 2: Setup Admin User
1. Open browser and navigate to: `http://localhost/SHAHEENPUBLICHIGHSCHOOL/setup_admin.php`
2. This will create the admin user with credentials:
   - **Username:** admin
   - **Password:** sphs786
3. **IMPORTANT:** Delete `setup_admin.php` file after running it once!

---

## Admin Panel Access

### Login Details
- **URL:** http://localhost/SHAHEENPUBLICHIGHSCHOOL/AdminCP/login.php
- **Username:** admin
- **Password:** sphs786

---

## Theme Management System

### One-Click Theme Change Feature

Your website now has a powerful theme management system!

#### Access Theme Manager:
1. Login to Admin Panel
2. Click on **"Theme Manager"** in the sidebar
3. You will see all available themes

#### Features:
- ✅ **Change colors with one click** - Simply click "Activate" on any theme
- ✅ **Customize colors** - Edit Primary, Secondary, and Accent colors for each theme
- ✅ **Create new themes** - Add unlimited custom color schemes
- ✅ **Live preview** - See your theme selection immediately
- ✅ **Logo support** - Themes automatically adjust to your school logo

#### How to Change Theme:
1. Go to Admin Panel → Theme Manager
2. Browse available themes (Blue & Yellow, Green, Purple, Red)
3. Click **"Activate"** button on your preferred theme
4. The website will instantly update with new colors!

#### How to Customize Colors:
1. In Theme Manager, find the theme you want to modify
2. Use the color pickers to select new colors:
   - **Primary Color:** Main color (headers, buttons, navbar)
   - **Secondary Color:** Background color (usually white)
   - **Accent Color:** Highlight color (badges, borders)
3. Click **"Update Colors"** to save
4. If theme is active, changes apply immediately!

#### How to Create New Theme:
1. Scroll to "Create New Theme" section
2. Enter theme name (e.g., "Ocean Blue Theme")
3. Select colors using color pickers
4. Click **"Create Theme"**
5. Your new theme will appear in the list
6. Click "Activate" to apply it

---

## Logo Management

### ✨ New Feature: Upload Logo from Admin Panel!

You can now upload your school logo directly from the Admin Panel - no need to manually replace files!

#### To Change Logo (Easy Way):
1. Login to Admin Panel
2. Go to **Theme Manager**
3. Find the **"Upload School Logo"** section at the top
4. Click "Choose File" and select your logo image
5. Click **"Upload & Apply Logo"**
6. Done! Your new logo appears instantly on the website!

#### Supported Formats:
- PNG (Recommended - supports transparent background)
- JPG/JPEG
- GIF
- WEBP

#### Recommended Specifications:
- Size: 200x200 pixels
- Background: Transparent (for PNG files)
- Max file size: 5MB

#### Alternative Method (Manual):
If you prefer, you can still manually replace the file at: `images/logo.png`

---

## School Information Update

### ✨ Quick Edit from Theme Manager (NEW!)

You can now quickly update your school's basic information directly from Theme Manager!

#### To Update School Name & Contact Info (Quick Method):
1. Login to Admin Panel
2. Go to **Theme Manager**
3. Scroll to **"School Information (Quick Edit)"** section
4. Update any of these fields:
   - **School Name** - Your school's name (appears everywhere on website)
   - **Email Address** - School contact email
   - **Contact Number** - Phone number
   - **Address** - School location
5. Click **"Update School Information"**
6. Changes apply instantly to the website!

### Advanced Settings:

For more detailed settings, use the **Settings** page:
1. Login to Admin Panel
2. Go to **Settings** (or click "Advanced Settings" in Theme Manager)
3. Update the following sections:
   - **General Information:** School name, email, phone, address
   - **Home Page Content:** Hero section, taglines
   - **Statistics:** Student count, teacher count, etc.
   - **Social Media:** Facebook, Instagram, YouTube links
   - **Contact Information:** Maps, WhatsApp number

---

## Website Features

### ✅ Completed Features:
- Dynamic theme management (change colors with 1 click)
- Logo integration with theme system
- School information management
- Admin panel with secure login
- Settings page for content management
- Responsive design for mobile devices

### 🎨 Theme System Benefits:
- **Instant Updates:** Change entire website color scheme in seconds
- **No Coding Required:** Use simple color pickers
- **Unlimited Themes:** Create as many color schemes as you want
- **Professional Look:** Pre-designed themes ready to use
- **Customizable:** Every color can be adjusted to your preference

---

## Important Files

- **Config:** `includes/config.php` - Database connection
- **Theme System:** `includes/theme_helper.php` - Theme functions
- **Theme Manager:** `AdminCP/theme_manager.php` - Theme control panel
- **Settings:** `AdminCP/settings.php` - School information settings

---

## Support & Customization

If you need to make further changes:
1. All theme colors are stored in the `themes` table in database
2. Active theme is automatically loaded on every page
3. CSS variables are generated dynamically from database
4. Logo path is managed through theme system

---

## Security Reminders

1. ✅ Change admin password after first login
2. ✅ Delete `setup_admin.php` after initial setup
3. ✅ Keep database credentials secure
4. ✅ Regular backups of database

---

## Quick Start Guide

1. **Setup Database:** Create `shaheenpublic_db` and import tables
2. **Setup Admin:** Run `setup_admin.php` once
3. **Login:** Access admin panel at `AdminCP/login.php`
4. **Customize:** Use Theme Manager to select your colors
5. **Update Content:** Use Settings to update school information
6. **Add Logo:** Replace `images/logo.png` with your school logo

---

**Congratulations!** Your Shaheen Public High School website is ready to use! 🎉

**Theme Management is Now Just ONE CLICK Away!**
