<?php
/**
 * process_payment.php
 *
 * Direct payment simulation logic has been completely removed in favor of live Flutterwave gateway processing.
 * All payment requests are routed through initialize_payment.php and verified via verify_payment.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

jsonResponse([
    'success' => false,
    'message' => 'Simulated payments are disabled. Please use official Flutterwave checkout.'
], 400);
?>
