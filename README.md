# Smart Asset Management System

A PHP and MySQL application for managing organisational assets throughout their lifecycle. It combines asset registration, assignment, maintenance planning, consent-based location check-ins, approved-area alerts, and auditable administration in one place.

> Location is collected only when an assigned user explicitly chooses to check in and grants browser permission. The application stores the latest reported location; it does not covertly track devices or remotely activate location services.

## Features

- **Asset lifecycle management** — register, assign, return, update, and review assets.
- **Role-based access** — separate administrator and user experiences with controlled actions.
- **Maintenance prioritisation** — record usage and health signals, then prioritise assets by predicted failure risk.
- **Predictive maintenance** — use an explainable baseline immediately, or train a local logistic-regression model from historical outcomes.
- **Consent-based location check-ins** — capture an assigned asset's last known location only with user consent.
- **Approved-area monitoring** — create an in-app alert when a consented check-in is outside active approved areas; optionally send an email alert.
- **Location dashboard** — visualise the most recent check-ins and approved areas with Google Maps.
- **Audit trail** — log location check-ins and administrator views of the location dashboard.
- **Security controls** — prepared database statements and CSRF protection for state-changing forms.

## Built with

- PHP
- MySQL / MariaDB
- XAMPP for local development
- Google Maps JavaScript API (optional map visualisation)
- Python 3 (optional maintenance-model training)

## Getting started

### Prerequisites

- XAMPP with Apache and MySQL running
- PHP with the MySQLi extension enabled
- A browser pointed at `http://localhost/Asset/`

### Installation

1. Place the project in XAMPP's `htdocs` directory as `Asset`.
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. Visit [http://localhost/Asset/install.php](http://localhost/Asset/install.php), enter your local MySQL credentials, and install the schema.
4. Create a local `config.php` file in the project root. It is intentionally ignored by Git. Database credentials may alternatively be supplied through the `ASSET_DB_HOST`, `ASSET_DB_USER`, `ASSET_DB_PASSWORD`, and `ASSET_DB_NAME` environment variables.
5. Open [http://localhost/Asset/create_super_admin.php](http://localhost/Asset/create_super_admin.php) once to create the first administrator, then sign in.

Use this minimal `config.php` when configuration values are needed locally:

```php
<?php

return [
    'db_host' => '127.0.0.1',
    'db_user' => 'root',
    'db_password' => '',
    'db_name' => 'ira_assets',

    // Optional integrations
    'google_maps_api_key' => '',
    'mail_host' => '',
    'mail_username' => '',
    'mail_password' => '',
    'mail_port' => 587,
    'mail_from' => '',
    'admin_alert_email' => '',
];
```

## Location check-ins and map

Location check-ins require all of the following:

1. The signed-in user has an assigned asset.
2. The user explicitly permits location access in their browser.
3. The site runs on HTTPS in deployment (browsers also permit geolocation on `localhost`).

To enable the map, create a Google Maps JavaScript API key, restrict it to your deployed HTTPS origin, and set `google_maps_api_key` in `config.php`. Without a key, the location table remains available.

When a consented check-in falls outside every active approved area, the system creates an in-app alert. To also deliver that alert by email, configure `mail_host`, `mail_username`, `mail_password`, `mail_port`, `mail_from`, and `admin_alert_email` in `config.php`.

## Predictive maintenance

For an existing database, import [`migrations/20260812_add_predictive_maintenance.sql`](migrations/20260812_add_predictive_maintenance.sql) with phpMyAdmin.

The Maintenance page accepts daily active hours, crash counts, and battery health. It estimates maintenance failure risk using repair history, asset age, warranty remaining, and usage data. New installations begin with an explainable baseline, so the feature is useful before historical data is available.

After collecting at least 30 labelled historical records, train a local model with a CSV containing a `failed_within_90_days` column set to `0` or `1`:

```bash
python3 ml/train_maintenance_model.py your_training_data.csv
```

## Project structure

```text
Asset/
├── assets/                         # Stylesheets and browser JavaScript
├── migrations/                     # Database upgrades
├── ml/                             # Local maintenance-model training script
├── database.sql                    # Base MySQL schema
├── install.php                     # Local schema installer
├── maintenance.php                 # Maintenance management and risk workflow
├── locations.php                   # Location dashboard and approved areas
├── location_checkin_api.php        # Consent-based check-in endpoint
└── config.php                      # Local secrets and integrations (not committed)
```

## Security and deployment notes

- Keep database, SMTP, and Maps credentials out of source control; use `config.php` or environment variables.
- Use TLS/HTTPS in production, particularly for browser location access.
- Restrict or remove access to `install.php` and `create_super_admin.php` once setup is complete.
- Use a restricted Google Maps API key rather than an unrestricted browser key.
- Review the audit log regularly, especially for location-related activity.

## Privacy

The application is designed around explicit consent and last-known-location records. It is not a background tracking system. Before deployment, make sure your organisation's user notice, data-retention policy, and access controls align with the jurisdictions and policies that apply to you.
