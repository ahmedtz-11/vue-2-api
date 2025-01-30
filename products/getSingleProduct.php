<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

try {
    // Database connection
    $conn = new PDO("mysql:host=localhost;dbname=dukani", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Check if the product ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']); // Ensure the ID is an integer

    try {
        // Execute the SQL query
        $query = "SELECT * FROM products WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Fetch the product data
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            // The product data as JSON
            echo json_encode($product);
        } else {
            //JSON error if the product is not found
            echo json_encode(['error' => 'Product not found']);
        }
    } catch (PDOException $e) {
        // Handle query execution errors
        echo json_encode(['error' => 'Error fetching product: ' . $e->getMessage()]);
    }
} else {
    // JSON error if no valid ID is provided
    echo json_encode(['error' => 'Invalid or missing product ID']);
}
