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
    c.status_id, 
    cs.name AS status_name 
    FROM categories c 
    LEFT JOIN category_status cs ON c.status_id = cs.id
    ");
    $stmtCategories->execute();
    $categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);


    // Query to fetch products
    // Query to fetch products
    $stmtProducts = $conn->prepare("SELECT 
    p.id, 
    p.name, 
    p.category_id, 
    p.price, 
    p.description,
    p.status_id, 
    ps.name AS product_status, 
    c.name AS category_name 
    FROM products p 
    LEFT JOIN product_status ps ON p.status_id = ps.id 
    LEFT JOIN categories c ON p.category_id = c.id
    ");
    $stmtProducts->execute();
    $products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);
  

    // Fetch only available and low stock products
    $stmt = $conn->prepare("
        SELECT p.id, p.name, p.price, SUM(s.quantity) AS total_stock
        FROM products p
        JOIN stocks s ON p.id = s.product_id
        WHERE s.status_id IN (1, 2) AND s.quantity > 0
        GROUP BY p.id
        HAVING total_stock > 0
        ORDER BY p.name ASC
    ");
    
    $stmt->execute();
    $products_forSell = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // Query to fetch stocks
    // Query to fetch stocks
    $stmtStocks = $conn->prepare("SELECT 
    s.id, 
    s.product_id, 
    s.quantity, 
    s.purchasing_price,
    s.expiry_date,
    s.added_by_id,
    s.added_at,
    s.status_id,
    p.name AS product_name,
    ss.name AS stock_status,
    u.username AS added_by_name
    FROM stocks s 
    LEFT JOIN products p ON s.product_id = p.id
    LEFT JOIN stock_status ss ON s.status_id = ss.id
    LEFT JOIN users u ON s.added_by_id = u.id
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
    u.status_id, 
    us.name AS status_name
    FROM users u
    LEFT JOIN user_status us ON u.status_id = us.id;
    ");
    $stmtUsers->execute();
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);


    // Query to fetch transactions
    // Query to fetch transactions
    $sql = "
        SELECT 
            st.id AS transaction_id, st.total_amount, st.payment_method_id, 
            st.payment_status_id, st.transaction_date, st.sold_by_id, 
            sd.product_id, sd.quantity, sd.unit_price, sd.total_price, 
            p.name AS product_name, u.username AS sold_by_name, ps.name AS payment_status_name, pm.name AS payment_method_name
        FROM sales_transactions st
        LEFT JOIN sales_details sd ON st.id = sd.transaction_id
        LEFT JOIN products p ON sd.product_id = p.id
        LEFT JOIN users u ON st.sold_by_id = u.id
        LEFT JOIN payment_status ps ON st.payment_status_id = ps.id
        LEFT JOIN payment_method pm ON st.payment_method_id = pm.id
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


    $paymentMethodSales = [];
foreach ($transactions as $transaction) {
    $paymentMethod = $transaction['payment_method']; // e.g., Cash, Credit, Online
    $paymentMethodSales[$paymentMethod] = ($paymentMethodSales[$paymentMethod] ?? 0) + $transaction['total_amount'];
}

// Process top-selling products for Bar Chart
$topSellingProducts = [];
foreach ($transactions as $transaction) {
    foreach ($transaction['details'] as $detail) {
        $productName = $detail['product_name'];
        $topSellingProducts[$productName] = ($topSellingProducts[$productName] ?? 0) + $detail['quantity'];
    }
}
arsort($topSellingProducts); // Sort in descending order

// Process sales trends for Line Chart
$salesTrend = [];
foreach ($transactions as $transaction) {
    $date = date('Y-m-d', strtotime($transaction['transaction_date']));
    $salesTrend[$date] = ($salesTrend[$date] ?? 0) + $transaction['total_amount'];
}
ksort($salesTrend); // Sort by date


$stockStatusDistribution = [];
foreach ($stocks as $stock) {
    $status = $stock['stock_status'];  // e.g., Available, Low Stock, Out of Stock
    $stockStatusDistribution[$status] = ($stockStatusDistribution[$status] ?? 0) + $stock['quantity'];
}


$avgTransactionValue = [];
foreach ($transactions as $transaction) {
    $date = date('Y-m-d', strtotime($transaction['transaction_date']));
    $avgTransactionValue[$date] = ($avgTransactionValue[$date] ?? 0) + $transaction['total_amount'];
}


    // Populate response data
    $response['data'] = [
        'categories' => $categories,
        'products' => $products,
        'products_forSell' => $products_forSell,
        'stocks' => $stocks,
        'users' => $users,
        'transactions' => $transactions,


        'paymentMethodSales' => $paymentMethodSales,
        'topSellingProducts' => array_slice($topSellingProducts, 0, 5), // Limit to top 5
        'salesTrend' => $salesTrend,
        'stockStatusDistribution' => $stockStatusDistribution,
        'avgTransactionValue' => $avgTransactionValue
        
    ];
} catch (PDOException $e) {
    // Handle errors
    $response['code'] = 500; // Error code
    $response['message'] = 'An error occurred: ' . $e->getMessage();
    $response['data'] = [];
}

// Output the response as JSON
echo json_encode($response);
