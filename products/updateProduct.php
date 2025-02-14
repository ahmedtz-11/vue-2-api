<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data['id']) && !empty($data['name']) && !empty($data['category']) && !empty($data['price']) && !empty($data['status'])) {
    try {
        // Database connection
        $conn = new PDO("mysql:host=localhost;dbname=dukani", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get the category ID from categories table
        $stmt = $conn->prepare("SELECT id FROM categories WHERE name = :category");
        $stmt->bindParam(':category', $data['category']);
        $stmt->execute();
        $category_id = $stmt->fetchColumn();

        if (!$category_id) {
            echo json_encode(['success' => false, 'error' => 'Invalid category']);
            exit;
        }

        // Get the status ID from product_status table
        $stmt = $conn->prepare("SELECT id FROM product_status WHERE name = :status");
        $stmt->bindParam(':status', $data['status']);
        $stmt->execute();
        $status_id = $stmt->fetchColumn();

        if (!$status_id) {
            echo json_encode(['success' => false, 'error' => 'Invalid status']);
            exit;
        }

        // Execute the update query
        $stmt = $conn->prepare("UPDATE products SET name = :name, category_id = :category_id, price = :price, description = :description, status_id = :status_id WHERE id = :id");
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':status_id', $status_id);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
}
?>
