<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

try {
    //Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=dukani", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id'], $data['username'], $data['role'], $data['status'])) {

    // Get the status ID from user_status table
    $stmt = $pdo->prepare("SELECT id FROM user_status WHERE name = :status");
    $stmt->bindParam(':status', $data['status']);
    $stmt->execute();
    $status_id = $stmt->fetchColumn();

    if (!$status_id) {
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
        exit;
    }

    //Execute SQL query
    $query = "UPDATE users SET username = :username, role = :role, status_id = :status_id WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':username', $data['username']);
    $stmt->bindParam(':role', $data['role']);
    $stmt->bindParam(':status_id', $status_id);
    $stmt->bindParam(':id', $data['id']);
    
    if ($stmt->execute()) {
        //JSON success response
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        //JSON error response
        echo json_encode(['success' => false, 'error' => 'Failed to add user']);
    }
} else {
    //JSON error response when input are not valid/missing
    echo json_encode(['error' => 'Invalid input']);
}
?>
