<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data['name']) && !empty($data['category']) && !empty($data['price']) && !empty($data['status'])) {
    try {
        // Database connection
        $conn = new PDO("mysql:host=localhost;dbname=dukani", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Execute the query
        $stmt = $conn->prepare("INSERT INTO products (name, category, price, description, status) VALUES (:name, :category, :price, :description, :status)");
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->execute();
        echo json_encode(['message' => 'Product added successfully']);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid input']);
}
?>
