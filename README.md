# TestFlow — Setup Guide (XAMPP / WAMP / MAMP)

## Folder Structure
```
testflow/
├── index.html        ← Main app (open this in browser)
├── config.php        ← DB connection settings
├── database.sql      ← Import this into phpMyAdmin
└── api/
    ├── cases.php     ← Test Cases API
    └── runs.php      ← Test Runs API
```

---

## Step 1 — Copy to htdocs
Copy the entire `testflow/` folder into your server root:
- **XAMPP**: `C:/xampp/htdocs/testflow/`
- **WAMP**: `C:/wamp64/www/testflow/`
- **MAMP**: `/Applications/MAMP/htdocs/testflow/`

---

## Step 2 — Import the Database
1. Open your browser → go to `http://localhost/phpmyadmin`
2. Click **"New"** in the left sidebar → name it `testflow` → click **Create**
3. Click the `testflow` database → click the **Import** tab
4. Click **"Choose File"** → select `database.sql` → click **Go**
5. You should see "Import has been successfully finished"

✅ `database.sql` includes all 5 tables (projects, test_cases, test_runs, reports, pdf_reports) plus seed data.
---

## Step 3 — Configure DB Connection (if needed)
Open `config.php` and update if your setup differs:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // your MySQL username
define('DB_PASS', '');       // your MySQL password
define('DB_NAME', 'testflow');
```

---

## Step 4 — Open the App
Go to: [http://localhost/testflow/index.html](http://localhost/testflow/index.html)

The green **MySQL ✓** badge in the sidebar confirms the database is connected.

---

## Hosting
### Local hosting with XAMPP / WAMP / MAMP
1. Copy `testflow/` into your web server root:
   - XAMPP: `C:/xampp/htdocs/testflow/`
   - WAMP: `C:/wamp64/www/testflow/`
   - MAMP: `/Applications/MAMP/htdocs/testflow/`
2. Start Apache and MySQL in your control panel.
3. Open `http://localhost/phpmyadmin` and create a database named `testflow`.
4. Import `database.sql`.
5. Open `http://localhost/testflow/index.html`.

### Remote hosting on a PHP/MySQL server
1. Upload the `testflow/` folder to your host’s web root (for example: `public_html/testflow`).
2. Create a MySQL database in your hosting control panel.
3. Update `config.php` with your hosting credentials:
```php
define('DB_HOST', 'your_host');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_pass');
define('DB_NAME', 'testflow');
```
4. Import `database.sql` into the new database.
5. Visit `https://yourdomain.com/testflow/index.html`.

### Important notes
- Ensure `pdf_uploads/` and `uploads/` directories exist and are writable by the web server.
- PHP must be enabled on the host so `api/*.php` can run.
- Always import `database.sql` (it contains all required tables with seed data).

---

## Troubleshooting
| Problem | Fix |
|---|---|
| "Cannot reach the PHP API" | Make sure Apache & MySQL are started in XAMPP |
| DB connection failed | Check `config.php` credentials match your MySQL setup |
| 404 on API calls | Confirm the folder is named `testflow` inside `htdocs` |
| Empty tables | Re-import `database.sql` — make sure you selected the right DB |
