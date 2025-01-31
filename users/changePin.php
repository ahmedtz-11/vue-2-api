<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

try {
    // Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=dukani", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $_GET['id'] ?? null;  // User ID from query string

if (isset($data['oldPin'], $data['newPin'], $id)) {
    // Query to check old PIN
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($data['oldPin'], $user['password'])) {
        // Update the PIN if old PIN is correct
        $newPassword = password_hash($data['newPin'], PASSWORD_BCRYPT);
        $updateStmt = $pdo->prepare("UPDATE users SET password = :newPassword WHERE id = :id");
        $updateStmt->bindParam(':newPassword', $newPassword);
        $updateStmt->bindParam(':id', $id);

        if ($updateStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'PIN changed successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update PIN']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Old PIN is incorrect']);
    }
} else {
    echo json_encode(['error' => 'Invalid input']);
}
?>
