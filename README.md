# Creative Chaos Portal

This repository contains the Creative Chaos registration and administration portal tuned for DreamHost shared hosting.

- Public registration form supports team and open-class entries with automatic fee calculations.
- Admin dashboard provides submission review, CSV exports, password management, and database maintenance tools.
- Optional Google Sheets synchronization and email notifications via PHP `mail()` or SMTP.

## Getting Started on DreamHost

1. Set your site to PHP 8.0 or newer in the DreamHost panel.
2. Upload the repository contents to your domain (the `app/` directory stays intact).
3. Copy `app/config.sample.php` to `app/config.local.php` and fill in your database credentials and portal URL.
4. Visit `/init_db.php` once to create tables and a super-admin account, then remove or protect that script.
5. Sign in at `/admin/login.php`, change the generated password, and begin managing registrations.

See [`app/INSTALL.md`](app/INSTALL.md) for detailed DreamHost-specific setup guidance and [`app/GOOGLE_SHEETS_SETUP.md`](app/GOOGLE_SHEETS_SETUP.md) for Sheets integration.
