<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$response = [
    'code' => 200,  // Default code for success
    'message' => 'Request processed successfully',
    'data' => []
];

try {
    // Database connection
    $conn = new PDO("mysql:host=localhost;dbname=dukani", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query to fetch categories
    // Query to fetch categories
    $stmtCategories = $conn->prepare("SELECT id, category FROM categories");
    $stmtCategories->execute();
    $categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);

    // Query to fetch products
    // Query to fetch products
    $stmtProducts = $conn->prepare("SELECT * FROM products");
    $stmtProducts->execute();
    $products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);
    
    // Query to fetch stocks
    // Query to fetch stocks
    $stmtStocks = $conn->prepare("SELECT * FROM stocks");
    $stmtStocks->execute();
    $stocks = $stmtStocks->fetchAll(PDO::FETCH_ASSOC);

    // Query to fetch users
    // Query to fetch users
    $stmtUsers = $conn->prepare("SELECT * FROM users");
    $stmtUsers->execute();
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    // Query to fetch transactions
    // Query to fetch transactions
    $sql = "
        SELECT 
            st.id AS transaction_id, st.total_amount, st.payment_method, 
            st.payment_status, st.transaction_date, st.sold_by, 
            sd.product_id, sd.quantity, sd.unit_price, sd.total_price, 
            p.name AS product_name
        FROM sales_transactions st
        LEFT JOIN sales_details sd ON st.id = sd.transaction_id
        LEFT JOIN products p ON sd.product_id = p.id
    ";
    $result = $conn->query($sql);

    $transactions = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $transaction_id = $row['transaction_id'];
        if (!isset($transactions[$transaction_id])) {
            $transactions[$transaction_id] = [
                'transaction_id' => $row['transaction_id'],
                'total_amount' => $row['total_amount'],
                'payment_method' => $row['payment_method'],
                'payment_status' => $row['payment_status'],
                'transaction_date' => $row['transaction_date'],
                'sold_by' => $row['sold_by'],
                'details' => []
            ];
        }
        $transactions[$transaction_id]['details'][] = [
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'unit_price' => $row['unit_price'],
            'total_price' => $row['total_price']
        ];
    }

    // Populate response data
    $response['data'] = [
        'categories' => $categories,
        'products' => $products,
        'stocks' => $stocks,
        'users' => $users,
        'transactions' => $transactions
    ];
} catch (PDOException $e) {
    // Handle errors
    $response['code'] = 500; // Error code
    $response['message'] = 'An error occurred: ' . $e->getMessage();
    $response['data'] = [];
}

// Output the response as JSON
echo json_encode($response);
