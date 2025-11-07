<?php
$FPDF_AVAILABLE = false;
$fpdfPath = __DIR__ . '/../lib/fpdf/fpdf.php';
if (file_exists($fpdfPath)) {
    require_once $fpdfPath;
    $FPDF_AVAILABLE = true;
}

if ($FPDF_AVAILABLE) {
class ReceiptPDF extends FPDF {
    function Header() {
        // Logo
        if (file_exists('../Images/aratcoffee.png')) {
            $this->Image('../Images/aratcoffee.png', 10, 10, 30);
        } elseif (file_exists('../Images/Icon.png')) { // fallback
            $this->Image('../Images/Icon.png', 10, 10, 20);
        }

        // Shop Name
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'AratCoffee', 0, 1, 'C');

        // Address/Tagline
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, 'Your Favorite Coffee Shop', 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-30);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 5, 'Thank you for your purchase!', 0, 1, 'C');
        $this->Cell(0, 5, 'Visit us again soon!', 0, 0, 'C');
    }
}
}

function buildReceiptPdf($conn, $order_id) {
    global $FPDF_AVAILABLE;
    if (!$FPDF_AVAILABLE) return [null, null];
    // Fetch order details
    $order_sql = "SELECT o.*, DATE_FORMAT(o.created_at, '%M %d, %Y %h:%i %p') as formatted_date
                  FROM orders o WHERE o.id = ?";
    $order_stmt = $conn->prepare($order_sql);
    $order_stmt->bind_param("i", $order_id);
    $order_stmt->execute();
    $order = $order_stmt->get_result()->fetch_assoc();

    if (!$order) {
        return [null, null];
    }

    // Fetch order items
    $items_sql = "SELECT item_name, quantity, price, total FROM order_items WHERE order_id = ?";
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();

    // Create PDF
    $pdf = new ReceiptPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 10);

    // Transaction Details
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'RECEIPT', 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(50, 6, 'Order Number:', 0, 0);
    $pdf->Cell(0, 6, '#' . str_pad($order_id, 6, '0', STR_PAD_LEFT), 0, 1);

    $pdf->Cell(50, 6, 'Date & Time:', 0, 0);
    $pdf->Cell(0, 6, $order['formatted_date'], 0, 1);

    $pdf->Cell(50, 6, 'Payment Method:', 0, 0);
    $pdf->Cell(0, 6, ucfirst($order['payment_method'] ?? 'Cash'), 0, 1);

    if (!empty($order['discount_type']) && $order['discount_type'] !== 'none') {
        $pdf->Cell(50, 6, 'Discount:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($order['discount_type']) . ' (20%)', 0, 1);
    }

    $pdf->Ln(5);

    // Items Header
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(80, 8, 'Item', 1, 0, 'L');
    $pdf->Cell(20, 8, 'Qty', 1, 0, 'C');
    $pdf->Cell(25, 8, 'Price', 1, 0, 'R');
    $pdf->Cell(25, 8, 'Total', 1, 1, 'R');

    // Items
    $pdf->SetFont('Arial', '', 9);
    $subtotal = 0;

    while ($item = $items_result->fetch_assoc()) {
        $pdf->Cell(80, 6, $item['item_name'], 1, 0, 'L');
        $pdf->Cell(20, 6, $item['quantity'], 1, 0, 'C');
        $pdf->Cell(25, 6, '₱' . number_format($item['price'], 2), 1, 0, 'R');
        $pdf->Cell(25, 6, '₱' . number_format($item['total'], 2), 1, 1, 'R');
        $subtotal += $item['total'];
    }

    $pdf->Ln(3);

    // Totals
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(125, 6, 'Subtotal:', 0, 0, 'R');
    $pdf->Cell(25, 6, '₱' . number_format($subtotal, 2), 0, 1, 'R');

    if (!empty($order['discount_amount']) && floatval($order['discount_amount']) > 0) {
        $pdf->Cell(125, 6, 'Discount:', 0, 0, 'R');
        $pdf->Cell(25, 6, '-₱' . number_format($order['discount_amount'], 2), 0, 1, 'R');
    }

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(125, 8, 'TOTAL:', 0, 0, 'R');
    $pdf->Cell(25, 8, '₱' . number_format($order['total_amount'], 2), 0, 1, 'R');

    if (($order['payment_method'] ?? '') === 'cash' && isset($order['cash_amount'])) {
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(125, 6, 'Cash Tendered:', 0, 0, 'R');
        $pdf->Cell(25, 6, '₱' . number_format($order['cash_amount'], 2), 0, 1, 'R');

        $pdf->Cell(125, 6, 'Change:', 0, 0, 'R');
        $pdf->Cell(25, 6, '₱' . number_format($order['change_amount'], 2), 0, 1, 'R');
    }

    return [$pdf, $order];
}

function generateReceipt($order_id, $stream = false) {
    include 'db_connect.php';

    list($pdf, $order) = buildReceiptPdf($conn, $order_id);
    if (!$pdf) {
        // Fallback to HTML receipt if FPDF unavailable or order not found
        // Fetch order for HTML mode as well
        if (!isset($order)) {
            $stmt = $conn->prepare("SELECT o.*, DATE_FORMAT(o.created_at, '%M %d, %Y %h:%i %p') as formatted_date FROM orders o WHERE o.id = ?");
            $stmt->bind_param('i', $order_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$order) return false;
        }

        // Items
        $items_stmt = $conn->prepare("SELECT item_name, quantity, price, total FROM order_items WHERE order_id = ?");
        $items_stmt->bind_param('i', $order_id);
        $items_stmt->execute();
        $items_res = $items_stmt->get_result();
        $items = [];
        $subtotal = 0;
        while ($row = $items_res->fetch_assoc()) { $items[] = $row; $subtotal += $row['total']; }
        $items_stmt->close();

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8" />'
              . '<title>Receipt #' . htmlspecialchars(str_pad($order_id,6,'0',STR_PAD_LEFT)) . '</title>'
              . '<style>body{font-family:Arial,Helvetica,sans-serif;color:#222;margin:0} .wrap{max-width:700px;margin:16px auto;padding:16px} .hdr{display:flex;align-items:center;gap:12px;border-bottom:1px solid #eee;padding-bottom:10px} .hdr img{height:40px} .title{font-size:20px;font-weight:bold} table{width:100%;border-collapse:collapse;margin-top:12px} th,td{border:1px solid #ddd;padding:6px;font-size:12px} th{background:#f7f7f7;text-align:left} .tr{text-align:right} .center{text-align:center} .totals td{border:none} .note{margin-top:16px;text-align:center;color:#555;font-style:italic}</style>'
              . '</head><body><div class="wrap">'
              . '<div class="hdr">'
              . (file_exists(__DIR__.'/../Images/Icon.png')? '<img src="../Images/Icon.png" alt="Logo" />' : '')
              . '<div><div class="title">AratCoffee</div><div>Your Favorite Coffee Shop</div></div>'
              . '</div>'
              . '<h3 class="center">RECEIPT</h3>'
              . '<div>Order Number: #' . htmlspecialchars(str_pad($order_id,6,'0',STR_PAD_LEFT)) . '</div>'
              . '<div>Date & Time: ' . htmlspecialchars($order['formatted_date'] ?? '') . '</div>'
              . '<div>Payment Method: ' . htmlspecialchars(ucfirst($order['payment_method'] ?? 'Cash')) . '</div>';
        if (!empty($order['discount_type']) && $order['discount_type'] !== 'none') {
            $html .= '<div>Discount: ' . htmlspecialchars(ucfirst($order['discount_type'])) . ' (20%)</div>';
        }
        $html .= '<table><thead><tr><th>Item</th><th class="center">Qty</th><th class="tr">Price</th><th class="tr">Total</th></tr></thead><tbody>';
        foreach ($items as $it) {
            $html .= '<tr>'
                  . '<td>' . htmlspecialchars($it['item_name']) . '</td>'
                  . '<td class="center">' . intval($it['quantity']) . '</td>'
                  . '<td class="tr">₱' . number_format($it['price'],2) . '</td>'
                  . '<td class="tr">₱' . number_format($it['total'],2) . '</td>'
                  . '</tr>';
        }
        $html .= '</tbody></table>';
        $html .= '<table class="totals" style="margin-top:8px"><tr><td class="tr" style="width:85%">Subtotal:</td><td class="tr" style="width:15%">₱' . number_format($subtotal,2) . '</td></tr>';
        if (!empty($order['discount_amount']) && floatval($order['discount_amount'])>0) {
            $html .= '<tr><td class="tr">Discount:</td><td class="tr">-₱' . number_format($order['discount_amount'],2) . '</td></tr>';
        }
        $html .= '<tr><td class="tr"><strong>TOTAL:</strong></td><td class="tr"><strong>₱' . number_format($order['total_amount'],2) . '</strong></td></tr>';
        if (($order['payment_method'] ?? '')==='cash' && isset($order['cash_amount'])) {
            $html .= '<tr><td class="tr">Cash Tendered:</td><td class="tr">₱' . number_format($order['cash_amount'],2) . '</td></tr>'
                  .  '<tr><td class="tr">Change:</td><td class="tr">₱' . number_format($order['change_amount'],2) . '</td></tr>';
        }
        $html .= '</table><div class="note">Thank you for your purchase!</div></div></body></html>';

    // Save HTML copy
        $receipts_dir = '../receipts';
        if (!is_dir($receipts_dir)) { mkdir($receipts_dir, 0755, true); }
        $filename = 'receipt_' . $order_id . '_' . date('Ymd_His') . '.html';
        $filepath = $receipts_dir . '/' . $filename;
        @file_put_contents($filepath, $html);

    // Optional email
    maybeSendReceiptEmail($filepath, $order, $_GET['email'] ?? null);

        if ($stream) {
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            return 'streamed_html';
        }
        return $filename; // return HTML filename
    }

    // Always save a copy
    $receipts_dir = '../receipts';
    if (!is_dir($receipts_dir)) {
        mkdir($receipts_dir, 0755, true);
    }

    $filename = 'receipt_' . $order_id . '_' . date('Ymd_His') . '.pdf';
    $filepath = $receipts_dir . '/' . $filename;
    $pdf->Output($filepath, 'F');

    // Optional email
    maybeSendReceiptEmail($filepath, $order, $_GET['email'] ?? null);

    if ($stream) {
        // Stream inline to browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($filepath);
        return 'streamed';
    }

    return $filename;
}

// Optional: email the receipt if email is available and send_email=1
function maybeSendReceiptEmail($filepath, $order, $toEmail = null) {
    if (!isset($_GET['send_email']) || $_GET['send_email'] != '1') return;

    // Determine recipient
    $recipient = $toEmail ?: ($order['customer_email'] ?? null);
    if (!$recipient) return; // no email available

    // Try to load PHPMailer and SMTP config if present
    $smtpConfig = __DIR__ . '/../LogIn/Admin/smtp_config.php';
    $phpMailerA = __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    if (!file_exists($smtpConfig) || !file_exists($phpMailerA)) return;

    require_once $smtpConfig;
    require_once __DIR__ . '/../vendor/autoload.php';

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        if (function_exists('smtp_config') && is_array(smtp_config())) {
            $cfg = smtp_config();
            $mail->isSMTP();
            $mail->Host = $cfg['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $cfg['username'];
            $mail->Password = $cfg['password'];
            $mail->SMTPSecure = $cfg['encryption'] ?? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $cfg['port'];
            $mail->setFrom($cfg['from_email'], $cfg['from_name'] ?? 'AratCoffee');
        }

        $mail->addAddress($recipient);
        $mail->Subject = 'Your AratCoffee Receipt #' . str_pad($order['id'] ?? '', 6, '0', STR_PAD_LEFT);
        $mail->Body = 'Thank you for your purchase! Attached is your receipt.';
        $mail->addAttachment($filepath);
        $mail->send();
    } catch (Exception $e) {
        // Silently ignore email errors
        error_log('Receipt email error: ' . $e->getMessage());
    }
}

// Handle direct access
if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $stream = isset($_GET['stream']) && $_GET['stream'] == '1';

    if ($stream) {
        // Stream and exit
        $res = generateReceipt($order_id, true);
        // If failed, return a simple HTML page so iframe shows something helpful
        if ($res === false) {
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Receipt Error</title>'
                . '<style>body{font-family:Arial,Helvetica,sans-serif;padding:20px;color:#333} .box{max-width:700px;margin:0 auto;border:1px solid #eee;border-radius:8px;padding:16px;background:#fafafa}</style>'
                . '</head><body><div class="box"><h3>Receipt not available</h3>'
                . '<p>We couldn\'t find receipt data for order #' . htmlspecialchars($order_id) . '.</p>'
                . '<p>Please refresh the page or try again.</p></div></body></html>';
        }
        exit;
    }

    // Default: generate and return JSON with filename
    $filename = generateReceipt($order_id, false);
    if ($filename) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'filename' => $filename]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Failed to generate receipt']);
    }
}
?>
