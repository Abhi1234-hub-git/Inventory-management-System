<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Database configuration
$host = 'localhost';
$db   = 'NexGen';
$user = 'root';
$pass = 'mypassword';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

// Get action from request
$action = $_GET['action'] ?? '';

// Handle different actions
switch ($action) {
    case 'dashboard':
        getDashboardData($pdo);
        break;
    case 'getBrands':
        getBrands($pdo);
        break;
    case 'getCategories':
        getCategories($pdo);
        break;
    case 'getStores':
        getStores($pdo);
        break;
    case 'getCustomers':
        getCustomers($pdo);
        break;
    case 'getProducts':
        getProducts($pdo);
        break;
    case 'getProduct':
        getProduct($pdo);
        break;
    case 'addProduct':
        addProduct($pdo);
        break;
    case 'updateProduct':
        updateProduct($pdo);
        break;
    case 'deleteProduct':
        deleteProduct($pdo);
        break;
    case 'getCustomer':
        getCustomer($pdo);
        break;
    case 'addCustomer':
        addCustomer($pdo);
        break;
    case 'updateCustomer':
        updateCustomer($pdo);
        break;
    case 'deleteCustomer':
        deleteCustomer($pdo);
        break;
    case 'getTransactions':
        getTransactions($pdo);
        break;
    case 'getTransaction':
        getTransaction($pdo);
        break;
    case 'addTransaction':
        addTransaction($pdo);
        break;
    case 'deleteTransaction':
        deleteTransaction($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// Dashboard data
function getDashboardData($pdo) {
    try {
        // Total products
        $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM product");
        $total_products = $stmt->fetch()['total_products'];
        
        // Total customers
        $stmt = $pdo->query("SELECT COUNT(*) as total_customers FROM customers");
        $total_customers = $stmt->fetch()['total_customers'];
        
        // Total sales
        $stmt = $pdo->query("SELECT SUM(total_amount) as total_sales FROM transaction");
        $total_sales = $stmt->fetch()['total_sales'] ?? 0;
        
        // Total transactions
        $stmt = $pdo->query("SELECT COUNT(*) as total_transactions FROM transaction");
        $total_transactions = $stmt->fetch()['total_transactions'];
        
        // Recent transactions (last 5)
        $stmt = $pdo->query("
            SELECT t.id, t.total_amount, t.due, t.payment_method, 
                   DATE_FORMAT(t.created_at, '%Y-%m-%d') as date,
                   c.name as customer_name
            FROM transaction t
            LEFT JOIN customers c ON t.cart_id = c.customer_id
            ORDER BY t.created_at DESC
            LIMIT 5
        ");
        $recent_transactions = $stmt->fetchAll();
        
        // Top products (by sales)
        $stmt = $pdo->query("
            SELECT p.pname, c.category_name, 
                   SUM(i.quantity * i.net_price) as total_sales
            FROM invoice i
            JOIN product p ON i.product_id = p.pid
            JOIN categories c ON p.cid = c.cid
            GROUP BY p.pid
            ORDER BY total_sales DESC
            LIMIT 5
        ");
        $top_products = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'total_products' => $total_products,
            'total_customers' => $total_customers,
            'total_sales' => $total_sales,
            'total_transactions' => $total_transactions,
            'recent_transactions' => $recent_transactions,
            'top_products' => $top_products
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Get brands
function getBrands($pdo) {
    try {
        $stmt = $pdo->query("SELECT bid, brandname FROM brands");
        $brands = $stmt->fetchAll();
        echo json_encode(['success' => true, 'brands' => $brands]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Get categories
function getCategories($pdo) {
    try {
        $stmt = $pdo->query("SELECT cid, category_name FROM categories");
        $categories = $stmt->fetchAll();
        echo json_encode(['success' => true, 'categories' => $categories]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Get stores
function getStores($pdo) {
    try {
        $stmt = $pdo->query("SELECT sid, sname FROM stores");
        $stores = $stmt->fetchAll();
        echo json_encode(['success' => true, 'stores' => $stores]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Get customers
function getCustomers($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM customers");
        $customers = $stmt->fetchAll();
        echo json_encode(['success' => true, 'customers' => $customers]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Get products
function getProducts($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT p.*, b.brandname, c.category_name 
            FROM product p
            JOIN brands b ON p.bid = b.bid
            JOIN categories c ON p.cid = c.cid
        ");
        $products = $stmt->fetchAll();
        echo json_encode(['success' => true, 'products' => $products]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Get single product
function getProduct($pdo) {
    try {
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("
            SELECT p.*, b.brandname, c.category_name 
            FROM product p
            JOIN brands b ON p.bid = b.bid
            JOIN categories c ON p.cid = c.cid
            WHERE p.pid = ?
        ");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        
        if ($product) {
            echo json_encode(['success' => true, 'product' => $product]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Add product
function addProduct($pdo) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $pdo->prepare("
            INSERT INTO product (pname, cid, bid, sid, p_stock, price, added_date)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['pname'],
            $data['cid'],
            $data['bid'],
            $data['sid'],
            $data['p_stock'],
            $data['price'],
            $data['added_date']
        ]);
        
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Update product
function updateProduct($pdo) {
    try {
        $id = $_GET['id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $pdo->prepare("
            UPDATE product 
            SET pname = ?, cid = ?, bid = ?, sid = ?, p_stock = ?, price = ?, added_date = ?
            WHERE pid = ?
        ");
        
        $stmt->execute([
            $data['pname'],
            $data['cid'],
            $data['bid'],
            $data['sid'],
            $data['p_stock'],
            $data['price'],
            $data['added_date'],
            $id
        ]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Delete product
function deleteProduct($pdo) {
    try {
        $id = $_GET['id'] ?? 0;
        
        $stmt = $pdo->prepare("DELETE FROM product WHERE pid = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Get single customer
function getCustomer($pdo) {
    try {
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch();
        
        if ($customer) {
            echo json_encode(['success' => true, 'customer' => $customer]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Customer not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Add customer
function addCustomer($pdo) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $pdo->prepare("
            INSERT INTO customers (name, email, phone_number, address, registration_date)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone_number'],
            $data['address'],
            $data['registration_date']
        ]);
        
        // Also add to customer_cart table
        $customer_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO customer_cart (cust_id) VALUES (?)");
        $stmt->execute([$customer_id]);
        
        echo json_encode(['success' => true, 'id' => $customer_id]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Update customer
function updateCustomer($pdo) {
    try {
        $id = $_GET['id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $pdo->prepare("
            UPDATE customers 
            SET name = ?, email = ?, phone_number = ?, address = ?, registration_date = ?
            WHERE customer_id = ?
        ");
        
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone_number'],
            $data['address'],
            $data['registration_date'],
            $id
        ]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Delete customer
function deleteCustomer($pdo) {
    try {
        $id = $_GET['id'] ?? 0;
        
        // First delete from customer_cart
        $stmt = $pdo->prepare("DELETE FROM customer_cart WHERE cust_id = ?");
        $stmt->execute([$id]);
        
        // Then delete from customers
        $stmt = $pdo->prepare("DELETE FROM customers WHERE customer_id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Get transactions
function getTransactions($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT t.*, c.name as customer_name, 
                   DATE_FORMAT(t.created_at, '%Y-%m-%d %H:%i') as date
            FROM transaction t
            LEFT JOIN customers c ON t.cart_id = c.customer_id
            ORDER BY t.created_at DESC
        ");
        $transactions = $stmt->fetchAll();
        echo json_encode(['success' => true, 'transactions' => $transactions]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Get single transaction
function getTransaction($pdo) {
    try {
        $id = $_GET['id'] ?? 0;
        
        // Get transaction details
        $stmt = $pdo->prepare("
            SELECT t.*, c.name as customer_name, 
                   DATE_FORMAT(t.created_at, '%Y-%m-%d %H:%i') as date
            FROM transaction t
            LEFT JOIN customers c ON t.cart_id = c.customer_id
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        $transaction = $stmt->fetch();
        
        if (!$transaction) {
            echo json_encode(['success' => false, 'message' => 'Transaction not found']);
            return;
        }
        
        // Get transaction products
        $stmt = $pdo->prepare("
            SELECT i.*, p.pname, p.price
            FROM invoice i
            JOIN product p ON i.product_id = p.pid
            WHERE i.transaction_id = ?
        ");
        $stmt->execute([$id]);
        $products = $stmt->fetchAll();
        
        $transaction['products'] = $products;
        
        echo json_encode(['success' => true, 'transaction' => $transaction]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Add transaction
function addTransaction($pdo) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Start transaction
        $pdo->beginTransaction();
        
        // 1. Create transaction record
        $stmt = $pdo->prepare("
            INSERT INTO transaction (total_amount, paid, due, gst, discount, payment_method, cart_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['total'],
            $data['paid'],
            $data['due'],
            $data['tax'],
            $data['discount'],
            $data['payment_method'],
            $data['customer_id']
        ]);
        
        $transaction_id = $pdo->lastInsertId();
        
        // 2. Create invoice items
        $stmt = $pdo->prepare("
            INSERT INTO invoice (product_id, quantity, net_price, transaction_id)
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($data['products'] as $product) {
            $stmt->execute([
                $product['id'],
                $product['quantity'],
                $product['price'],
                $transaction_id
            ]);
            
            // 3. Update product stock
            $updateStmt = $pdo->prepare("
                UPDATE product 
                SET p_stock = p_stock - ? 
                WHERE pid = ?
            ");
            $updateStmt->execute([$product['quantity'], $product['id']]);
        }
        
        // 4. Create employee transaction record (assign to random employee for demo)
        $stmt = $pdo->query("SELECT employee_id FROM employees ORDER BY RAND() LIMIT 1");
        $employee = $stmt->fetch();
        
        if ($employee) {
            $commission = $data['total'] * 0.05; // 5% commission for demo
            $stmt = $pdo->prepare("
                INSERT INTO employee_transaction (transaction_id, employee_id, commission_percentage, commission_amount)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$transaction_id, $employee['employee_id'], 5, $commission]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode(['success' => true, 'transaction_id' => $transaction_id]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Delete transaction
function deleteTransaction($pdo) {
    try {
        $id = $_GET['id'] ?? 0;
        
        // Start transaction
        $pdo->beginTransaction();
        
        // 1. Get invoice items to restore stock
        $stmt = $pdo->prepare("
            SELECT product_id, quantity 
            FROM invoice 
            WHERE transaction_id = ?
        ");
        $stmt->execute([$id]);
        $items = $stmt->fetchAll();
        
        // 2. Restore product stock
        foreach ($items as $item) {
            $updateStmt = $pdo->prepare("
                UPDATE product 
                SET p_stock = p_stock + ? 
                WHERE pid = ?
            ");
            $updateStmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // 3. Delete invoice items
        $stmt = $pdo->prepare("DELETE FROM invoice WHERE transaction_id = ?");
        $stmt->execute([$id]);
        
        // 4. Delete employee transaction records
        $stmt = $pdo->prepare("DELETE FROM employee_transaction WHERE transaction_id = ?");
        $stmt->execute([$id]);
        
        // 5. Delete transaction
        $stmt = $pdo->prepare("DELETE FROM transaction WHERE id = ?");
        $stmt->execute([$id]);
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}