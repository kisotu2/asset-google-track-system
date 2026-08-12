<?php
// Copy this file to config.php. Never commit config.php or a Maps API key.
return [
    'db_host' => '127.0.0.1', 'db_name' => 'ira_assets', 'db_user' => 'root', 'db_password' => '',
    // Restrict this key to your HTTPS production origin in Google Cloud Console.
    'google_maps_api_key' => '',
    // Email OTP is only sent when these are configured; development displays no OTP.
    'mail_host' => '', 'mail_username' => '', 'mail_password' => '', 'mail_port' => 587,
    // Receive approved-area alerts. Email is only attempted when mail settings and this are set.
    'admin_alert_email' => '', 'mail_from' => '',
];
