<?php
function mpesaBaseUrl(array $config): string
{
    return $config['mpesa']['environment'] === 'production'
        ? 'https://api.safaricom.co.ke'
        : 'https://sandbox.safaricom.co.ke';
}

function getMpesaAccessToken(array $config): string
{
    $key = $config['mpesa']['consumer_key'];
    $secret = $config['mpesa']['consumer_secret'];

    $url = mpesaBaseUrl($config) . '/oauth/v1/generate?grant_type=client_credentials';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . base64_encode($key . ':' . $secret),
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $error) {
        throw new Exception('M-PESA token request failed: ' . $error);
    }

    $data = json_decode($response, true);

    if ($status < 200 || $status >= 300 || empty($data['access_token'])) {
        throw new Exception('M-PESA token request was rejected.');
    }

    return $data['access_token'];
}

function normalizePhone(string $phone): string
{
    $phone = preg_replace('/\D+/', '', $phone);

    if (preg_match('/^07\d{8}$/', $phone)) {
        return '254' . substr($phone, 1);
    }

    if (preg_match('/^01\d{8}$/', $phone)) {
        return '254' . substr($phone, 1);
    }

    if (preg_match('/^254\d{9}$/', $phone)) {
        return $phone;
    }

    if (preg_match('/^\+254\d{9}$/', $phone)) {
        return substr($phone, 1);
    }

    throw new InvalidArgumentException('Enter a valid Kenyan M-PESA number.');
}

function initiateStkPush(
    array $config,
    string $phone,
    float $amount,
    string $accountReference,
    string $description
): array {
    $shortcode = $config['mpesa']['shortcode'];
    $passkey = $config['mpesa']['passkey'];
    $timestamp = date('YmdHis');

    $password = base64_encode($shortcode . $passkey . $timestamp);

    $payload = [
        'BusinessShortCode' => $shortcode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => (int) ceil($amount),
        'PartyA' => $phone,
        'PartyB' => $shortcode,
        'PhoneNumber' => $phone,
        'CallBackURL' => $config['mpesa']['callback_url'],
        'AccountReference' => $accountReference,
        'TransactionDesc' => $description,
    ];

    $token = getMpesaAccessToken($config);
    $url = mpesaBaseUrl($config) . '/mpesa/stkpush/v1/processrequest';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $error) {
        throw new Exception('STK Push request failed: ' . $error);
    }

    $data = json_decode($response, true);

    if ($status < 200 || $status >= 300 || empty($data['ResponseCode']) || $data['ResponseCode'] !== '0') {
        $message = $data['errorMessage'] ?? $data['ResponseDescription'] ?? 'M-PESA rejected the request.';
        throw new Exception($message);
    }

    return $data;
}
