# 🔐 Environment Setup Guide

## ✅ What Was Created

I've set up a professional environment configuration system for your Mabini Inventory System:

### 1. **`.env` file** - Your Local Configuration
Contains your database credentials and app settings:
```
DB_HOST=localhost
DB_NAME=mabini_inventory
DB_USER=root
DB_PASS=
```

### 2. **`.env.example` file** - Template for Team
A template file your team members can copy to create their own `.env`:
```bash
# Team members run this:
cp .env.example .env
# Then edit .env with their local database credentials
```

### 3. **`.gitignore` file** - Security
Updated to prevent sensitive `.env` file from being committed to GitHub.

### 4. **`config/env.php`** - Environment Loader
Loads variables from `.env` file into your PHP application.

### 5. **`config/database.php`** - Database Connection
Uses environment variables for database connection (no more hardcoded credentials!).

### 6. **`database/schema.sql`** - Database Structure
Complete SQL schema with all tables, relationships, and initial data.

### 7. **`setup_database.php`** - One-Click Setup
Web interface to create your database structure.

---

## 🚀 How to Use

### First Time Setup:

1. **Check your `.env` file** is configured correctly:
   ```
   DB_HOST=localhost       # Your MySQL server
   DB_NAME=mabini_inventory  # Database name
   DB_USER=root            # Your MySQL username
   DB_PASS=                # Your MySQL password (empty for XAMPP default)
   ```

2. **Run database setup**:
   - Open: http://localhost:8000/setup_database.php
   - Click "Setup Database Now"

3. **Load sample data** (optional):
   - Open: http://localhost:8000/load_sample_data.php
   - Click "Load Sample Data"

4. **Login to system**:
   - Username: `admin`
   - Password: `admin123`

---

## 👥 Team Member Setup

When your team members pull from GitHub, they should:

1. **Copy the example file**:
   ```bash
   cp .env.example .env
   ```

2. **Edit `.env` with their local credentials**:
   ```
   DB_HOST=localhost
   DB_NAME=mabini_inventory
   DB_USER=root
   DB_PASS=their_password_here
   ```

3. **Run setup_database.php** to create tables

4. **Import database dump** (if you provide one):
   ```bash
   mysql -u root -p mabini_inventory < team_database.sql
   ```

---

## 🔒 Security Benefits

### Before (Hardcoded):
```php
$host = "localhost";  // Exposed in code
$user = "root";       // Everyone sees this
$pass = "secret123";  // PASSWORD IN CODE! 😱
```

### After (.env):
```php
$host = env('DB_HOST');     // From .env file
$user = env('DB_USER');     // Not in Git
$pass = env('DB_PASS');     // Safe! 🔐
```

**Benefits:**
- ✅ Passwords never committed to GitHub
- ✅ Each team member uses their own credentials
- ✅ Easy to change settings per environment
- ✅ Production vs Development configs
- ✅ Industry standard practice

---

## 📝 Available Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_HOST` | localhost | MySQL server address |
| `DB_NAME` | mabini_inventory | Database name |
| `DB_USER` | root | MySQL username |
| `DB_PASS` | (empty) | MySQL password |
| `APP_NAME` | Mabini Inventory System | Application name |
| `APP_ENV` | development | Environment (development/production) |
| `APP_DEBUG` | true | Show detailed errors |
| `SESSION_LIFETIME` | 3600 | Session timeout in seconds |

---

## 🛠️ Common Tasks

### Change Database Password:
1. Edit `.env` file
2. Update `DB_PASS=your_new_password`
3. Restart dev server if needed

### Use Different Database:
1. Edit `.env` file
2. Change `DB_NAME=my_other_database`
3. Run setup_database.php again

### Switch to Production:
1. Edit `.env` file
2. Set `APP_ENV=production`
3. Set `APP_DEBUG=false`
4. Set strong `DB_PASS`

---

## ❓ Troubleshooting

### "Database connection failed"
- Check XAMPP MySQL is running
- Verify `.env` credentials are correct
- Make sure database exists

### ".env file not found"
- Create it from `.env.example`:
  ```bash
  cp .env.example .env
  ```

### "Access denied for user"
- Check `DB_USER` and `DB_PASS` in `.env`
- Verify user exists in MySQL
- Grant proper permissions

### Team member can't connect
- They need their own `.env` file
- `.env` is in `.gitignore` (won't be in Git)
- They must create it from `.env.example`

---

## 🎯 Next Steps

1. ✅ Verify `.env` settings are correct
2. ✅ Run http://localhost:8000/setup_database.php
3. ✅ Run http://localhost:8000/load_sample_data.php
4. ✅ Login with admin/admin123
5. ✅ Test the Item Master List
6. ✅ Commit and push (`.env` won't be included!)

---

## 📚 Why .env?

This is a **professional best practice** used by:
- Laravel (PHP framework)
- Symfony (PHP framework)
- Node.js applications
- Python Django/Flask
- Ruby on Rails
- And virtually all modern web applications

**You're now following industry standards!** 🎉
