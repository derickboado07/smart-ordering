<?php
session_start();
// In your Payments.php file, add this at the top:
$order_id = $_GET['order_id'] ?? ($_SESSION['pending_order_id'] ?? null);
$order_total = $_SESSION['order_total'] ?? 0;

error_log("💰 PAYMENT PAGE - Order ID: " . $order_id . ", Total: " . $order_total);

if (!$order_id) {
    error_log("❌ Payment page accessed without order_id");
    // You might want to redirect back or show an error
}

// Convert to array for compatibility with existing payment.js
$order_ids = [$order_id];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Paydesign.css" />
    <title>Online Payment</title>
</head>
<body>
 <!-- Back button (fixed top-right with icon) -->
  <a href="../HomePage/MainHome.php" id="backToHome" title="Back to Home" style="position:fixed; top:14px; right:16px; display:flex; align-items:center; gap:8px; padding:8px 12px; background:linear-gradient(135deg,#ffffff,#f6f6f6); color:#333; text-decoration:none; border-radius:24px; border:1px solid #ddd; box-shadow:0 6px 18px rgba(0,0,0,0.08); z-index:1000;">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M15 18l-6-6 6-6" />
    </svg>
    <span style="font-weight:600; font-size:14px;">Back</span>
  </a>
 <!-- Header -->
  <div class="header">
    <img src="../Images/Icon.png" alt="Logo">
    <h1>AratCoffee</h1>
  </div>

  <!-- Payment Container -->
  <div class="payment-container">

    <!-- Left: Payment Method -->
    <div class="payment-method">
      <h3>Payment Method</h3>
      <a href="Cash.php" class="method-btn">💵 Cash Payment</a>
      <a href="Payments.php" class="method-btn">💳 Online Payment</a>
    </div>

    <!-- Middle: E-Wallet -->
    <div class="e-wallet">
      <h3>E-Wallet</h3>
      <div class="wallet-option">
        <input type="radio" name="wallet" value="gcash" checked>
        <img src="../Images/GCash-Logo.jpg" alt="GCash">
        <span>GCash Payment</span>
      </div>
      <div class="wallet-option">
        <input type="radio" name="wallet" value="qrph">
        <img src="../Images/QRPH-Logo.png" alt="QRPH">
        <span>QRPH Payment</span>
      </div>
    </div>

    <!-- Right: Payment Info -->
    <div class="payment-info">
      <h4>Payment Information:</h4>
      <p><strong>Date:</strong> <span id="paymentDate"></span></p>
  <p>Subtotal: <span id="subtotal">₱<?php echo number_format($order_total, 2); ?></span></p>
  <p>VAT (included): <span id="vatInfo">₱<?php echo number_format($order_total - ($order_total / 1.12), 2); ?></span></p>
  <p>Total Pay: <span id="totalPay">₱<?php echo number_format($order_total, 2); ?></span></p>
      <p>Discount: <span id="discountInfo">None</span></p>
  <p><b>Final Total: <span id="finalPay">₱<?php echo number_format($order_total, 2); ?></span></b></p>

      <!-- Hidden fields for order data -->
      <input type="hidden" id="orderIds" value="<?php echo htmlspecialchars(json_encode([$order_id])); ?>">
      <input type="hidden" id="orderTotal" value="<?php echo $order_total; ?>">

      <!-- Rest of your code remains the same -->
      <div class="discount-box">
        <table>
          <tr>
            <td><label><input type="radio" name="discount" value="senior"> Senior Citizen</label></td>
            <td>20%</td>
          </tr>
          <tr>
            <td><label><input type="radio" name="discount" value="student"> Student</label></td>
            <td>20%</td>
          </tr>
          <tr>
            <td><label><input type="radio" name="discount" value="none" checked> None</label></td>
          </tr>
        </table>
      </div>

      <div class="qr-section" id="gcashSection">
        <h4>Pay with <img src="../Images/GCash-Logo.jpg" alt="gcash" class="gcash-small"></h4>
        <p id="gcashName">Na***** F**</p>
        <img src="../Images/GCASH.jpg" alt="GCash QR Code" class="qr-code">

        <div class="reference-section" style="margin: 15px 0;">
          <label for="referenceNumber" style="display: block; margin-bottom: 8px; font-weight: bold;">
            Enter GCash Reference Number:
          </label>
          <input type="text"
                 id="referenceNumber"
                 placeholder="e.g., GC1234567890"
                 style="padding: 8px; width: 200px; border: 1px solid #ccc; border-radius: 4px;"
                 maxlength="20">
          <p style="font-size: 12px; color: #666; margin: 5px 0;">
            ⓘ After scanning QR, enter the reference number from GCash
          </p>
        </div>

        <p><a href="#" id="successfulPaymentLink">Confirm Payment</a></p>
      </div>

      <div class="qr-section" id="qrphSection" style="display: none;">
        <h4>Pay with <img src="../Images/QRPH-Logo.png" alt="qrph" class="gcash-small"></h4>
        <p id="qrphName">QRPH User</p>
        <img src="../Images/QRPH.jpg" alt="QRPH QR Code" class="qr-code">

        <div class="reference-section" style="margin: 15px 0;">
          <label for="qrphReferenceNumber" style="display: block; margin-bottom: 8px; font-weight: bold;">
            Enter QRPH Reference Number:
          </label>
          <input type="text"
                 id="qrphReferenceNumber"
                 placeholder="e.g., QR1234567890"
                 style="padding: 8px; width: 200px; border: 1px solid #ccc; border-radius: 4px;"
                 maxlength="20">
          <p style="font-size: 12px; color: #666; margin: 5px 0;">
            ⓘ After scanning QR, enter the reference number from QRPH
          </p>
        </div>

        <p><a href="#" id="qrphSuccessfulPaymentLink">Confirm Payment</a></p>
      </div>
    </div>
  </div>

  <?php $v = @filemtime(__DIR__ . '/payment.js') ?: time(); ?>
  <script src="payment.js?v=<?php echo $v; ?>"></script>

  <!-- Inline Receipt Viewer -->
  <div id="receiptSection" style="display:none; position:fixed; inset:0; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; width:90%; max-width:900px; height:90%; border-radius:8px; box-shadow:0 10px 30px rgba(0,0,0,0.3); display:flex; flex-direction:column;">
      <div style="padding:10px 14px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #eee;">
        <h3 style="margin:0; font-family:Arial, sans-serif;">Receipt Preview</h3>
        <div>
          <button id="printReceiptBtn" style="margin-right:8px; padding:8px 12px; background:#2b7cff; color:#fff; border:none; border-radius:4px; cursor:pointer;">Print</button>
          <button id="closeReceiptBtn" style="padding:8px 12px; background:#888; color:#fff; border:none; border-radius:4px; cursor:pointer;">Close</button>
        </div>
      </div>
      <div style="flex:1;">
        <iframe id="receiptFrame" title="Receipt" style="width:100%; height:100%; border:0;" src="about:blank"></iframe>
      </div>
    </div>
  </div>

  <script>
// Pass the order_id to your payment JavaScript
const orderId = <?php echo json_encode($order_id); ?>;
const orderTotal = <?php echo json_encode($order_total); ?>;

console.log("Payment page loaded - Order ID:", orderId, "Total:", orderTotal);

// Use these variables in your payment processing
</script>
</body>
</html>