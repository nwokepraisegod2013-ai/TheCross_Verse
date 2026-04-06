# 🎓 EduVerse Portal – Full Setup Guide

## Overview
EduVerse is a complete, professionally animated school registration portal supporting **two schools** with **age-based enrollment**, an **admin dashboard**, and a **PHP/MySQL backend**.

---

## 📁 File Structure

```
school-portal/
├── index.html          ← Landing page (animated, fun, academic)
├── login.html          ← Login page (students, parents, teachers)
├── register.html       ← Student registration (3-step form)
├── css/
│   ├── style.css       ← Main styles
│   ├── animations.css  ← All animations & keyframes
│   └── admin.css       ← Admin panel styles
├── js/
│   ├── main.js         ← Landing page interactions
│   └── admin.js        ← Admin CRUD & UI logic
├── admin/
│   └── index.html      ← Admin panel (full dashboard)
└── php/
    ├── config.php      ← DB connection & helpers
    ├── database.sql    ← Run once to set up MySQL tables
    ├── login.php       ← POST: authenticate users
    ├── register.php    ← POST: submit registration
    ├── users.php       ← CRUD: user management (admin)
    ├── content.php     ← CRUD: schools, age groups, announcements
    ├── settings.php    ← Settings management
    └── auth.php        ← Auth helpers (logout, password change)
```

---

## 🚀 Setup Instructions

### 1. Requirements
- PHP 8.0+
- MySQL 8.0+ or MariaDB 10.5+
- Web server: Apache / Nginx / XAMPP / WAMP

### 2. Database Setup

```bash
# Connect to MySQL
mysql -u root -p

# Run the setup SQL
source /path/to/school-portal/php/database.sql
```

Or via phpMyAdmin: import `php/database.sql`.

### 3. Configure Database Connection

Edit `php/config.php`:
```php
define('DB_HOST', 'localhost');   // your DB host
define('DB_USER', 'root');        // your DB username
define('DB_PASS', 'yourpassword');// your DB password
define('DB_NAME', 'eduverse_db'); // database name
```

### 4. Deploy Files

Copy the entire `school-portal/` folder to your web server root:
- XAMPP: `C:/xampp/htdocs/school-portal/`
- WAMP: `C:/wamp64/www/school-portal/`
- Linux: `/var/www/html/school-portal/`

### 5. Access the Portal

| Page | URL |
|------|-----|
| Landing Page | `http://localhost/school-portal/` |
| Login | `http://localhost/school-portal/login.html` |
| Register | `http://localhost/school-portal/register.html` |
| Admin Panel | `http://localhost/school-portal/admin/` |

---

## 🔐 Default Admin Login

| Field | Value |
|-------|-------|
| Username | `admin` |
| Password | `admin123` |

> ⚠️ **Change immediately** after first login via Admin → Settings → Change Password

---

## 🏫 Schools

| School | Key | Mascot |
|--------|-----|--------|
| BrightStar Academy | `brightstar` | 🦁 |
| Moonrise Institute | `moonrise` | 🦅 |

---

## 🎂 Age Groups

| Group | Ages | Level |
|-------|------|-------|
| 🌱 Tiny Sprouts | 3–5 | Nursery |
| 🌿 Junior Explorers | 6–8 | Primary 1–2 |
| 🌳 Discoverers | 9–11 | Primary 3–5 |
| 🚀 Pioneers | 12–14 | Junior High |
| 🏆 Champions | 15–18 | Senior High |

---

## ⚙️ Admin Panel Features

### Dashboard
- Live student/teacher/registration counters
- Recent registrations overview
- Students-by-school bar charts

### Registrations
- View all pending/approved/rejected applications
- Filter by school and status
- Approve & auto-create login credentials
- Export to CSV

### Users
- Add/edit/delete login accounts
- Set role: student / parent / teacher / admin
- Set school and age group
- Assign username and password

### School Profiles
- Edit school name, motto, description
- Update feature lists displayed on landing page

### Age Groups
- Create/edit/delete age groups
- Configure age ranges and level labels

### Announcements
- Post announcements (school-specific or all schools)
- Set priority level (normal/urgent/info)
- Delete outdated announcements

### Settings
- Change admin password
- Toggle registration open/closed
- Set portal name and admin email

---

## 🔄 Registration Workflow

```
Parent/Student visits register.html
         ↓
Fills 3-step registration form (personal → contact → interests)
         ↓
Data sent to php/register.php
         ↓
Saved to 'registrations' table (status: pending)
         ↓
Admin receives notification email
         ↓
Admin reviews in admin panel
         ↓
Clicks "Approve & Create Login" → auto-creates user account
         ↓
Student/Parent can now login with auto-generated credentials
```

---

## 🛡️ Security Notes

1. All passwords stored as **bcrypt hashes**
2. All user input **sanitized** with PDO prepared statements
3. Admin routes protected by **session checks**
4. CSRF tokens can be added for extra security (see config.php)
5. In production: enable HTTPS, set `session.cookie_secure = true`

---

## 🎨 Frontend Animations

The frontend includes:
- **Word reveal** hero title animation
- **Orbiting planet** hero visual with CSS keyframes
- **Floating emoji** background particles
- **Counter animation** on stats
- **Confetti burst** on CTA section
- **3D tilt effect** on school cards (mouse parallax)
- **Rotating mascots** and spinning logo
- **Staggered card reveals** on scroll
- **Pulse buttons** with ripple effects
- **Shimmer gradient** text effect

---

## 📧 Email Notifications

Email is sent via PHP's `mail()` function. For production, use a mail service:
- **PHPMailer** + SMTP (Gmail, SendGrid, etc.)
- Install: `composer require phpmailer/phpmailer`
- Replace `@mail()` call in `register.php`

---

Built with ❤️ for young learners everywhere!