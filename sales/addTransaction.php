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

try {
    // Database connection
    $conn = new PDO("mysql:host=localhost;dbname=dukani", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $conn->beginTransaction();

    // Extract input values
    $total_amount = $data['total_amount'];
    $payment_method_name = $data['payment_method'];
    $payment_status_name = $data['payment_status'];
    $sold_by_username = $data['sold_by'];
    $sales_details = $data['sales_details'];

    // Fetch `payment_method` ID
    $stmt = $conn->prepare("SELECT id FROM payment_method WHERE name = :name");
    $stmt->execute(['name' => $payment_method_name]);
    $payment_method_id = $stmt->fetchColumn();
    
    if (!$payment_method_id) {
        throw new Exception("Invalid payment method: $payment_method_name");
    }

    // Fetch `payment_status` ID
    $stmt = $conn->prepare("SELECT id FROM payment_status WHERE name = :name");
    $stmt->execute(['name' => $payment_status_name]);
    $payment_status_id = $stmt->fetchColumn();
    
    if (!$payment_status_id) {
        throw new Exception("Invalid payment status: $payment_status_name");
    }

    // Fetch `sold_by` ID (from users table)
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute(['username' => $sold_by_username]);
    $sold_by_id = $stmt->fetchColumn();
    
    if (!$sold_by_id) {
        throw new Exception("Invalid user: $sold_by_username");
    }

    // Insert into `sales_transactions`
    $stmt = $conn->prepare("INSERT INTO sales_transactions (total_amount, payment_method, payment_status, sold_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$total_amount, $payment_method_id, $payment_status_id, $sold_by_id]);
    $transaction_id = $conn->lastInsertId();

    // Insert into `sales_details`
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
    echo json_encode(['success' => true, 'message' => 'Transaction created successfully']);
} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
