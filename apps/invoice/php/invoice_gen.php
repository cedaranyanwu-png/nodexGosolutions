<?php
declare(strict_types=1);

header('Content-Type: application/json');
$dataFile = __DIR__ . '/invoices.json';

function getStorage(string $file): array {
    if (!file_exists($file)) {
        $initial = ['customers' => [], 'invoices' => []];
        file_put_contents($file, json_encode($initial, JSON_PRETTY_PRINT));
        return $initial;
    }
    return json_decode(file_get_contents($file), true) ?? ['customers' => [], 'invoices' => []];
}

function saveStorage(string $file, array $data): void {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

$action = $_GET['action'] ?? '';
$data = getStorage($dataFile);

// Get Customer List
if ($action === 'get_customers') {
    // Default seed customers if empty
    if (empty($data['customers'])) {
        $data['customers'] = [
            ['id' => 1, 'name' => 'Acme Corp', 'details' => "123 Tech Lane\nSan Francisco, CA\ncontact@acme.com"],
            ['id' => 2, 'name' => 'Stark Industries', 'details' => "10880 Malibu Point\nMalibu, CA\naccounting@stark.com"]
        ];
        saveStorage($dataFile, $data);
    }

    echo json_encode(['success' => true, 'customers' => $data['customers']]);
    exit;
}

// Save Invoice & Customer Sync
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_invoice') {
    $rawInvoice = $_POST['invoice'] ?? '{}';
    $invoice = json_decode($rawInvoice, true);

    if (empty($invoice['invoice_num'])) {
        echo json_encode(['success' => false, 'error' => 'Invoice number required.']);
        exit;
    }

    // Save invoice record
    $data['invoices'][$invoice['invoice_num']] = $invoice;

    // Auto-save new customer if client name exists
    if (!empty($invoice['client_name'])) {
        $exists = false;
        foreach ($data['customers'] as $cust) {
            if (strtolower($cust['name']) === strtolower($invoice['client_name'])) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $data['customers'][] = [
                'id' => time(),
                'name' => htmlspecialchars($invoice['client_name'], ENT_QUOTES, 'UTF-8'),
                'details' => htmlspecialchars($invoice['client_details'], ENT_QUOTES, 'UTF-8')
            ];
        }
    }

    saveStorage($dataFile, $data);
    echo json_encode(['success' => true]);
    exit;
}