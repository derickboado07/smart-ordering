<?php
session_start();

// Get order data from session - UPDATED FOR NEW DATABASE STRUCTURE
$order_id = $_SESSION['pending_order_id'] ?? null;
$order_total = $_SESSION['order_total'] ?? 0;

// If no pending order, redirect back to menu
if (empty($order_id)) {
    header("Location: ../CoffeeMenu/HomeMenu.php");
    exit;
}

// Convert to array for compatibility with existing payment.js
$order_ids = [$order_id];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="cashdesign.css" />
  <title>Cash Payment</title>
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
      <h3>Cash</h3>
      <div class="wallet-option">
        <input type="radio" name="wallet" checked>
        <span>Cash Payment</span>
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

      <!-- Rest of your cash payment form remains the same -->
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

      <div class="cash-section">
        <h4>Pay with Cash</h4>
        <p><b>Total Pay: <span id="finalPayCash">₱<?php echo number_format($order_total, 2); ?></span></b></p>

        <label for="cashInput"><b>Cash:</b></label>
        <input type="text" id="cashInput" placeholder="Enter cash amount" />

        <button id="enterCashBtn">Enter</button>

        <p><b>Discounted:</b> <span id="discountedAmount">₱0.00</span></p>
        <p><b>Change:</b> <span id="changeAmount">₱0.00</span></p>

        <button id="confirmPaymentBtn" disabled>Confirm Payment</button>
        <a id="listOrdersLink" class="list-orders" href="../List-Orders/Orderlist.php" style="display:none;">List Orders</a>
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
</body>
</html>