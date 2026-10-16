# 🎠 Hero Carousel Management - Complete Guide

## Overview
Homepage pe ab rotating image carousel hai! AdminCP se multiple images upload karo aur wo automatically rotate hongi with beautiful transitions.

---

## ✨ Features

### ✅ What You Can Do:
1. **Multiple Images** - Up to unlimited carousel slides
2. **Auto Rotate** - Har 5 seconds mein automatically change hoti hai
3. **Manual Control** - Left/Right arrows se manually navigate karo
4. **Dot Indicators** - Bottom pe dots se directly kisi bhi slide pe jao
5. **Smooth Transitions** - Beautiful fade-in/fade-out effects
6. **Pause on Hover** - Mouse hover karne pe auto-play pause ho jata hai
7. **Custom Titles** - Har slide pe apna title aur subtitle daal sakte ho
8. **Order Control** - Slides ka order control karo
9. **Active/Inactive** - Kisi bhi slide ko show/hide karo

---

## 📍 How to Access

1. **Login to AdminCP:**
   - URL: `http://localhost/SHAHEENPUBLICHIGHSCHOOL/AdminCP/login.php`
   - Username: `admin`
   - Password: `sphs786`

2. **Navigate to Hero Carousel:**
   - Sidebar mein "Hero Carousel" pe click karo
   - Ya direct URL: `http://localhost/SHAHEENPUBLICHIGHSCHOOL/AdminCP/manage_hero_carousel.php`

---

## 🗄️ Database Setup (First Time Only)

### Step-by-Step:
1. **Open phpMyAdmin:**
   - URL: `http://localhost/phpmyadmin`

2. **Select Database:**
   - Left sidebar se `shaheenpublic_db` select karo

3. **Import SQL File:**
   - Top menu se "Import" tab pe click karo
   - "Choose File" pe click karo
   - Select: `add_hero_carousel_table.sql`
   - Scroll down aur "Go" button pe click karo
   - ✅ Table `hero_carousel` create ho jayega!

4. **Verify:**
   - Left sidebar mein `hero_carousel` table dikhai dega
   - Sample 3 slides already insert ho jayengi (optional)

---

## ➕ Adding Carousel Slides

### Step-by-Step Process:

1. **Click "Add New Slide" Button**
   - Top-right corner mein blue button

2. **Fill the Form:**

   **Slide Image*** (Required):
   - Click "Choose File"
   - Select your widescreen image
   - **Recommended Size:** 1920x600 pixels
   - **Aspect Ratio:** 16:9 (widescreen)
   - **Max Size:** 10MB
   - **Formats:** JPG, PNG, GIF, WEBP

   **Title** (Optional):
   - Slide ka main heading
   - Example: `Welcome to Shaheen Public High School`
   - Agar empty chhodo to default: `Welcome to [School Name]`

   **Subtitle** (Optional):
   - Title ke neeche subtitle
   - Example: `Empowering students with quality education`
   - Agar empty hai to subtitle show nahi hoga

   **Display Order:**
   - Number (0, 1, 2, 3...)
   - 0 = Pehle slide
   - 1 = Doosri slide
   - 2 = Teesri slide
   - Lower number = Pehle show hoga

   **Status:**
   - **Active** = Website pe show hoga
   - **Inactive** = Hidden rahega

3. **Click "Add Slide"**
   - Success message dikhega
   - New slide table mein add ho jayegi

---

## ✏️ Editing Slides

1. **Find the Slide** in the table
2. **Click "Edit" Button** (blue button)
3. **Update Information:**
   - Image: Naya upload karo ya purana rakhne ke liye empty chhodo
   - Title/Subtitle: Edit karo
   - Display Order: Change karo
   - Status: Active/Inactive toggle karo
4. **Click "Update Slide"**
   - Changes instantly save ho jayenge

---

## 🔄 Quick Actions

### Toggle Status (Active ↔ Inactive):
- **Click "Toggle" Button** (green)
- Active → Inactive ya Inactive → Active
- Instant change, no form needed!

### Delete a Slide:
- **Click "Delete" Button** (red)
- Confirmation popup aayega
- Confirm karne pe:
  - Database se delete hoga
  - Image file bhi delete ho jayegi
  - **Warning:** Permanent deletion!

---

## 📸 Image Guidelines

### Recommended Specifications:
- **Size:** 1920x600 pixels (widescreen)
- **Aspect Ratio:** 16:9
- **Format:** JPG (best for photos)
- **File Size:** 500KB - 2MB (optimized)
- **Max Size:** 10MB

### Image Tips:
1. **High Quality:** Use professional photos
2. **Good Lighting:** Bright, clear images
3. **Text Readable:** Agar image pe text hai to readable ho
4. **Consistent Style:** Similar style ke images use karo
5. **School Related:** Building, students, activities ki photos best hain

### Free Image Tools:
- **Resize:** [resize-image.net](https://resize-image.net)
- **Compress:** [tinypng.com](https://tinypng.com)
- **Free Photos:** [unsplash.com](https://unsplash.com/s/photos/school)

---

## 🎯 Carousel Behavior on Website

### Auto-Play Features:
- **Auto Rotate:** Har 5 seconds mein next slide
- **Smooth Transition:** Fade-in/fade-out effect (1.5 seconds)
- **Pause on Hover:** Mouse hover karne pe pause ho jata hai
- **Resume on Leave:** Mouse hata lo to wapas chalu ho jata hai

### Navigation Controls:
1. **Left Arrow (←):** Previous slide
2. **Right Arrow (→):** Next slide
3. **Dots (Bottom):** Directly kisi bhi slide pe jao
4. **All controls reset timer** - Click karne pe 5 second timer reset hota hai

### Animations:
- Title: Fade-in from bottom (1 second)
- Subtitle: Fade-in from bottom (1.2 seconds)
- Buttons: Fade-in from bottom (1.4 seconds)

---

## 📊 Display Order Examples

### Example 1: Simple 3-Slide Carousel
```
Display Order: 0 → "Welcome" slide (shows first)
Display Order: 1 → "Excellence" slide (shows second)
Display Order: 2 → "Facilities" slide (shows third)
```

### Example 2: Custom Priority
```
Display Order: 5 → "Special Event" slide
Display Order: 10 → "Admissions Open" slide
Display Order: 15 → "Results" slide
```

### Example 3: Seasonal
```
Display Order: 0 → "Summer Camp" (Active during summer)
Display Order: 1 → "Main Welcome" (Always active)
Display Order: 2 → "Winter Activities" (Inactive during summer)
```

---

## 🎨 Content Suggestions

### Slide 1: Welcome
- **Image:** School building exterior
- **Title:** Welcome to Shaheen Public High School
- **Subtitle:** Empowering students with quality education

### Slide 2: Excellence
- **Image:** Students in classroom
- **Title:** Excellence in Education
- **Subtitle:** Building tomorrow's leaders today

### Slide 3: Facilities
- **Image:** Computer lab / Science lab
- **Title:** Modern Learning Environment
- **Subtitle:** State-of-the-art facilities for holistic development

---

## 🚨 Troubleshooting

### Problem: Carousel not showing?
**Solution:**
1. Check if slides are "Active" status
2. Run SQL file: `add_hero_carousel_table.sql`
3. Clear browser cache (Ctrl + F5)
4. Check if at least 1 active slide exists

### Problem: Images not uploading?
**Solution:**
1. Check folder permissions: `images/hero/` should be writable
2. Check file size (max 10MB)
3. Check file format (JPG, PNG, GIF, WEBP only)
4. Try resizing image to recommended size

### Problem: Carousel not rotating?
**Solution:**
1. Check browser console for JavaScript errors (F12)
2. Make sure you have 2 or more active slides
3. Refresh the page completely (Ctrl + F5)

### Problem: Images look stretched or cropped?
**Solution:**
1. Use recommended size: 1920x600 pixels
2. Maintain 16:9 aspect ratio
3. Use image editing tool to crop properly
4. Test on different screen sizes

### Problem: Carousel too fast/slow?
**Solution:**
- Edit `index.php` line ~125
- Change: `setInterval(nextSlide, 5000);`
- 5000 = 5 seconds
- 3000 = 3 seconds
- 7000 = 7 seconds

---

## 💡 Pro Tips

1. **Limit Slides:**
   - 3-5 slides is ideal
   - Too many = visitors don't see all
   - Too few = becomes repetitive

2. **Image Quality:**
   - Use professional photos
   - Compress before upload
   - Test on mobile and desktop

3. **Text Overlay:**
   - Keep titles short (5-10 words)
   - Make sure text is readable
   - Avoid busy background images

4. **Consistent Theme:**
   - Use similar color schemes
   - Maintain consistent style
   - Same aspect ratio for all

5. **Update Regularly:**
   - Seasonal updates
   - Event-based changes
   - Keep content fresh

6. **Mobile Testing:**
   - Test on phone/tablet
   - Images should look good on all screens
   - Text should be readable

7. **Inactive vs Delete:**
   - Use "Inactive" for temporary removal
   - Use "Delete" only if never needed again
   - Keeps seasonal slides for next year

---

## 📋 Quick Start Checklist

- [ ] Login to AdminCP
- [ ] Navigate to "Hero Carousel"
- [ ] Click "Add New Slide"
- [ ] Upload widescreen image (1920x600 px)
- [ ] Add title and subtitle
- [ ] Set display order (0 for first)
- [ ] Set status to "Active"
- [ ] Click "Add Slide"
- [ ] Repeat for 2-3 more slides
- [ ] Visit homepage to see carousel!
- [ ] Test arrow navigation
- [ ] Test dot navigation
- [ ] Test auto-rotate (wait 5 seconds)

---

## 🔗 Related Features

- **Theme Manager:** Logo aur colors change karne ke liye
- **Settings Page:** Hero button text aur links change karne ke liye
- **Leadership Messages:** Leadership section manage karne ke liye

---

## 📝 Technical Details

### Database Table: `hero_carousel`
```sql
- id (Auto increment)
- image_path (Image file path)
- title (Slide title - optional)
- subtitle (Slide subtitle - optional)
- display_order (Sort order)
- status (active/inactive)
- created_at (Timestamp)
- updated_at (Timestamp)
```

### Files Modified:
- **AdminCP/manage_hero_carousel.php** - Management interface
- **index.php** - Carousel display
- **includes/settings_helper.php** - Helper functions
- **add_hero_carousel_table.sql** - Database schema

### Image Storage:
- **Directory:** `images/hero/`
- **Naming:** `slide_[timestamp]_[random].jpg`
- **Access:** `.htaccess` configured for public access

---

## 🎉 Features Summary

✅ **Easy Management** - AdminCP se sab kuch control karo
✅ **Beautiful Design** - Professional fade transitions
✅ **Auto-Play** - 5 seconds mein automatic rotation
✅ **Manual Control** - Arrows aur dots navigation
✅ **Responsive** - Mobile, tablet, desktop sab pe perfect
✅ **Pause on Hover** - User control
✅ **Custom Content** - Har slide pe apna title/subtitle
✅ **Flexible Order** - Kisi bhi order mein arrange karo
✅ **No Coding** - Bilkul bhi code edit nahi karna

---

Made with ❤️ for Shaheen Public High School
