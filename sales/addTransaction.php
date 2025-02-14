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
    $stmt = $conn->prepare("INSERT INTO sales_transactions (total_amount, payment_method_id, payment_status_id, sold_by_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$total_amount, $payment_method_id, $payment_status_id, $sold_by_id]);
    $transaction_id = $conn->lastInsertId();

    // Insert into `sales_details` and deduct stock
    $stmt = $conn->prepare("INSERT INTO sales_details (transaction_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($sales_details as $detail) {
        $product_id = $detail['product_id'];
        $quantity_sold = $detail['quantity'];
        $unit_price = $detail['unit_price'];
        $total_price = $quantity_sold * $unit_price;

        // Insert into sales_details
        $stmt->execute([$transaction_id, $product_id, $quantity_sold, $unit_price, $total_price]);

        // Deduct from stock (earliest expiry first)
        $remaining_quantity = $quantity_sold;

        $stock_stmt = $conn->prepare("SELECT id, quantity FROM stocks WHERE product_id = ? AND quantity > 0 ORDER BY expiry_date ASC");
        $stock_stmt->execute([$product_id]);
        $stocks = $stock_stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($stocks as $stock) {
            if ($remaining_quantity <= 0) {
                break;
            }

            $stock_id = $stock['id'];
            $available_quantity = $stock['quantity'];

            if ($available_quantity >= $remaining_quantity) {
                // Deduct and update stock
                $update_stmt = $conn->prepare("UPDATE stocks SET quantity = quantity - ? WHERE id = ?");
                $update_stmt->execute([$remaining_quantity, $stock_id]);
                $remaining_quantity = 0;
            } else {
                // Deplete this stock entry and move to the next
                $update_stmt = $conn->prepare("UPDATE stocks SET quantity = 0 WHERE id = ?");
                $update_stmt->execute([$stock_id]);
                $remaining_quantity -= $available_quantity;
            }
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Transaction completed, stock updated']);
} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
