<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['product_name']) && !empty($data['quantity']) && !empty($data['purchasing_price']) && !empty($data['expiry_date']) && !empty($data['added_by'])) {
    try {
        // Database connection
        $conn = new PDO("mysql:host=localhost;dbname=dukani", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Determine status based on conditions
        if ($data['quantity'] == 0) {
            $status_name = "Out of Stock";
        } elseif (strtotime($data['expiry_date']) < time()) {
            $status_name = "Expired";
        } elseif ($data['quantity'] < 5) {
            $status_name = "Low Stock";
        } else {
            $status_name = "Available";
        }

        // Get the status ID from stock_status table
        $sql = "SELECT id FROM stock_status WHERE name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$status_name]);
        $status_id = $stmt->fetchColumn();

        // Get product ID from products table
        $stmt = $conn->prepare("SELECT id FROM products WHERE name = :name");
        $stmt->execute(['name' => $data['product_name']]);
        $product_id = $stmt->fetchColumn();
        if (!$product_id) {
            throw new Exception("Product: $product_id not found");
        }

        // Fetch `added_by` ID (from users table)
        $added_by_username = $data['added_by'];
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute(['username' => $added_by_username]);
        $sold_by_id = $stmt->fetchColumn();
        
        if (!$sold_by_id) {
            throw new Exception("Invalid user: $sold_by_username");
        }

        // Execute the update query
        $stmt = $conn->prepare("INSERT INTO stocks (product_name, quantity, purchasing_price, expiry_date, added_by, status) VALUES (:product_name, :quantity, :purchasing_price, :expiry_date, :added_by, :status)");
        $stmt->bindParam(':product_name', $product_id);
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':purchasing_price', $data['purchasing_price']);
        $stmt->bindParam(':expiry_date', $data['expiry_date']);
        $stmt->bindParam(':added_by',  $sold_by_id);
        $stmt->bindParam(':status', $status_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Stock added successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to add stock']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
}
?>
