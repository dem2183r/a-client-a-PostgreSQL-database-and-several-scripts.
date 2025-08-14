<?php
header('Content-Type: application/json');

$n = isset($_GET['n']) ? (int)$_GET['n'] : 1000;
$n = max(1, min($n, 10000));

$results = ['started' => 0, 'skipped' => 0];

for ($i = 0; $i < $n; $i++) {
    // Ждём, пока alpha.php не освободится
    while (true) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://localhost/order-system/alpha.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);

        if (strpos($response, 'Скрипт уже выполняется') === false) {
            // Успешно запущен
            $results['started']++;
            break;
        } else {
            // Ждём 0.1 сек и пробуем снова
            usleep(100000); // 100 мс
        }
    }
}

echo json_encode($results);
?>
