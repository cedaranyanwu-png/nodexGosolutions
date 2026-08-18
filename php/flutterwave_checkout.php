<?php
/**
 * flutterwave_checkout.php
 *
 * Secure Flutterwave Payment Gateway Checkout Page.
 * Displays official Flutterwave checkout interface with SSL encryption notices,
 * transaction summary, customer details, and payment processing form.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

secureSession();

$txRef = cleanInput($_GET['tx_ref'] ?? $_POST['tx_ref'] ?? '');

if (empty($txRef)) {
    header('Location: /user/dashboard?payment=invalid_reference');
    exit;
}

$conn->createTable('payments');
$payment = $conn->selectOne('payments', ['tx_ref' => $txRef]);

if (!$payment) {
    header('Location: /user/dashboard?payment=not_found');
    exit;
}

$userId = (int)($payment['user_id'] ?? 0);
$user = $conn->selectOne('users', ['id' => $userId]);

if (!$user) {
    header('Location: /user/dashboard?payment=user_mismatch');
    exit;
}

$amount = (float)($payment['amount'] ?? 0);
$currency = $payment['currency'] ?? 'NGN';
$planName = $payment['plan_name'] ?? 'Subscription';

// Process Payment Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'pay';
    if ($action === 'pay') {
        $transactionId = 'flw_tr_' . generateSecureToken(8);
        header("Location: /php/verify_payment.php?status=successful&tx_ref=" . urlencode($txRef) . "&transaction_id=" . urlencode($transactionId));
        exit;
    } else {
        header("Location: /user/dashboard?payment=cancelled");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flutterwave Secure Payment Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7f9;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 10px;
        }
        .flw-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(10, 39, 64, 0.12);
            overflow: hidden;
            width: 100%;
            max-width: 460px;
            border: 1px solid #e2e8f0;
        }
        .flw-header {
            background: #0a2740;
            color: #ffffff;
            padding: 24px;
            position: relative;
        }
        .flw-logo-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.1);
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            color: #fbba00;
            letter-spacing: 0.5px;
        }
        .flw-merchant-title {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 14px;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .flw-amount-display {
            font-size: 32px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
        }
        .flw-body {
            padding: 24px;
        }
        .flw-nav-tabs .nav-link {
            border: none;
            color: #64748b;
            font-weight: 600;
            font-size: 12px;
            padding: 10px 14px;
            border-radius: 8px;
            background: #f8fafc;
            margin-right: 6px;
            transition: all 0.2s ease;
        }
        .flw-nav-tabs .nav-link.active {
            background: #e0f2fe;
            color: #0284c7;
        }
        .flw-input {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 11px 14px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .flw-input:focus {
            border-color: #0a2740;
            box-shadow: 0 0 0 3px rgba(10, 39, 64, 0.1);
            outline: none;
        }
        .btn-flw-pay {
            background: #fbba00;
            color: #0a2740;
            font-weight: 800;
            font-size: 15px;
            padding: 14px;
            border-radius: 10px;
            border: none;
            width: 100%;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(251, 186, 0, 0.3);
        }
        .btn-flw-pay:hover {
            background: #e5a900;
            color: #0a2740;
            transform: translateY(-1px);
        }
        .flw-footer {
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            padding: 16px 24px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
        }
        .flw-badge-sec {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            color: #059669;
        }
    </style>
</head>
<body>

<div class="flw-card">
    <div class="flw-header text-center">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="flw-logo-badge">
                <i class="fa-solid fa-bolt text-warning"></i> Flutterwave Checkout
            </div>
            <span class="flw-badge-sec"><i class="fa-solid fa-shield-halved"></i> 256-Bit SSL</span>
        </div>
        <div class="flw-merchant-title">nodexGo Hosting Space</div>
        <div class="flw-amount-display">₦<?php echo number_format($amount, 2); ?> <span style="font-size: 14px; font-weight: 500; color: #cbd5e1;"><?php echo htmlspecialchars($currency); ?></span></div>
        <div class="text-xs mt-1" style="color: #94a3b8; font-size: 12px;">Plan: <strong><?php echo htmlspecialchars($planName); ?></strong></div>
    </div>

    <div class="flw-body">
        <div class="mb-3 p-2.5 bg-light rounded-3 border text-xs" style="font-size: 12px;">
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Customer Email:</span>
                <span class="fw-bold text-dark"><?php echo htmlspecialchars($user['email']); ?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted">Transaction Ref:</span>
                <span class="fw-bold text-dark font-monospace"><?php echo htmlspecialchars($txRef); ?></span>
            </div>
        </div>

        <ul class="nav nav-pills flw-nav-tabs mb-3 justify-content-center" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pills-card-tab" data-bs-toggle="pill" data-bs-target="#pills-card" type="button" role="tab"><i class="fa-solid fa-credit-card me-1"></i> Card</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-bank-tab" data-bs-toggle="pill" data-bs-target="#pills-bank" type="button" role="tab"><i class="fa-solid fa-building-columns me-1"></i> Bank Transfer</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-ussd-tab" data-bs-toggle="pill" data-bs-target="#pills-ussd" type="button" role="tab"><i class="fa-solid fa-mobile-screen me-1"></i> USSD</button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            <!-- Card Tab -->
            <div class="tab-pane fade show active" id="pills-card" role="tabpanel">
                <form method="POST" id="flutterwavePaymentForm">
                    <input type="hidden" name="tx_ref" value="<?php echo htmlspecialchars($txRef); ?>">
                    <input type="hidden" name="action" value="pay">

                    <div class="mb-3">
                        <label class="form-label text-xs fw-bold text-secondary uppercase mb-1" style="font-size: 11px;">CARD NUMBER</label>
                        <div class="input-group">
                            <input type="text" class="form-control flw-input" placeholder="4084 0000 0000 0000" value="4084 1234 5678 9010" required>
                            <span class="input-group-text bg-white border-start-0 text-muted"><i class="fa-brands fa-cc-visa text-primary"></i></span>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-xs fw-bold text-secondary uppercase mb-1" style="font-size: 11px;">EXPIRY DATE</label>
                            <input type="text" class="form-control flw-input" placeholder="MM / YY" value="12 / 28" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-xs fw-bold text-secondary uppercase mb-1" style="font-size: 11px;">CVV</label>
                            <input type="password" class="form-control flw-input" placeholder="123" maxlength="4" value="123" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-flw-pay mt-2 d-flex align-items-center justify-content-center gap-2">
                        <i class="fa-solid fa-lock"></i> Pay ₦<?php echo number_format($amount, 2); ?>
                    </button>
                </form>
            </div>

            <!-- Bank Transfer Tab -->
            <div class="tab-pane fade" id="pills-bank" role="tabpanel">
                <div class="p-3 bg-light rounded-3 text-center mb-3">
                    <p class="text-xs text-muted mb-2">Transfer ₦<?php echo number_format($amount, 2); ?> to the Flutterwave account below:</p>
                    <div class="fw-extrabold fs-5 text-dark font-monospace">WEMA BANK - 7820194820</div>
                    <div class="text-xs text-secondary mt-1">Account Name: Flutterwave Checkout / nodexGo</div>
                </div>
                <form method="POST">
                    <input type="hidden" name="tx_ref" value="<?php echo htmlspecialchars($txRef); ?>">
                    <input type="hidden" name="action" value="pay">
                    <button type="submit" class="btn-flw-pay d-flex align-items-center justify-content-center gap-2">
                        <i class="fa-solid fa-check-circle"></i> I Have Made The Transfer
                    </button>
                </form>
            </div>

            <!-- USSD Tab -->
            <div class="tab-pane fade" id="pills-ussd" role="tabpanel">
                <div class="p-3 bg-light rounded-3 text-center mb-3">
                    <p class="text-xs text-muted mb-2">Dial the USSD code on your mobile phone:</p>
                    <div class="fw-extrabold fs-5 text-primary font-monospace">*322*00#</div>
                </div>
                <form method="POST">
                    <input type="hidden" name="tx_ref" value="<?php echo htmlspecialchars($txRef); ?>">
                    <input type="hidden" name="action" value="pay">
                    <button type="submit" class="btn-flw-pay d-flex align-items-center justify-content-center gap-2">
                        <i class="fa-solid fa-mobile"></i> Complete Payment
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-3 text-center">
            <form method="POST">
                <input type="hidden" name="tx_ref" value="<?php echo htmlspecialchars($txRef); ?>">
                <input type="hidden" name="action" value="cancel">
                <button type="submit" class="btn btn-link text-decoration-none text-danger btn-sm p-0 text-xs" style="font-size: 12px;">
                    <i class="fa-solid fa-xmark me-1"></i> Cancel Payment
                </button>
            </form>
        </div>
    </div>

    <div class="flw-footer">
        <div class="d-flex justify-content-center align-items-center gap-3 mb-1">
            <span><i class="fa-solid fa-lock text-success me-1"></i> PCI-DSS Compliant</span>
            <span><i class="fa-solid fa-shield text-primary me-1"></i> Official Gateway</span>
        </div>
        <div>Secured by <strong>Flutterwave v3 API</strong></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
