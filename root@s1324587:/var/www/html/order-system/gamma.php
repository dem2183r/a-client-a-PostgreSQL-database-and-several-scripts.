<?php
header("Content-Type: text/html; charset=UTF-8");
header('Content-Type: application/json');


try {
    $pdo = new PDO("pgsql:host=localhost;dbname=orderdb", "orderuser", "orderpass123");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo json_encode(['error' => 'Ошибка подключения к БД: ' . $e->getMessage()]);
    exit;
}

try {
    // Получение последних 100 заказов
    $stmt = $pdo->prepare("
        SELECT o.*, p.name as product_name, p.category_id, c.name as category_name
        FROM orders o
        JOIN products p ON o.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        ORDER BY o.purchase_time DESC
        LIMIT 100
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($orders)) {
        echo json_encode(['error' => 'Нет заказов']);
        exit;
    }


    $categoryStats = [];
    $firstTime = new DateTime($orders[count($orders)-1]['purchase_time']);
    $lastTime = new DateTime($orders[0]['purchase_time']);
    $timeDiff = $firstTime->diff($lastTime);

    foreach ($orders as $order) {
        $categoryId = $order['category_id'];
        $categoryName = $order['category_name'];
        $quantity = $order['quantity'];

        // Статистика по категориям
        if (!isset($categoryStats[$categoryId])) {
            $categoryStats[$categoryId] = [
                'name' => $categoryName,
                'count' => 0
            ];
        }
        $categoryStats[$categoryId]['count'] += $quantity;
    }


    $response = [
        'total_orders' => count($orders),
        'time_period' => [
            'first_order' => $orders[count($orders)-1]['purchase_time'],
            'last_order' => $orders[0]['purchase_time'],
            'duration' => $timeDiff->format('%h ч %i мин %s сек')
        ],
        'category_statistics' => array_values($categoryStats)
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
