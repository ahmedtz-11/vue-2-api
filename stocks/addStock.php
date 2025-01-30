<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['product_name']) && !empty($data['quantity']) && !empty($data['purchasing_price']) && !empty($data['expiry_date']) && !empty($data['added_by'])) {
    try {
        $conn = new PDO("mysql:host=localhost;dbname=dukani", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("INSERT INTO stocks (product_name, quantity, purchasing_price, expiry_date, added_by) VALUES (:product_name, :quantity, :purchasing_price, :expiry_date, :added_by)");
        $stmt->bindParam(':product_name', $data['product_name']);
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':purchasing_price', $data['purchasing_price']);
        $stmt->bindParam(':expiry_date', $data['expiry_date']);
        $stmt->bindParam(':added_by', $data['added_by']);

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
