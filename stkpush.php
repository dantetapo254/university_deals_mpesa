<?php
header('Content-Type: application/json');

require __DIR__ . '/db.php';
require __DIR__ . '/mpesa.php';

$config = require __DIR__ . '/config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $orderId = trim($_POST['order_id'] ?? '');
    $phoneInput = trim($_POST['phone'] ?? '');
    $amount = (float) ($_POST['amount'] ?? 0);

    if (!preg_match('/^UDC[A-Za-z0-9]+$/', $orderId)) {
        throw new Exception('Invalid order.');
    }

    if ($amount <= 0) {
        throw new Exception('Invalid payment amount.');
    }

    // IMPORTANT:
    // In your real marketplace, DO NOT trust the amount sent by the browser.
    // Load the order/cart total from MySQL here and calculate it on the server.
    $phone = normalizePhone($phoneInput);

    $stmt = $pdo->prepare(
        'INSERT INTO mpesa_transactions
        (order_id, phone, amount, status)
        VALUES (?, ?, ?, "PENDING")'
    );
    $stmt->execute([$orderId, $phone, $amount]);

    $transactionId = $pdo->lastInsertId();

    $response = initiateStkPush(
        $config,
        $phone,
        $amount,
        $orderId,
        $config['mpesa']['transaction_desc']
    );

    $update = $pdo->prepare(
        'UPDATE mpesa_transactions
         SET merchant_request_id = ?, checkout_request_id = ?, response_code = ?,
             response_description = ?, updated_at = NOW()
         WHERE id = ?'
    );

    $update->execute([
        $response['MerchantRequestID'] ?? null,
        $response['CheckoutRequestID'] ?? null,
        $response['ResponseCode'] ?? null,
        $response['ResponseDescription'] ?? null,
        $transactionId
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'M-PESA prompt sent. Check your phone and enter your M-PESA PIN.',
        'transaction_id' => $transactionId,
        'checkout_request_id' => $response['CheckoutRequestID'] ?? null
    ]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
