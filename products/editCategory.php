<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data['id']) && !empty($data['name']) && !empty($data['status'])) {
    try {
        // Database connection
        $conn = new PDO("mysql:host=localhost;dbname=dukani", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get the status ID from category_status table
        $stmt = $conn->prepare("SELECT id FROM category_status WHERE name = :status");
        $stmt->bindParam(':status', $data['status']);
        $stmt->execute();
        $status_id = $stmt->fetchColumn();

        if (!$status_id) {
            echo json_encode(['success' => false, 'error' => 'Invalid status']);
            exit;
        }

        // Execute the query
        $stmt = $conn->prepare("UPDATE categories SET name = :name, status_id = :status_id WHERE id = :id");
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':status_id', $status_id);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Category has been updated!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
}
?>
