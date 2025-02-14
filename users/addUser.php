<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

try {
    //Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=dukani", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // JSON error when database connection failed
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['username'], $data['password'], $data['role'], $data['status'])) {

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
    $query = "INSERT INTO users (username, password, role, status_id) VALUES (:username, :password, :role, :status_id)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':username', $data['username']);
    $stmt->bindParam(':password', password_hash($data['password'], PASSWORD_BCRYPT));
    $stmt->bindParam(':role', $data['role']);
    $stmt->bindParam(':status_id', $status_id);
    
    if ($stmt->execute()) {
        // JSON success when user created
        echo json_encode(['success' => true, 'message' => 'New user added']);
    } else {
        // JSON error when user creation failed
        echo json_encode(['success' => false, 'error' => 'Failed to add user']);
    }
} else {
    // JSON error when required fields are missing
    echo json_encode(['error' => 'Invalid input']);
}
?>
