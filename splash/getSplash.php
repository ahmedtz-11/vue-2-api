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
    $stmtCategories = $conn->prepare("SELECT 
    c.id, 
    c.name, 
    c.status, 
    cs.name AS status_name 
    FROM categories c 
    LEFT JOIN category_status cs ON c.status = cs.id
    ");
    $stmtCategories->execute();
    $categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);


    // Query to fetch products
    // Query to fetch products
    $stmtProducts = $conn->prepare("SELECT 
    p.id, 
    p.name, 
    p.category, 
    p.price, 
    p.description,
    p.status, 
    ps.name AS product_status, 
    c.name AS category_name 
    FROM products p 
    LEFT JOIN product_status ps ON p.status = ps.id 
    LEFT JOIN categories c ON p.category = c.id
    ");
    $stmtProducts->execute();
    $products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);
  
    
    // Query to fetch stocks
    // Query to fetch stocks
    $stmtStocks = $conn->prepare("SELECT 
    s.id, 
    s.product_name, 
    s.quantity, 
    s.purchasing_price,
    s.expiry_date,
    s.added_by,
    s.added_at,
    s.status,
    p.name AS product_name,
    ss.name AS stock_status,
    u.username AS added_by_name
    FROM stocks s 
    LEFT JOIN products p ON s.product_name = p.id
    LEFT JOIN stock_status ss ON s.status = ss.id
    LEFT JOIN users u ON s.added_by = u.id
    ");
    $stmtStocks->execute();
    $stocks = $stmtStocks->fetchAll(PDO::FETCH_ASSOC);


    // Query to fetch users
    // Query to fetch users
    $stmtUsers = $conn->prepare("SELECT 
    u.id, 
    u.username, 
    u.password, 
    u.role, 
    u.createdAt, 
    u.status, 
    us.name AS status_name
    FROM users u
    LEFT JOIN user_status us ON u.status = us.id;
    ");
    $stmtUsers->execute();
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);


    // Query to fetch transactions
    // Query to fetch transactions
    $sql = "
        SELECT 
            st.id AS transaction_id, st.total_amount, st.payment_method, 
            st.payment_status, st.transaction_date, st.sold_by, 
            sd.product_id, sd.quantity, sd.unit_price, sd.total_price, 
            p.name AS product_name, u.username AS sold_by_name, ps.name AS payment_status_name, pm.name AS payment_method_name
        FROM sales_transactions st
        LEFT JOIN sales_details sd ON st.id = sd.transaction_id
        LEFT JOIN products p ON sd.product_id = p.id
        LEFT JOIN users u ON st.sold_by = u.id
        LEFT JOIN payment_status ps ON st.payment_status = ps.id
        LEFT JOIN payment_method pm ON st.payment_method = pm.id
    ";
    $result = $conn->query($sql);

    $transactions = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $transaction_id = $row['transaction_id'];
        if (!isset($transactions[$transaction_id])) {
            $transactions[$transaction_id] = [
                'transaction_id' => $row['transaction_id'],
                'total_amount' => $row['total_amount'],
                'payment_method' => $row['payment_method_name'],
                'payment_status' => $row['payment_status_name'],
                'transaction_date' => $row['transaction_date'],
                'sold_by' => $row['sold_by_name'],
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
