# 📊 Database Deployment Guide for Render

## Overview

Your TestFlow application uses a single, consolidated SQL file: **`database.sql`**

This guide explains how to deploy and manage your database in production on Render.

---

## 📋 Database Structure

### Single Source of Truth: `database.sql`

Your `database.sql` file is **complete and production-ready**:

| Table | Purpose | Rows |
|-------|---------|------|
| `projects` | Projects/products being tested | 1 (seed) |
| `test_cases` | Individual test cases | 6 (seed) |
| `test_runs` | Test run history | 0 (grows over time) |
| `reports` | File reports (PDF, Excel, etc) | 0 (grows over time) |
| `pdf_reports` | PDF reports storage | 0 (grows over time) |

**Total**: 5 tables, all relationships configured, all seed data included

---

## 🚀 Deployment Steps for Render

### Step 1: Create MySQL Database on Render

**In Render Dashboard:**

1. Go to https://render.com/dashboard
2. Click **New** → **Database**
3. Select **MySQL**
4. Configure:
   - **Name**: `testflow-db` (or your choice)
   - **Database**: `testflow`
   - **Region**: Closest to you
   - **Plan**: Standard ($15/month)
5. Click **Create Database**
6. Wait 2-3 minutes for initialization

**After creation, note:**
- **Hostname**: e.g., `mysql-abc123.xyz.render.com`
- **Port**: Usually `3306`
- **Username**: e.g., `avnadmin` (auto-generated)
- **Password**: Auto-generated, save it!
- **Database**: `testflow`

---

### Step 2: Import Schema Using phpMyAdmin

**Easy Method (Recommended):**

1. In Render dashboard, open your MySQL database
2. Find **phpMyAdmin** link (may be in "Connections" section)
3. Click phpMyAdmin link (opens in new tab)
4. Log in with credentials from Step 1
5. Click **New** (or **Create**)
6. Create database named `testflow`
7. Click on `testflow` database
8. Click **Import** tab
9. Click **Choose File**
10. Select **`database.sql`** from your repository
11. Click **Go**
12. Wait for success message

✅ All 5 tables created with seed data!

---

### Step 3: Verify Database Import

**Still in phpMyAdmin:**

```sql
-- Check tables exist
SHOW TABLES;
-- Should show: pdf_reports, projects, reports, test_cases, test_runs

-- Check seed data
SELECT COUNT(*) FROM projects;     -- Should show: 1
SELECT COUNT(*) FROM test_cases;   -- Should show: 6
```

**Or use command line:**

```bash
mysql -h your_hostname -u your_username -p your_database

# Inside mysql:
SHOW TABLES;
SELECT * FROM projects;
```

---

### Step 4: Set Environment Variables in Web Service

When creating your Web Service on Render, add these variables:

```
DB_HOST = (your MySQL hostname from Step 1)
DB_USER = (your MySQL username)
DB_PASS = (your MySQL password)
DB_NAME = testflow
DB_PORT = 3306
```

Your `config.php` will read these and connect automatically.

---

## 🔄 Database Backup & Restore

### Automatic Backups (Recommended)

**On Render:**
1. Go to your MySQL database
2. Look for **Backups** section
3. Enable automatic backups (usually enabled by default)
4. Backups occur automatically daily

### Manual Backup

**From phpMyAdmin:**
1. Click on `testflow` database
2. Click **Export** tab
3. Select format: **SQL**
4. Click **Go**
5. Save the downloaded file

**From Command Line:**
```bash
mysqldump -h your_hostname -u your_username -p your_database > backup.sql
```

### Restore from Backup

**If database gets corrupted:**

1. In phpMyAdmin, delete the corrupted database
2. Create new database `testflow`
3. Import your backup SQL file (same as Step 2 above)

**Or command line:**
```bash
mysql -h your_hostname -u your_username -p your_database < backup.sql
```

---

## 📊 Database Growth & Monitoring

### Expected Data Growth

Over time, these tables will grow:
- `test_runs` - One row per test run
- `reports` - One row per uploaded report
- `pdf_reports` - One row per uploaded PDF

**Size estimate:**
- Small project (100 test runs, 50 reports): ~5-10 MB
- Large project (10K test runs, 1K reports): ~100-500 MB

### Monitor Database Size

**In phpMyAdmin:**
1. Click on `testflow` database
2. Look for **Database Size** info
3. All table sizes shown

**From MySQL:**
```sql
SELECT 
    SUM(ROUND(((data_length + index_length) / 1024 / 1024), 2)) AS size_mb
FROM information_schema.TABLES
WHERE table_schema = 'testflow';
```

### When to Upgrade

- **Current plan**: $15/month (5GB standard, can handle ~500K records)
- **Upgrade when**: Database approaches 80% capacity
- **Options**: Upgrade plan or implement archival strategy

---

## 🔐 Security Best Practices

### 1. **Strong Passwords**
✅ Use Render's auto-generated password (very strong)
✅ Never change to something simpler
✅ Rotate credentials periodically

### 2. **Access Control**
✅ Render limits access to your web service only (auto)
✅ No public internet access to database
✅ SSL/TLS encryption on all connections

### 3. **Data Protection**
✅ Automatic daily backups
✅ Encrypted backups
✅ Regular restore tests

### 4. **Credentials Management**
✅ Store in environment variables (not code) ✅ Already done!
✅ Never commit database password to Git
✅ Use .env for local development

---

## 🆘 Troubleshooting

### "Can't Connect to Database"

**Check these:**
1. Verify credentials in Render dashboard
2. Ensure environment variables set correctly:
   ```
   DB_HOST = exactly as shown in Render
   DB_USER = exactly as shown
   DB_PASS = exactly as shown
   DB_NAME = testflow
   DB_PORT = 3306
   ```
3. Check if MySQL service is running (green status in Render)
4. Try connecting manually:
   ```bash
   mysql -h hostname -u username -p
   ```

### "Tables Don't Exist"

**Solution:**
1. Check in phpMyAdmin if `testflow` database exists
2. Check if tables were created:
   ```sql
   SHOW TABLES;
   ```
3. If empty, re-import `database.sql`

### "Can't Import database.sql"

**Check:**
1. File size not too large (your file is ~3KB, should be fine)
2. Check for SQL errors in import result
3. Try importing via command line:
   ```bash
   mysql -h hostname -u username -p testflow < database.sql
   ```

### "Database Fills Up Too Fast"

**Solutions:**
1. Check what's growing: `SELECT * FROM test_runs ORDER BY created_at DESC LIMIT 10;`
2. Archive old data
3. Upgrade database plan
4. Implement retention policies

---

## 📈 Scaling Strategy

### Stage 1: Development (Now)
- Database size: < 100 MB
- Cost: $15/month
- Backups: Daily
- Status: ✅ Sufficient

### Stage 2: Growth (100+ Projects)
- Database size: 100-500 MB
- Cost: Still $15/month (unless upgrade needed)
- Action: Monitor closely

### Stage 3: Large Scale (1000+ Projects)
- Database size: > 500 MB
- Cost: Consider upgrade or archival
- Action: 
  - Archive old test runs
  - Move old reports to S3
  - Upgrade to larger MySQL plan

---

## 🔧 Advanced: Custom Migrations

### For Future Schema Changes

Instead of editing `database.sql`, create migrations:

**Structure:**
```
migrations/
  ├── 001_initial_schema.sql (= current database.sql)
  ├── 002_add_user_table.sql
  ├── 003_add_test_results_column.sql
  └── migrations_log.txt
```

**Migration format:**
```sql
-- migration_002_add_user_table.sql
-- Applied: 2026-05-27
-- Description: Add users and authentication

ALTER TABLE projects ADD COLUMN owner_id INT;
ALTER TABLE projects ADD FOREIGN KEY (owner_id) REFERENCES users(id);

CREATE TABLE IF NOT EXISTS users (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  PRIMARY KEY (id)
);
```

**Benefits:**
- Track schema changes
- Easy rollback
- Version control
- Document why changes were made

---

## 📋 Deployment Checklist

Before going live:

- [ ] MySQL database created on Render
- [ ] `database.sql` imported successfully
- [ ] All 5 tables exist and have data
- [ ] Environment variables set correctly
- [ ] PHP can connect to database
- [ ] Green ✅ badge shows in app
- [ ] API endpoints responding
- [ ] Backup strategy configured
- [ ] Test restore from backup

---

## 🎯 Summary

| Item | Detail |
|------|--------|
| **SQL File** | Single: `database.sql` (5 tables, complete) |
| **Import Method** | phpMyAdmin (easiest) or command line |
| **Database Size** | ~3KB SQL, grows with data |
| **Backup** | Automatic daily on Render |
| **Cost** | $15/month |
| **Scaling** | Handled by Render, upgrade as needed |

---

## 💡 Next Steps

1. ✅ Create MySQL database on Render
2. ✅ Import `database.sql` via phpMyAdmin
3. ✅ Verify all tables created
4. ✅ Set environment variables in Web Service
5. ✅ Deploy and test

**You're good to go!** 🚀
