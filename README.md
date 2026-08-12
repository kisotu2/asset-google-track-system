# Smart Asset Management System

This PHP/MySQL application implements the revised proposal: asset registration and assignment, role-based access, maintenance/lifecycle prioritisation, consent-based browser location check-ins, Google Maps last-known-location visualisation, approved-area alerts, and audit logging.

## Local installation

1. In the XAMPP MySQL service, open `http://localhost/Asset/install.php` and install the schema.
2. Copy `config.example.php` to `config.php` and set your local MySQL credentials. `config.php` is ignored by Git.
3. Open `http://localhost/Asset/create_super_admin.php` once to create the first administrator, then sign in.
4. For the map, create a Google Maps JavaScript API key, restrict it to the deployed HTTPS origin, and set `google_maps_api_key` in `config.php`.

Location check-ins require HTTPS in deployment (or localhost), a user-assigned asset, and the user's explicit browser permission. The system deliberately stores a last-known check-in; it does not track devices covertly or remotely activate a device location service.

## Security notes

- No database, SMTP, or Maps secrets are stored in source code.
- State-changing forms have CSRF protection; data access uses prepared statements.
- Location check-ins and administrator location-dashboard views are audit logged.
- Restrict access to `install.php` after local setup and use TLS in production.
