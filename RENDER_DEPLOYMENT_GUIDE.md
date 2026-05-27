# TestFlow - Render Deployment Guide

## ✅ Pre-Deployment Checklist

- [ ] Code has been fixed for production
- [ ] Docker files created (Dockerfile, render.yaml, .dockerignore)
- [ ] Repository pushed to GitHub
- [ ] Render account created
- [ ] MySQL database configured on Render

---

## 📋 Step 1: Prepare Your GitHub Repository

### 1.1 Commit and Push Code
```bash
cd C:\xampp\htdocs\testflow

# Stage all changes
git add .

# Commit with message
git commit -m "Production-ready for Render: fix MIME detection, add Docker config"

# Push to GitHub
git push origin main
```

### 1.2 Verify Files Exist
Ensure these files are in your repository root:
- ✅ `Dockerfile` (created)
- ✅ `render.yaml` (created)
- ✅ `.dockerignore` (created)
- ✅ `config.php` (updated with env vars)
- ✅ `api/reports.php` (fixed MIME detection)
- ✅ `api/pdf_reports.php` (fixed MIME detection)
- ✅ All other PHP files

---

## 🔧 Step 2: Set Up MySQL Database on Render

### 2.1 Create MySQL Database
1. Go to https://render.com/dashboard
2. Click **New** → **Database**
3. Select **MySQL**
4. Configure:
   - **Name**: `testflow-db`
   - **Database Name**: `testflow`
   - **Plan**: Standard ($15/month) or PostgreSQL for free
5. Click **Create Database**
6. Wait 2-3 minutes for database to initialize

### 2.2 Note Database Credentials
Once created, you'll see:
- **Hostname** (e.g., `mysql-server.xyz.render.com`)
- **Port** (usually `3306`)
- **Username** (e.g., `avnadmin`)
- **Password** (auto-generated, save it!)
- **Database** (should be `testflow`)

**Save these credentials** — you'll need them in Step 3.

### 2.3 Import Database Schema
1. In Render dashboard, click your database
2. Go to **Connection** → Copy the connection string
3. Connect using MySQL client:
   ```bash
   mysql -h hostname -u username -p
   ```
4. Create database and import schema:
   ```sql
   CREATE DATABASE IF NOT EXISTS testflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE testflow;
   
   -- Then import your database.sql file
   -- Copy content from database.sql and paste here
   ```

**OR use phpMyAdmin** (if your MySQL add-on supports it):
- Find phpMyAdmin URL in database panel
- Log in with credentials
- Create `testflow` database
- Click **Import** → select `database.sql`

---

## 🚀 Step 3: Deploy to Render

### 3.1 Create Web Service
1. Go to https://render.com/dashboard
2. Click **New** → **Web Service**
3. Select **GitHub** (you may need to authorize first)
4. Find and select your `testflow` repository
5. Configure:
   - **Name**: `testflow-api` (or your choice)
   - **Environment**: `Docker` (should auto-detect from Dockerfile)
   - **Plan**: `Free` (for testing) or `Standard` (for production)
   - **Branch**: `main`

### 3.2 Add Environment Variables
**CRITICAL**: Set database credentials before deploying!

In the "Environment Variables" section, add:

| Key | Value | Example |
|-----|-------|---------|
| `DB_HOST` | Your MySQL hostname | `mysql-server.xyz.render.com` |
| `DB_USER` | Your MySQL username | `avnadmin` |
| `DB_PASS` | Your MySQL password | `your_password_here` |
| `DB_NAME` | Database name | `testflow` |
| `DB_PORT` | Port (usually 3306) | `3306` |

### 3.3 Deploy
1. Click **Create Web Service**
2. Render will:
   - Build Docker image (~2-3 minutes)
   - Deploy your app
   - Assign you a public URL (e.g., `testflow-api.onrender.com`)

**Watch the deployment logs** to ensure it succeeds.

---

## ✅ Step 4: Verify Deployment

### 4.1 Check Service Status
- Go to your service in Render dashboard
- Look for green checkmark ✅ (service is live)
- URL should show as active

### 4.2 Test Your App
Open in browser:
```
https://testflow-api.onrender.com/testflow/index.html
```

### 4.3 Verify Database Connection
In the app sidebar, you should see:
- ✅ **MySQL ✓** badge (green) = database connected
- ❌ If red, check environment variables

### 4.4 Test API Endpoints
Open browser developer console (F12) and test:

```javascript
// Test list projects/cases
fetch('https://testflow-api.onrender.com/testflow/api/cases.php?project=telemed')
  .then(r => r.json())
  .then(d => console.log(d))

// Test PDF generation
fetch('https://testflow-api.onrender.com/testflow/api/generate_report.php?project=telemed&notes=Production test')
  .then(r => r.blob())
  .then(d => console.log('PDF generated!'))
```

---

## 🔐 Security Best Practices (Production)

### 1. Update CORS Policy (If Needed)
In `config.php`, currently:
```php
header('Access-Control-Allow-Origin: *');
```

For production, restrict to your domain:
```php
header('Access-Control-Allow-Origin: https://yourdomain.com');
```

### 2. File Upload Directory
- Use **Render Disks** for persistent file storage ($20/month extra)
- Or **AWS S3** for cloud file storage (free tier available)
- Without persistent storage, uploads will be deleted on redeploy

### 3. Environment Variables
- ✅ Never hardcode database passwords
- ✅ Use Render's environment variable system
- ✅ Rotate credentials regularly

### 4. Database Backups
- Enable automatic backups in Render MySQL settings
- Regularly download backups for safety

---

## 🆘 Troubleshooting

| Problem | Solution |
|---------|----------|
| **"Cannot connect to database"** | Check environment variables match your MySQL credentials |
| **PDF generation fails** | Ensure Python3 and reportlab installed (Dockerfile handles this) |
| **File uploads disappear after redeploy** | Expected behavior. Use Render Disks or S3 for persistence |
| **"Unhealthy service"** | Check logs in Render dashboard. Likely a database connection issue |
| **Docker build fails** | Check Dockerfile syntax. Make sure all files committed to GitHub |
| **Port already in use** | Render assigns port 80 automatically. Don't hardcode ports |

### View Logs
In Render dashboard:
1. Go to your service
2. Click **Logs** tab
3. Look for errors and debug

### Force Redeploy
1. Go to your service
2. Click **Manual Deploy** → **Deploy latest commit**
3. Watch logs for issues

---

## 📁 File Structure After Changes

```
testflow/
├── Dockerfile              ← Created ✅
├── render.yaml            ← Created ✅
├── .dockerignore          ← Created ✅
├── config.php             ← Updated ✅ (uses env vars)
├── index.html
├── database.sql
├── README.md
├── api/
│   ├── cases.php
│   ├── runs.php
│   ├── reports.php        ← Fixed ✅ (finfo)
│   ├── pdf_reports.php    ← Fixed ✅ (finfo)
│   └── generate_report.php
├── scripts/
│   └── generate_report.py
├── pdf_uploads/           ← Will be created
└── uploads/               ← Will be created
```

---

## 💾 Environment Variables Summary

For quick reference, these are the environment variables your app needs:

```
DB_HOST=mysql.your-render.com
DB_PORT=3306
DB_USER=avnadmin
DB_PASS=your_password
DB_NAME=testflow
```

Set these in Render dashboard **before** first deployment.

---

## 🎯 What's Production-Ready Now

✅ **MIME Detection** - Fixed with `finfo_file()`
✅ **Environment Variables** - Database credentials now configurable
✅ **Docker Container** - Proper production image with PHP 8.1 + Apache
✅ **Python Support** - reportlab installed for PDF generation
✅ **Health Checks** - Render monitors your app
✅ **File Permissions** - Properly configured for Apache
✅ **Database Schema** - Ready to import

---

## 📞 Need Help?

If something goes wrong:

1. **Check Render Logs**: Dashboard → Your Service → Logs
2. **Verify Env Variables**: Make sure DB credentials are correct
3. **Test Database**: Try connecting with MySQL client
4. **Restart Service**: Manual Deploy button in Render dashboard

---

## 🎉 You're Done!

Once deployment succeeds:
- ✅ Your app is live at `https://testflow-api.onrender.com`
- ✅ All features work (including Python PDF generation)
- ✅ Database is secure with environment variables
- ✅ Auto-scales if traffic increases
- ✅ HTTPS enabled by default

**Next steps**:
- Set up custom domain (optional)
- Configure persistent file storage (if needed)
- Set up monitoring/alerts
- Regular backups of database

---

**Questions?** Check Render's documentation: https://render.com/docs
