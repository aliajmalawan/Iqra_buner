# 📋 Leadership Messages Management - Complete Guide

## Overview
Ab aap apni website ki "Message from Our Leadership" section ko AdminCP se asaani se manage kar sakte ho! Photos, names, designations, aur messages sab kuch dynamic hai.

---

## 🎯 Features

### ✅ What You Can Do:
1. **Add New Leaders** - Naye leadership messages add karo
2. **Edit Existing Leaders** - Purane messages update karo
3. **Upload Photos** - Leader ki photo upload karo
4. **Upload Signatures** - Digital signatures add karo (optional)
5. **Reorder Display** - Kon pehle dikhega, control karo
6. **Activate/Deactivate** - Messages ko show/hide karo
7. **Delete Leaders** - Jo zaroorat na ho, delete karo

---

## 📍 How to Access

1. **Login to AdminCP:**
   - URL: `http://localhost/SHAHEENPUBLICHIGHSCHOOL/AdminCP/login.php`
   - Username: `admin`
   - Password: `sphs786`

2. **Navigate to Leadership Management:**
   - Sidebar mein "Leadership Messages" pe click karo
   - Ya direct URL: `http://localhost/SHAHEENPUBLICHIGHSCHOOL/AdminCP/manage_leadership.php`

---

## ➕ Adding a New Leader

### Step-by-Step Process:

1. **Click "Add New Leader" Button**
   - Green button top-right corner mein

2. **Fill in the Form:**
   - **Name*** (Required): Leader ka full name
     - Example: `Muhammad Ahmed Khan`

   - **Designation*** (Required): Leader ki position
     - Example: `Principal, Shaheen Public High School`

   - **Role Title*** (Required): Message ka title
     - Example: `Principal's Message` ya `پرنسپل کا پیغام`

   - **Photo** (Optional): Leader ki photo upload karo
     - Recommended size: 500x500 pixels (square)
     - Max file size: 5MB
     - Formats: JPG, PNG, GIF, WEBP

   - **Signature** (Optional): Digital signature
     - Recommended: PNG with transparent background
     - Max file size: 2MB
     - Height: ~50-100 pixels

   - **Message*** (Required): Leadership message
     - Multiple paragraphs allowed
     - Urdu/English dono likh sakte ho
     - Line breaks automatically convert hongi

   - **Display Order**: Number (0, 1, 2, 3...)
     - 0 = Pehle dikhega
     - 1 = Doosre number pe
     - 2 = Teesre number pe
     - Lower number = Pehle show hoga

   - **Status**: Active ya Inactive
     - Active = Website pe show hoga
     - Inactive = Hidden rahega

3. **Click "Add Leader"**
   - Success message dikhega
   - Table mein naya entry show hogi

---

## ✏️ Editing a Leader

1. **Find the Leader** in the table
2. **Click "Edit" Button** (blue button)
3. **Update the Information:**
   - Sab fields edit kar sakte ho
   - Photo/Signature: Agar new upload karo to purani replace ho jayegi
   - Agar chhodo to purani rahegi
4. **Click "Update Leader"**
   - Changes instantly save ho jayenge

---

## 🔄 Quick Actions

### Toggle Status (Active ↔ Inactive):
- **Click "Toggle" Button** (green)
- Active → Inactive ya Inactive → Active
- No need to open edit form!

### Delete a Leader:
- **Click "Delete" Button** (red)
- Confirmation popup aayega
- Confirm karne pe:
  - Database se delete hoga
  - Photos bhi delete ho jayengi
  - **Warning:** This is permanent!

---

## 📸 Image Upload Guidelines

### Leader Photos:
- **Best Size:** 500x500 pixels (square)
- **Aspect Ratio:** 1:1 (square) preferred
- **Format:** JPG or PNG
- **Max Size:** 5MB
- **Tips:**
  - Professional headshot use karo
  - Clear background
  - Good lighting
  - Face centered

### Signatures:
- **Best Format:** PNG with transparent background
- **Height:** 50-100 pixels
- **Max Size:** 2MB
- **Tips:**
  - Scan actual signature
  - Remove background (use online tools)
  - Save as PNG
  - Keep it small but readable

---

## 🎨 How It Displays on Website

### Layout Pattern:
1. **First Leader:** Photo on LEFT, Message on RIGHT
2. **Second Leader:** Photo on RIGHT, Message on LEFT
3. **Third Leader:** Photo on LEFT, Message on RIGHT
4. **Pattern repeats** - Alternating layout for visual variety

### What Visitors See:
- Leader's photo (or default icon if no photo)
- Role title (e.g., "Founder's Message")
- Name
- Designation
- Full message with quote styling
- Signature (if uploaded)

---

## 📊 Display Order Examples

### Example 1: Simple Order
```
Display Order: 0 → Founder (shows first)
Display Order: 1 → Principal (shows second)
Display Order: 2 → Director (shows third)
```

### Example 2: Custom Priority
```
Display Order: 5 → Director
Display Order: 10 → Principal
Display Order: 15 → Vice Principal
```
Even numbers badhe ho to order same rahega (lower first)

---

## ⚙️ Database Setup

### First Time Setup:
1. **Run the SQL file:**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Select database: `shaheenpublic_db`
   - Click "Import" tab
   - Choose file: `add_leadership_table.sql`
   - Click "Go"
   - Table `leadership` create ho jayega

### Table Structure:
```sql
- id (Auto increment)
- name (Leader's name)
- designation (Job title)
- role_title (Message title)
- photo (Image path)
- signature (Signature image path)
- message (Full message text)
- display_order (Sort order)
- status (active/inactive)
- created_at (Auto timestamp)
- updated_at (Auto timestamp)
```

---

## 🗂️ File Locations

### PHP Files:
- **Management Page:** `AdminCP/manage_leadership.php`
- **Display Template:** `includes/leadership_section.php`
- **Helper Functions:** `includes/settings_helper.php`
- **Database Schema:** `add_leadership_table.sql`

### Image Upload Directories:
- **Leadership Photos:** `images/leadership/`
- **Signatures:** `images/signatures/`
- Both folders have `.htaccess` for security

---

## 🚨 Troubleshooting

### Problem: Images not uploading?
**Solution:**
1. Check folder permissions: `images/leadership/` and `images/signatures/` should be writable
2. Check file size (5MB for photos, 2MB for signatures)
3. Check file format (JPG, PNG, GIF, WEBP only)

### Problem: Leadership section not showing?
**Solution:**
1. Check if leaders are "Active" status
2. Run SQL file to create table: `add_leadership_table.sql`
3. Clear browser cache and refresh

### Problem: Display order not working?
**Solution:**
1. Make sure numbers are different for each leader
2. Lower number = Shows first
3. Refresh the page after saving

### Problem: Photos too large or small?
**Solution:**
1. Use image editing software to resize
2. Recommended: 500x500 pixels
3. Use online tools like: resize-image.net

---

## 💡 Pro Tips

1. **Professional Photos:**
   - Use high-quality headshots
   - Consistent background colors
   - Same size for all leaders

2. **Writing Messages:**
   - Keep paragraphs short and readable
   - Use inspirational quotes
   - Personal touch adds value

3. **Signatures:**
   - Actual handwritten signatures look best
   - Scan and remove background
   - Keep file size small

4. **Display Order:**
   - Founder usually comes first (order: 0)
   - Principal/Director next (order: 1, 2)
   - Others follow (order: 3, 4, 5...)

5. **Status Management:**
   - Use "Inactive" instead of deleting
   - Easy to reactivate later
   - Keeps history

---

## 📝 Example Leadership Entry

```
Name: Muhammad Ahmed Khan
Designation: Principal, Shaheen Public High School
Role Title: Principal's Message
Photo: Upload professional headshot
Signature: Upload PNG signature
Message:
"Education is the passport to the future, for tomorrow belongs to those who prepare for it today.

At Shaheen Public High School, we are committed to providing quality education that shapes not just students' minds, but their character and future.

Our dedicated faculty and modern facilities ensure that every student receives the attention and resources they need to excel.

Join us on this journey of knowledge and growth."

Display Order: 1
Status: Active
```

---

## 🎉 Quick Start Checklist

- [ ] Login to AdminCP
- [ ] Navigate to "Leadership Messages"
- [ ] Click "Add New Leader"
- [ ] Fill in required fields (Name, Designation, Role Title, Message)
- [ ] Upload photo (optional but recommended)
- [ ] Upload signature (optional)
- [ ] Set display order (0 for first)
- [ ] Set status to "Active"
- [ ] Click "Add Leader"
- [ ] Check website homepage to see the message!

---

## 🔗 Related Features

- **Theme Manager:** Logo aur colors change karne ke liye
- **Settings Page:** Hero content aur footer update karne ke liye
- **Manage Faculty:** Teachers ki information add karne ke liye

---

Made with ❤️ for Shaheen Public High School
