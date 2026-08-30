<?php

declare(strict_types=1);

/**
 * Escenario de pruebas de carga concurrente para verificar el SRS:
 * - 110 usuarios simultáneos.
 * - 4 flujos principales: Home, Listado de noticias, Búsqueda de noticias, Login.
 * - Criterio de éxito: 95% de las peticiones responden en menos de 2 segundos (P95 < 2.0s).
 *
 * Uso:
 *   php tests/load/load_test_runner.php [baseUrl] [concurrency] [requestsPerUser]
 * Ejemplo:
 *   php tests/load/load_test_runner.php http://127.0.0.1:8080 110 2
 */

$baseUrl = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$concurrency = (int) ($argv[2] ?? 110);
$requestsPerUser = (int) ($argv[3] ?? 2);

echo "===============================================================\n";
echo " SFL ULS Lab - Prueba de Carga Concurrente (SRS Requirement)\n";
echo "===============================================================\n";
echo " Base URL:            {$baseUrl}\n";
echo " Usuarios simultáneos: {$concurrency}\n";
echo " Peticiones por user:  {$requestsPerUser}\n";
echo " Objetivo SRS:        P95 < 2000 ms (< 2.0s)\n";
echo "===============================================================\n\n";

$endpoints = [
    [
        'name'   => '1. Home (/)',
        'method' => 'GET',
        'url'    => $baseUrl . '/',
        'body'   => null,
    ],
    [
        'name'   => '2. Listado de noticias (/noticias)',
        'method' => 'GET',
        'url'    => $baseUrl . '/noticias',
        'body'   => null,
    ],
    [
        'name'   => '3. Búsqueda de noticias (/noticias?q=investigacion)',
        'method' => 'GET',
        'url'    => $baseUrl . '/noticias?q=investigacion',
        'body'   => null,
    ],
    [
        'name'   => '4. Login (/api/auth/login)',
        'method' => 'POST',
        'url'    => $baseUrl . '/api/auth/login',
        'body'   => json_encode(['email' => 'admin@techhub.cl', 'password' => 'Admin123456!']),
        'headers'=> ['Content-Type: application/json'],
    ],
];

function runConcurrentBatch(array $endpoints, int $concurrency): array
{
    $chunkSize = 15; // Tamaño de bloque para evitar saturación de sockets en servidores de desarrollo
    $results = [];
    $totalRemaining = $concurrency;
    $currentIndex = 0;

    while ($totalRemaining > 0) {
        $currentBatchSize = min($chunkSize, $totalRemaining);
        $mh = curl_multi_init();
        $handles = [];

        for ($i = 0; $i < $currentBatchSize; $i++) {
            $ep = $endpoints[($currentIndex + $i) % count($endpoints)];
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $ep['url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            if ($ep['method'] === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if (!empty($ep['body'])) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $ep['body']);
                }
            }

            if (!empty($ep['headers'])) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $ep['headers']);
            }

            curl_multi_add_handle($mh, $ch);
            $handles[] = ['handle' => $ch, 'endpoint' => $ep['name'], 'start' => microtime(true)];
        }

        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc === CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc === CURLM_OK) {
            if (curl_multi_select($mh, 0.1) === -1) {
                usleep(5000);
            }
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc === CURLM_CALL_MULTI_PERFORM);
        }

        foreach ($handles as $item) {
            $ch = $item['handle'];
            $info = curl_getinfo($ch);
            $totalTime = (microtime(true) - $item['start']) * 1000; // en ms
            $httpCode = $info['http_code'];
            $error = curl_error($ch);

            $results[] = [
                'endpoint'  => $item['endpoint'],
                'http_code' => $httpCode,
                'time_ms'   => $totalTime,
                'success'   => ($httpCode >= 200 && $httpCode < 500 && empty($error)),
                'error'     => $error,
            ];

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);
        $totalRemaining -= $currentBatchSize;
        $currentIndex += $currentBatchSize;
    }

    return $results;
}

function calculatePercentile(array $sortedValues, float $percentile): float
{
    $count = count($sortedValues);
    if ($count === 0) return 0.0;
    $index = ($percentile / 100) * ($count - 1);
    $lower = (int) floor($index);
    $upper = (int) ceil($index);
    if ($lower === $upper) {
        return (float) $sortedValues[$lower];
    }
    return $sortedValues[$lower] + ($index - $lower) * ($sortedValues[$upper] - $sortedValues[$lower]);
}

$allResults = [];
$totalBatches = $requestsPerUser;

for ($batch = 1; $batch <= $totalBatches; $batch++) {
    echo "Ejecutando lote {$batch}/{$totalBatches} ({$concurrency} peticiones concurrentes)... ";
    $batchResults = runConcurrentBatch($endpoints, $concurrency);
    echo "Listo.\n";
    $allResults = array_merge($allResults, $batchResults);
}

// Analizar resultados
$totalRequests = count($allResults);
$successfulRequests = 0;
$times = [];

$byEndpoint = [];
foreach ($allResults as $res) {
    if ($res['success']) {
        $successfulRequests++;
    }
    $times[] = $res['time_ms'];
    $ep = $res['endpoint'];
    if (!isset($byEndpoint[$ep])) {
        $byEndpoint[$ep] = ['times' => [], 'success' => 0, 'fail' => 0];
    }
    $byEndpoint[$ep]['times'][] = $res['time_ms'];
    if ($res['success']) {
        $byEndpoint[$ep]['success']++;
    } else {
        $byEndpoint[$ep]['fail']++;
    }
}

sort($times);
$minTime = !empty($times) ? min($times) : 0;
$maxTime = !empty($times) ? max($times) : 0;
$avgTime = !empty($times) ? array_sum($times) / count($times) : 0;
$p50 = calculatePercentile($times, 50);
$p90 = calculatePercentile($times, 90);
$p95 = calculatePercentile($times, 95);
$p99 = calculatePercentile($times, 99);

echo "\n===============================================================\n";
echo " RESUMEN DE RENDIMIENTO\n";
echo "===============================================================\n";
printf(" Total de peticiones:      %d\n", $totalRequests);
printf(" Exitosas:                 %d (%.1f%%)\n", $successfulRequests, ($successfulRequests / max(1, $totalRequests)) * 100);
printf(" Fallidas:                 %d\n", $totalRequests - $successfulRequests);
echo "---------------------------------------------------------------\n";
printf(" Tiempo Mínimo:            %.2f ms\n", $minTime);
printf(" Tiempo Promedio:          %.2f ms\n", $avgTime);
printf(" Tiempo P50 (Mediana):     %.2f ms\n", $p50);
printf(" Tiempo P90:               %.2f ms\n", $p90);
printf(" Tiempo P95 (Objetivo):    %.2f ms\n", $p95);
printf(" Tiempo P99:               %.2f ms\n", $p99);
printf(" Tiempo Máximo:            %.2f ms\n", $maxTime);
echo "===============================================================\n";

echo "\nDesglose por flujo:\n";
foreach ($byEndpoint as $name => $data) {
    sort($data['times']);
    $epP95 = calculatePercentile($data['times'], 95);
    $epAvg = array_sum($data['times']) / max(1, count($data['times']));
    printf("  %-45s | Total: %3d | P95: %7.2f ms | Avg: %7.2f ms\n", $name, count($data['times']), $epP95, $epAvg);
}

echo "\n===============================================================\n";
if ($p95 < 2000) {
    echo " RESULTADO: APROBADO (P95 = " . round($p95, 2) . " ms < 2000 ms)\n";
    echo " Cumple con el requisito de rendimiento del SRS.\n";
    echo "===============================================================\n";
    exit(0);
} else {
    echo " RESULTADO: REPROBADO (P95 = " . round($p95, 2) . " ms >= 2000 ms)\n";
    echo " No cumple con el límite de 2000 ms.\n";
    echo "===============================================================\n";
    exit(1);
}
