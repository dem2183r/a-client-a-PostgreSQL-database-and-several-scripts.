<?php
error_log("=== Запуск alpha.php в " . date('Y-m-d H:i:s') . " ===\n", 3, '/var/www/html/order-system/logs/alpha.log');
$redis = new Redis();
try {
    $redis->connect('127.0.0.1', 6379);
    error_log(" Redis: подключено\n", 3, '/var/www/html/order-system/logs/alpha.log');
} catch (Exception $e) {
    error_log(" Redis: ошибка подключения - " . $e->getMessage() . "\n", 3, '/var/www/html/order-system/logs/alpha.log');
    die("Ошибка Redis: " . $e->getMessage());
}

$lockKey = 'alpha_script_lock';

if ($redis->exists($lockKey)) {
    error_log(" Блокировка активна: $lockKey\n", 3, '/var/www/html/order-system/logs/alpha.log');
    echo "Скрипт уже выполняется";
    exit;
} else {
    error_log(" Нет блокировки, устанавливаем...\n", 3, '/var/www/html/order-system/logs/alpha.log');
}

$redis->setex($lockKey, 30, 'running');
error_log(" Блокировка установлена: $lockKey (30 сек)\n", 3, '/var/www/html/order-system/logs/alpha.log');

try {
    $pdo = new PDO("pgsql:host=localhost;dbname=orderdb", "orderuser", "orderpass123");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    error_log(" БД: ошибка подключения - " . $e->getMessage() . "\n", 3, '/var/www/html/order-system/logs/alpha.log');
    echo "Ошибка БД: " . $e->getMessage();
    exit;
}

try {
    sleep(1);

    $stmt = $pdo->prepare("SELECT id FROM products ORDER BY RANDOM() LIMIT 1");
    $stmt->execute();
    $product = $stmt->fetch();

    if ($product) {
        $quantity = rand(1, 10);
        $insertStmt = $pdo->prepare("INSERT INTO orders (product_id, quantity, purchase_time) VALUES (?, ?, NOW())");
        $insertStmt->execute([$product['id'], $quantity]);

        echo "Заказ создан: продукт {$product['id']}, количество {$quantity}";
        error_log(" Заказ создан: продукт {$product['id']}, кол-во {$quantity}\n", 3, '/var/www/html/order-system/logs/alpha.log');
    } else {
        echo "Нет продуктов в базе данных";
        error_log(" Нет продуктов в БД\n", 3, '/var/www/html/order-system/logs/alpha.log');
    }
} catch (Exception $e) {
    error_log(" Ошибка выполнения: " . $e->getMessage() . "\n", 3, '/var/www/html/order-system/logs/alpha.log');
    echo "Ошибка: " . $e->getMessage();
} finally {
    // Снятие блокировки
    $redis->del($lockKey);
    error_log(" Блокировка снята: $lockKey\n", 3, '/var/www/html/order-system/logs/alpha.log');
}
?>
