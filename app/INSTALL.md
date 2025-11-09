# Creative Chaos Portal v2 — DreamHost Shared Hosting Guide

**Features**: Team/Open registration (7 writers included; $10 each additional), dynamic team roster, submissions, author showcase, admin dashboard, CSV export, secure downloads, optional Google Sheets sync.

## 1) Prep Your DreamHost Site
- In the DreamHost Panel open **Websites → Manage Websites → [your site] → Manage**, then set **PHP Mode** to at least **PHP 8.0** (8.2 recommended).
- Note your domain path (e.g., `/home/username/example.com`) — you will upload the app inside it.

## 2) Create a MySQL Database
- In the DreamHost Panel go to **More → MySQL Databases**.
- Create a database and user. Record the **hostname** (e.g., `mysql.example.com`), **database name**, **username**, and **password**.

## 3) Upload the Application
- Upload the repository contents (the `app/` directory and supporting files) into your site directory using SFTP or WebFTP.
- You may place the portal in a subdirectory (e.g., `/creative-chaos`). The `app/` folder should remain intact.

## 4) Configure the Portal
- Copy `app/config.sample.php` to **`app/config.local.php`**.
- Edit `config.local.php` with your database credentials, DreamHost hostname, and desired `app_url` (include the subdirectory if any, such as `https://example.com/creative-chaos`).
- Adjust `admin_emails` to list recipients for registration alerts.
- Optional: update `session_save_path` to a writable directory (e.g., `/home/username/tmp`) if DreamHost requests a custom session location.
- Optional: fill in the SMTP section to relay mail through DreamHost (e.g., `sub5.mail.dreamhost.com`) or another provider. Leaving `enabled => false` uses PHP's built-in `mail()`.

> **Environment variables:** Advanced users can also set `CC_*` variables via DreamHost's panel or `.php` wrappers. Values in `config.local.php` override the defaults bundled with the app.

## 5) Initialize the Database
- Visit `https://yourdomain.com/creative-chaos/init_db.php` once.
- The script creates the tables and prints the generated admin credentials. Save them, then remove or restrict `init_db.php`.

## 6) Sign In to the Admin
- Navigate to `/admin/login.php`, log in with the generated credentials, and immediately change the password under **Admin → Change Password**.

## 7) (Optional) Google Sheets Sync
- Follow `GOOGLE_SHEETS_SETUP.md` to create a service account and share your sheet.
- Upload the JSON key to the server (e.g., `app/service_account.json`).
- In `config.local.php`, set `'google_sheets' => ['enabled' => true, ...]` with your sheet ID and range.

**Registrations Sheet Column Order**
```
ID | Registration Type | Team Name | Student Name | Grade | School | Guardian Email | Category | Writer Count | Extra Writers | Fee | Created At
```

## 8) Security Checklist
- The included `uploads/.htaccess` blocks PHP execution in uploaded content.
- Never commit `config.local.php` or credential files to version control.
- Delete or protect `init_db.php` after provisioning.
- DreamHost's `Logs/` directory already includes daily access/error logs for auditing.

## 9) Maintenance Tips
- Use the Admin dashboard exports for periodic CSV backups.
- You can schedule DreamHost cron jobs to hit `/admin/export.php?download=csv` if automated backups are desired.
- Super admins can disable accounts or reset passwords under **Admin → Users**.
- `/admin/migrate.php` adds missing columns to older databases; `/admin/remake_db.php` resets everything (use cautiously).

## 10) Pricing Rules Recap
- Team: Base $100 covers 7 writers; each additional writer adds $10.
- Open Class: $20 per registrant; multiple entries per household supported in one form.
