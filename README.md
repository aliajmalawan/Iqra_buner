# Fort Education System - Modern Educational Website

A professional, modern, and responsive educational website built with PHP, MySQL, HTML5, CSS3, and JavaScript.

## Features

### Frontend Features
- **Modern Responsive Design** - Works seamlessly on all devices
- **Hero Section** with engaging content and call-to-action buttons
- **About Us** - Mission, vision, values, and leadership message
- **Courses** - Dynamic course listings with detailed information
- **Admission System** - Online admission form with document upload
- **Faculty** - Showcase experienced teachers and their qualifications
- **Events & News** - Display upcoming events and latest announcements
- **Gallery** - Photo and video gallery with lightbox functionality
- **Contact** - Contact form, map integration, and working hours
- **Smooth Animations** - Professional animations and transitions
- **Mobile Menu** - Responsive navigation for mobile devices

### Admin Panel Features
- **Secure Login System** - Password-protected admin access
- **Dashboard** - Overview with statistics and quick actions
- **Manage Courses** - Add, edit, and delete courses
- **Manage Faculty** - Add and manage faculty members
- **Manage Admissions** - View and approve/reject applications
- **Manage Contacts** - View and respond to contact messages
- **Statistics** - Real-time data on courses, faculty, admissions, and contacts

## Color Scheme

- **Primary Color:** #0B4DA2 (Blue)
- **Secondary Color:** #FFFFFF (White)
- **Accent Color:** #F9C900 (Yellow/Gold)

## Installation Instructions

### Prerequisites
- XAMPP/WAMP/MAMP (or any local server with PHP and MySQL)
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Step 1: Setup Files
1. Copy all files to your `htdocs` folder (for XAMPP) or web root directory
2. The folder structure should be: `htdocs/forteducationsystem/`

### Step 2: Create Database
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Click on "Import" tab
3. Choose the `database.sql` file from the project folder
4. Click "Go" to import the database

Alternatively, you can:
1. Create a new database named `forteducation_db`
2. Copy and paste the contents of `database.sql` into the SQL tab
3. Execute the queries

### Step 3: Configure Database Connection
The database configuration is already set up in `includes/config.php`:
- **Host:** localhost
- **Username:** root
- **Password:** (empty)
- **Database:** forteducation_db

If your setup is different, edit the file `includes/config.php` and update the database credentials.

### Step 4: Set Folder Permissions
Ensure the following folders have write permissions:
- `uploads/documents/`
- `uploads/gallery/`

### Step 5: Access the Website

**Frontend Website:**
- URL: http://localhost/forteducationsystem/
- Browse all pages: Home, About, Courses, Admission, Faculty, Events, Gallery, Contact

**Admin Panel:**
- URL: http://localhost/forteducationsystem/admin/login.php
- **Default Username:** admin
- **Default Password:** admin123

**Important:** Change the default admin password after first login!

## Folder Structure

```
forteducationsystem/
├── admin/                  # Admin panel files
│   ├── login.php          # Admin login
│   ├── dashboard.php      # Admin dashboard
│   ├── logout.php         # Logout functionality
│   ├── manage_admissions.php
│   └── manage_contacts.php
├── css/                   # Stylesheets
│   └── style.css         # Main CSS file
├── js/                    # JavaScript files
│   └── main.js           # Main JavaScript
├── images/               # Images and media
├── includes/             # PHP includes
│   ├── config.php       # Database configuration
│   ├── header.php       # Header template
│   └── footer.php       # Footer template
├── uploads/              # Upload directories
│   ├── documents/       # Admission documents
│   └── gallery/         # Gallery images
├── index.php            # Home page
├── about.php            # About us page
├── courses.php          # Courses page
├── admission.php        # Admission form page
├── faculty.php          # Faculty page
├── events.php           # Events & news page
├── gallery.php          # Gallery page
├── contact.php          # Contact page
├── database.sql         # Database schema
└── README.md            # This file
```

## Database Tables

1. **admin_users** - Admin login credentials
2. **courses** - Course information
3. **faculty** - Faculty members
4. **admissions** - Student admission applications
5. **events** - Events and activities
6. **news** - News and announcements
7. **gallery** - Photo and video gallery
8. **contacts** - Contact form submissions
9. **settings** - Site settings and configuration

## Usage Guide

### Adding Courses (Admin)
1. Login to admin panel
2. Click "Courses" in the navigation
3. Add course details: name, description, duration, fee
4. Save and publish

### Managing Admissions
1. View all admission applications in the admin panel
2. Review student details and documents
3. Approve or reject applications
4. Applications are automatically stored in the database

### Customization

#### Changing Colors
Edit `css/style.css` and modify the CSS variables:
```css
:root {
    --primary-color: #0B4DA2;
    --secondary-color: #FFFFFF;
    --accent-color: #F9C900;
}
```

#### Adding Logo
1. Place your logo image in the `images/` folder as `logo.png`
2. The logo will automatically appear in the header

#### Updating Contact Information
Edit the following files:
- `includes/header.php` - Top bar contact info
- `includes/footer.php` - Footer contact details
- `contact.php` - Contact page information

#### Adding Google Maps
In `contact.php`, replace the map placeholder with your Google Maps embed code:
```html
<iframe src="YOUR_GOOGLE_MAPS_EMBED_URL" width="100%" height="300"></iframe>
```

## Security Notes

1. **Change Default Admin Password**
   - Login with default credentials
   - Go to settings and update password

2. **Update Database Credentials**
   - For production, use strong database passwords
   - Never commit `config.php` with production credentials

3. **File Upload Security**
   - Validate file types and sizes
   - Implement virus scanning for uploads
   - Store uploads outside web root in production

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Technologies Used

- **Frontend:** HTML5, CSS3, JavaScript
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Icons:** Font Awesome 6.4.0
- **Design:** Custom responsive CSS framework

## Support

For issues or questions:
- Check the Facebook page: https://www.facebook.com/forteducationsystem
- Review the code comments in each file
- Consult PHP and MySQL documentation

## Credits

Developed for Fort Education System
Design Philosophy: Modern, clean, and user-friendly educational platform

## License

This project is proprietary software developed for Fort Education System.

---

**Note:** This is a complete, production-ready educational website with all essential features. Customize the content, colors, and images to match your institution's branding!
