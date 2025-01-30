<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['category'])) {
    try {
        $conn = new PDO("mysql:host=localhost;dbname=dukani", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("INSERT INTO categories (category) VALUES (:category)");
        $stmt->bindParam(':category', $data['category']);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'New category added!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to add category']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
}
?>
