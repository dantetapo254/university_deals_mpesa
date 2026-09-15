<?php
// Safaricom calls this endpoint after the STK transaction is completed/cancelled.
require __DIR__ . '/db.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

file_put_contents(
    __DIR__ . '/callback.log',
    date('c') . ' ' . $raw . PHP_EOL,
    FILE_APPEND
);

try {
    $callback = $data['Body']['stkCallback'] ?? null;

    if (!$callback) {
        http_response_code(400);
        exit('Invalid callback.');
    }

    $checkoutRequestId = $callback['CheckoutRequestID'] ?? '';
    $merchantRequestId = $callback['MerchantRequestID'] ?? '';
    $resultCode = (int) ($callback['ResultCode'] ?? -1);
    $resultDesc = $callback['ResultDesc'] ?? '';

    if (!$checkoutRequestId) {
        http_response_code(400);
        exit('Missing CheckoutRequestID.');
    }

    if ($resultCode === 0) {
        $items = $callback['CallbackMetadata']['Item'] ?? [];

        $receipt = null;
        $amount = null;
        $phone = null;
        $transactionDate = null;

        foreach ($items as $item) {
            switch ($item['Name'] ?? '') {
                case 'MpesaReceiptNumber':
                    $receipt = $item['Value'] ?? null;
                    break;
                case 'Amount':
                    $amount = $item['Value'] ?? null;
                    break;
                case 'PhoneNumber':
                    $phone = $item['Value'] ?? null;
                    break;
                case 'TransactionDate':
                    $transactionDate = $item['Value'] ?? null;
                    break;
            }
        }

        $stmt = $pdo->prepare(
            'UPDATE mpesa_transactions
             SET status = "PAID",
                 merchant_request_id = ?,
                 result_code = ?,
                 result_description = ?,
                 mpesa_receipt = ?,
                 paid_amount = ?,
                 paid_phone = ?,
                 transaction_date = ?,
                 updated_at = NOW()
             WHERE checkout_request_id = ?'
        );

        $stmt->execute([
            $merchantRequestId,
            $resultCode,
            $resultDesc,
            $receipt,
            $amount,
            $phone,
            $transactionDate,
            $checkoutRequestId
        ]);

        // IMPORTANT:
        // This is where your real system should also mark the associated
        // University Deals Center order as PAID.
        //
        // Example:
        // UPDATE orders SET payment_status='PAID',
        // mpesa_receipt=? WHERE order_id=?;

    } else {
        $stmt = $pdo->prepare(
            'UPDATE mpesa_transactions
             SET status = "FAILED",
                 merchant_request_id = ?,
                 result_code = ?,
                 result_description = ?,
                 updated_at = NOW()
             WHERE checkout_request_id = ?'
        );

        $stmt->execute([
            $merchantRequestId,
            $resultCode,
            $resultDesc,
            $checkoutRequestId
        ]);
    }

    header('Content-Type: application/json');
    echo json_encode([
        'ResultCode' => 0,
        'ResultDesc' => 'Accepted'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Callback processing error'
    ]);
}
