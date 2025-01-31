<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dukani";

// Database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['username'], $data['password'])) {
    $username = $conn->real_escape_string($data['username']);
    $password = $conn->real_escape_string($data['password']);

    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Return success along with user ID and username
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'id' => $user['id'],  // Add user ID to the response
                'username' => $user['username']  // Include the username as well
            ]);
        } else {
            echo json_encode(['error' => 'Invalid username or password']);
        }
    } else {
        echo json_encode(['error' => 'Invalid username or password']);
    }
} else {
    echo json_encode(['error' => 'Username and password are required']);
}

$conn->close();
?>
