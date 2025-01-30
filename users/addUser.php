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
    //Execute SQL query
    $query = "INSERT INTO users (username, password, role, status) VALUES (:username, :password, :role, :status)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':username', $data['username']);
    $stmt->bindParam(':password', password_hash($data['password'], PASSWORD_BCRYPT));
    $stmt->bindParam(':role', $data['role']);
    $stmt->bindParam(':status', $data['status']);
    
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
