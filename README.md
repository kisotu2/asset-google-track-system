# Smart Asset Management System

This PHP/MySQL application implements the revised proposal: asset registration and assignment, role-based access, maintenance/lifecycle prioritisation, consent-based browser location check-ins, Google Maps last-known-location visualisation, approved-area alerts, and audit logging.

## Local installation

1. In the XAMPP MySQL service, open `http://localhost/Asset/install.php` and install the schema.
2. Copy `config.example.php` to `config.php` and set your local MySQL credentials. `config.php` is ignored by Git.
3. Open `http://localhost/Asset/create_super_admin.php` once to create the first administrator, then sign in.
4. For the map, create a Google Maps JavaScript API key, restrict it to the deployed HTTPS origin, and set `google_maps_api_key` in `config.php`.

Location check-ins require HTTPS in deployment (or localhost), a user-assigned asset, and the user's explicit browser permission. The system deliberately stores a last-known check-in; it does not track devices covertly or remotely activate a device location service.

## Predictive maintenance and alerts

For an existing database, import `migrations/20260812_add_predictive_maintenance.sql` through phpMyAdmin. The Maintenance page then accepts daily active hours, crash counts, and battery health, and uses a local logistic-regression model to estimate maintenance failure risk from repair history, asset age, warranty remaining, and usage. It begins with an explainable baseline because a new database has no history. After collecting at least 30 labelled historical records (with `failed_within_90_days` set to `0` or `1`), run `python3 ml/train_maintenance_model.py your_training_data.csv` to create a trained local model.

When a consented location check-in falls outside every active approved area, the application creates an in-app alert. To additionally email it, set `mail_host`, `mail_username`, `mail_password`, `mail_port`, `mail_from`, and `admin_alert_email` in `config.php`.

## Security notes

- No database, SMTP, or Maps secrets are stored in source code.
- State-changing forms have CSRF protection; data access uses prepared statements.
- Location check-ins and administrator location-dashboard views are audit logged.
- Restrict access to `install.php` after local setup and use TLS in production.
