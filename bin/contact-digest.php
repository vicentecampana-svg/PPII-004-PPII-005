<?php

/**
 * SEC-09: envía el aviso agrupado de consultas de contacto si corresponde.
 *
 * Las consultas también disparan el aviso al llegar, pero sin este script las
 * últimas consultas acumuladas esperarían hasta que llegue otra. Programarlo
 * con cron, con la misma configuración (.env) que la aplicación, por ejemplo
 * cada 5 minutos:
 *
 *   0-59/5 * * * * php /var/www/html/bin/contact-digest.php
 *
 * Solo envía si ya pasó CONTACT_DIGEST_INTERVAL_MINUTES desde el último aviso
 * y hay al menos una consulta sin avisar, así que ejecutarlo seguido no
 * genera correos de más.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    $sent = (new \App\Services\ContactDigestService())->sendIfDue();
    fwrite(STDOUT, $sent ? "Aviso de consultas enviado.\n" : "Sin aviso pendiente.\n");
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, 'Error al enviar el aviso de consultas: ' . $e->getMessage() . "\n");
    exit(1);
}
