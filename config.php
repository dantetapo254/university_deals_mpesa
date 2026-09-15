<?php
// M-PESA / Database configuration.
// NEVER commit real Consumer Secret or Passkey to GitHub.

return [
    'mpesa' => [
        // Use 'sandbox' while testing, then change to 'production'.
        'environment' => 'sandbox',

        'consumer_key' => 'xpEA6biO5A3x8dier4qUDn1LXQOgTiz4rDUN9dMZ7PFVLSll',
        'consumer_secret' => 'DuZrbyTKgb273YzSDBBggINjbNixg5jna0mmVMSkeqBEqx7QmlJtU3qXDdVCzMmF',

        // Your M-PESA PayBill/Till shortcode supplied by Safaricom.
        'shortcode' => '123456',
        'passkey' => 'your_passkey_here',

        // MUST be publicly reachable by Safaricom in production.
        // Example: https://yourdomain.com/mpesa/callback.php
        'callback_url' => 'university-deals-center.free.nf/mpesa/callback.php',

        // Name shown in the checkout description.
        'transaction_desc' => 'Dante Project Payment',
    ],

    'database' => [
        'host' => 'localhost',
        'name' => 'university_deals',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
];
