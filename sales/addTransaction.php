<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$total_amount = $data['total_amount'];
$payment_method = $data['payment_method'];
$payment_status = $data['payment_status'];
$sold_by = $data['sold_by'];
$sales_details = $data['sales_details'];

try {
    $conn = new PDO("mysql:host=localhost;dbname=dukani", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $conn->beginTransaction();

    // Insert into sales_transactions
    $stmt = $conn->prepare("INSERT INTO sales_transactions (total_amount, payment_method, payment_status, sold_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$total_amount, $payment_method, $payment_status, $sold_by]);
    $transaction_id = $conn->lastInsertId();

    // Insert into sales_details
    $stmt = $conn->prepare("INSERT INTO sales_details (transaction_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
    foreach ($sales_details as $detail) {
        $stmt->execute([
            $transaction_id,
            $detail['product_id'],
            $detail['quantity'],
            $detail['unit_price'],
            $detail['quantity'] * $detail['unit_price']
        ]);
    }

    $conn->commit();
    echo json_encode(['message' => 'Transaction created successfully']);
} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(['message' => 'Error: ' . $e->getMessage()]);
}
?>
