<?php
/**
 * invoice_gen.php
 *
 * Manages customer listings, invoice creations, and syncs new clients into our JSON Database.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require the centralized JSON Database class from same directory
require_once __DIR__ . '/database.php';

// Instantiate the Database pointing to /app/databases/invoice
$db = new Database(__DIR__ . '/../databases', 'invoice');

// Ensure tables exist in the database
$db->createTable('customers');
$db->createTable('invoices');

// Capture active query string actions
$action = $_GET['action'] ?? '';

// Check if action matches fetch customers request
if ($action === 'get_customers') {
    // Return JSON response content
    header('Content-Type: application/json');

    // Retrieve customers listing from database
    $customers = $db->select('customers');

    // Seed initial client listings if table is completely empty
    if (empty($customers)) {
        // Seed Acme Corp client
        $db->insert('customers', [
            'name' => 'Acme Corp',
            'details' => "123 Tech Lane\nSan Francisco, CA\ncontact@acme.com"
        ]);
        // Seed Stark Industries client
        $db->insert('customers', [
            'name' => 'Stark Industries',
            'details' => "10880 Malibu Point\nMalibu, CA\naccounting@stark.com"
        ]);
        // Reload seeded customers list
        $customers = $db->select('customers');
    }

    // Output success response payload
    echo json_encode(['success' => true, 'customers' => $customers]);
    // Terminate
    exit;
}

// Check if request is a POST action to save invoice and sync client details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_invoice') {
    // Return JSON response content
    header('Content-Type: application/json');

    // Decode incoming raw invoice data parameters
    $rawInvoice = $_POST['invoice'] ?? '{}';
    $invoice = json_decode($rawInvoice, true);

    // Validate that invoice number is present
    if (empty($invoice['invoice_num'])) {
        // Return bad request error
        echo json_encode(['success' => false, 'error' => 'Invoice number required.']);
        // Stop execution
        exit;
    }

    // Insert invoice record into invoices JSON table
    $db->insert('invoices', $invoice);

    // Sync client name into customers list if not present
    if (!empty($invoice['client_name'])) {
        // Look up customer by exact case insensitive matching
        $exists = false;
        $customersList = $db->select('customers');
        foreach ($customersList as $cust) {
            if (strtolower($cust['name'] ?? '') === strtolower($invoice['client_name'])) {
                $exists = true;
                break;
            }
        }

        // If client is new, insert into customers table
        if (!$exists) {
            $db->insert('customers', [
                'name' => htmlspecialchars($invoice['client_name'], ENT_QUOTES, 'UTF-8'),
                'details' => htmlspecialchars($invoice['client_details'] ?? '', ENT_QUOTES, 'UTF-8')
            ]);
        }
    }

    // Output success confirmation
    echo json_encode(['success' => true]);
    // Terminate
    exit;
}
?>
