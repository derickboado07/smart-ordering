window.__payInit = window.__payInit || false;
window.onload = function () {
  if (window.__payInit) return; // guard against double init
  window.__payInit = true;
  // Show current date & time
  const now = new Date();
  const paymentDateEl = document.getElementById("paymentDate");
  if (paymentDateEl) {
    paymentDateEl.innerText = now.toLocaleString();
  }

  // Get order data from hidden fields
  const orderIds = JSON.parse(document.getElementById('orderIds').value);
  const orderTotal = parseFloat(document.getElementById('orderTotal').value);

  let subtotal = orderTotal || 0;
  let numericTotal = subtotal; // Total is the subtotal (VAT included)
  let finalTotal = numericTotal;
  let discountValue = 0;
  let discountType = 'none';

  // Set base totals
  const subtotalEl = document.getElementById("subtotal");
  const vatInfoEl = document.getElementById("vatInfo");
  const totalPayEl = document.getElementById("totalPay");
  const finalPayEl = document.getElementById("finalPay");
  const finalPayCashEl = document.getElementById("finalPayCash");

  if (subtotalEl) subtotalEl.innerText = "₱" + subtotal.toFixed(2);
  if (vatInfoEl) vatInfoEl.innerText = "₱" + (subtotal - (subtotal / 1.12)).toFixed(2);
  if (totalPayEl) totalPayEl.innerText = "₱" + numericTotal.toFixed(2);
  if (finalPayEl) finalPayEl.innerText = "₱" + numericTotal.toFixed(2);
  if (finalPayCashEl) finalPayCashEl.innerText = "₱" + numericTotal.toFixed(2);

  const discountInfoEl = document.getElementById("discountInfo");

  // --- Discount Handling ---
  const discountCheckboxes = document.querySelectorAll('input[name="discount"]');
  discountCheckboxes.forEach(checkbox => {
    checkbox.addEventListener("change", () => {
      discountType = 'none';
      discountValue = 0;

      // Priority: Senior > Student
      if (document.querySelector('input[value="senior"]')?.checked) {
        discountType = 'senior';
        discountValue = numericTotal * 0.20;
      } else if (document.querySelector('input[value="student"]')?.checked) {
        discountType = 'student';
        discountValue = numericTotal * 0.20;
      }

      // Calculate final total
      finalTotal = numericTotal - discountValue;

      // Update UI
      if (discountInfoEl) {
        discountInfoEl.innerText = discountType === 'none' ? 'None' :
          (discountType === 'senior' ? 'Senior Citizen 20%' : 'Student 20%');
      }
      
      if (finalPayEl) finalPayEl.innerText = "₱" + finalTotal.toFixed(2);
      if (finalPayCashEl) finalPayCashEl.innerText = "₱" + finalTotal.toFixed(2);

      const discountedSpan = document.getElementById("discountedAmount");
      if (discountedSpan) discountedSpan.innerText = "₱" + discountValue.toFixed(2);
    });
  });

  // --- Cash Payment Section ---
  const cashInput = document.getElementById("cashInput");
  const enterBtn = document.getElementById("enterCashBtn");
  const confirmBtn = document.getElementById("confirmPaymentBtn");
  const discountedSpan = document.getElementById("discountedAmount");
  const changeSpan = document.getElementById("changeAmount");
  const listOrdersLink = document.getElementById("listOrdersLink");

  if (cashInput) {
    // Prevent non-digits
    cashInput.addEventListener("input", (e) => {
      const cleaned = e.target.value.replace(/\D+/g, "");
      if (e.target.value !== cleaned) {
        e.target.value = cleaned;
      }
    });

    cashInput.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        if (enterBtn) enterBtn.click();
      }
    });
  }

  if (enterBtn) {
    enterBtn.addEventListener("click", () => {
      const raw = cashInput ? cashInput.value.trim() : "";
      const cash = raw === "" ? NaN : parseInt(raw, 10);

      if (isNaN(cash) || cash < 0) {
        alert("Please enter a valid whole-number cash amount (e.g. 250).");
        if (cashInput) cashInput.value = "";
        if (discountedSpan) discountedSpan.innerText = "₱0.00";
        if (changeSpan) changeSpan.innerText = "₱0.00";
        confirmBtn.disabled = true;
        return;
      }

      // Show discount amount
      if (discountedSpan) discountedSpan.innerText = "₱" + discountValue.toFixed(2);

      // Compute and show change
      if (cash < finalTotal) {
        if (changeSpan) changeSpan.innerText = "Not enough cash";
        confirmBtn.disabled = true;
      } else {
        const change = cash - finalTotal;
        if (changeSpan) changeSpan.innerText = "₱" + change.toFixed(2);
        confirmBtn.disabled = false;
      }
    });
  }

  // --- Confirm Payment Button (Cash) ---
  if (confirmBtn) {
    confirmBtn.addEventListener("click", async () => {
        const raw = cashInput ? cashInput.value.trim() : "";
        const cash = raw === "" ? NaN : parseInt(raw, 10);
        
        if (isNaN(cash) || cash < finalTotal) {
            alert("Please enter valid cash amount first.");
            return;
        }

        try {
            const paymentData = {
                order_ids: orderIds,
                payment_method: 'cash',
                discount_type: discountType,
                discount_amount: discountValue,
                final_total: finalTotal,
                cash_amount: cash,
                change_amount: cash - finalTotal
            };

            console.log("Sending payment data:", paymentData);

            const response = await fetch('../backend/process_payment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(paymentData)
            });

            const result = await response.json();
            console.log("Payment response:", result);

            if (result.status === 'success') {
              const oid = result.order_id || (orderIds && orderIds[0]) || null;
              // Show receipt inline instead of redirecting
              try {
                if (!oid) throw new Error('Missing order_id in response');
                showReceipt(oid);
              } catch (e) {
                console.error('Failed to display receipt inline:', e);
                alert('Payment processed! Opening receipt in a new tab...');
                if (oid) {
                  window.open('../backend/generate_receipt.php?order_id=' + encodeURIComponent(oid) + '&stream=1', '_blank');
                }
              }
              confirmBtn.style.display = 'none';
              if (listOrdersLink) listOrdersLink.style.display = 'block';
          } else {
                // Even on error, attempt to show whatever receipt we can (may show helpful error page)
                const oid = (orderIds && orderIds[0]) || null;
                if (oid) {
                  try { showReceipt(oid); } catch (e) {
                    console.error('Failed to display receipt inline after error:', e);
                    window.open('../backend/generate_receipt.php?order_id=' + encodeURIComponent(oid) + '&stream=1', '_blank');
                  }
                }
                alert('Error processing payment: ' + (result.message || 'Unknown error'));
                if (result.debug) {
                    console.error('Debug info:', result.debug);
                }
            }
        } catch (error) {
            console.error('Payment error:', error);
            // Try to show receipt anyway; the receipt page will show a helpful message if unavailable
            const oid = (orderIds && orderIds[0]) || null;
            if (oid) {
              try { showReceipt(oid); } catch (e) { window.open('../backend/generate_receipt.php?order_id=' + encodeURIComponent(oid) + '&stream=1', '_blank');}
            }
            alert('Network error processing payment. Check console for details.');
        }
    });
  }

  // --- Wallet Selection ---
  const walletRadios = document.querySelectorAll('input[name="wallet"]');
  walletRadios.forEach(radio => {
    radio.addEventListener("change", () => {
      const selectedWallet = document.querySelector('input[name="wallet"]:checked').value;

      // Show/hide sections based on selection
      const gcashSection = document.getElementById("gcashSection");
      const qrphSection = document.getElementById("qrphSection");

      if (selectedWallet === 'gcash') {
        if (gcashSection) gcashSection.style.display = 'block';
        if (qrphSection) qrphSection.style.display = 'none';
      } else if (selectedWallet === 'qrph') {
        if (gcashSection) gcashSection.style.display = 'none';
        if (qrphSection) qrphSection.style.display = 'block';
      }
    });
  });

  // --- GCash Payment Section ---
const successfulPaymentLink = document.getElementById("successfulPaymentLink");
const referenceInput = document.getElementById("referenceNumber");

if (successfulPaymentLink) {
  successfulPaymentLink.addEventListener("click", async function(e) {
      e.preventDefault();

      // Validate reference number
      const referenceNumber = referenceInput ? referenceInput.value.trim() : '';

      if (!referenceNumber) {
          alert("Please enter the GCash reference number before confirming payment.");
          if (referenceInput) referenceInput.focus();
          return;
      }

      if (referenceNumber.length < 5) {
          alert("Please enter a valid GCash reference number (at least 5 characters).");
          if (referenceInput) referenceInput.focus();
          return;
      }

      try {
          // FIXED: Use single order_id instead of order_ids array
          const paymentData = {
              order_id: orderIds[0], // Use the first order ID from array
              payment_method: 'gcash',
              discount_type: discountType,
              discount_amount: discountValue,
              final_total: finalTotal,
              cash_amount: finalTotal,
              change_amount: 0,
              reference_number: referenceNumber
          };

          console.log("Sending GCash payment data:", paymentData);

          const response = await fetch('../backend/process_payment.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(paymentData)
          });

          const result = await response.json();
          console.log("GCash payment response:", result);

      if (result.status === 'success') {
        const oid = result.order_id || (orderIds && orderIds[0]) || null;
        try {
          if (!oid) throw new Error('Missing order_id in response');
          showReceipt(oid);
        } catch (e) {
          console.error('Failed to display receipt inline:', e);
          alert('GCash payment processed! Opening receipt in a new tab...');
          if (oid) {
          window.open('../backend/generate_receipt.php?order_id=' + encodeURIComponent(oid) + '&stream=1', '_blank');
          }
        }
      } else {
        // Show receipt anyway if possible
        const oid = (orderIds && orderIds[0]) || null;
        if (oid) {
        try { showReceipt(oid); } catch (e) { window.open('../backend/generate_receipt.php?order_id=' + encodeURIComponent(oid) + '&stream=1', '_blank'); }
        }
        alert('❌ Error processing payment: ' + (result.message || 'Unknown error'));
              if (result.debug) {
                  console.error('Debug info:', result.debug);
              }
          }
      } catch (error) {
          console.error('GCash payment error:', error);
      const oid = (orderIds && orderIds[0]) || null;
      if (oid) { try { showReceipt(oid); } catch (e) { window.open('../backend/generate_receipt.php?order_id=' + encodeURIComponent(oid) + '&stream=1', '_blank'); } }
      alert('⚠️ Network error processing payment. Check console for details.');
      }
  });
}

  // --- QRPH Payment Section ---
const qrphSuccessfulPaymentLink = document.getElementById("qrphSuccessfulPaymentLink");
const qrphReferenceInput = document.getElementById("qrphReferenceNumber");

if (qrphSuccessfulPaymentLink) {
  qrphSuccessfulPaymentLink.addEventListener("click", async function(e) {
      e.preventDefault();

      // Validate reference number
      const referenceNumber = qrphReferenceInput ? qrphReferenceInput.value.trim() : '';

      if (!referenceNumber) {
          alert("Please enter the QRPH reference number before confirming payment.");
          if (qrphReferenceInput) qrphReferenceInput.focus();
          return;
      }

      if (referenceNumber.length < 5) {
          alert("Please enter a valid QRPH reference number (at least 5 characters).");
          if (qrphReferenceInput) qrphReferenceInput.focus();
          return;
      }

      try {
          const paymentData = {
              order_id: orderIds[0], // Use the first order ID from array
              payment_method: 'qrph',
              discount_type: discountType,
              discount_amount: discountValue,
              final_total: finalTotal,
              cash_amount: finalTotal,
              change_amount: 0,
              reference_number: referenceNumber
          };

          console.log("Sending QRPH payment data:", paymentData);

          const response = await fetch('../backend/process_payment.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(paymentData)
          });

          const result = await response.json();
          console.log("QRPH payment response:", result);

      if (result.status === 'success') {
        const oid = result.order_id || (orderIds && orderIds[0]) || null;
        try {
          if (!oid) throw new Error('Missing order_id in response');
          showReceipt(oid);
        } catch (e) {
          console.error('Failed to display receipt inline:', e);
          alert('QRPH payment processed! Opening receipt in a new tab...');
          if (oid) {
          window.open('../backend/generate_receipt.php?order_id=' + encodeURIComponent(oid) + '&stream=1', '_blank');
          }
        }
      } else {
        const oid = (orderIds && orderIds[0]) || null;
        if (oid) { try { showReceipt(oid); } catch (e) { window.open('../backend/generate_receipt.php?order_id=' + encodeURIComponent(oid) + '&stream=1', '_blank'); } }
        alert('❌ Error processing payment: ' + (result.message || 'Unknown error'));
              if (result.debug) {
                  console.error('Debug info:', result.debug);
              }
          }
      } catch (error) {
          console.error('QRPH payment error:', error);
      const oid = (orderIds && orderIds[0]) || null;
      if (oid) { try { showReceipt(oid); } catch (e) { window.open('../backend/generate_receipt.php?order_id=' + encodeURIComponent(oid) + '&stream=1', '_blank'); } }
      alert('⚠️ Network error processing payment. Check console for details.');
      }
  });
}

  // NEW: Auto-format reference number inputs
  if (referenceInput) {
    referenceInput.addEventListener('input', function(e) {
      // Convert to uppercase and remove special characters
      this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    });

    // Allow Enter key to submit
    referenceInput.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        if (successfulPaymentLink) successfulPaymentLink.click();
      }
    });
  }

  if (qrphReferenceInput) {
    qrphReferenceInput.addEventListener('input', function(e) {
      // Convert to uppercase and remove special characters
      this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    });

    // Allow Enter key to submit
    qrphReferenceInput.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        if (qrphSuccessfulPaymentLink) qrphSuccessfulPaymentLink.click();
      }
    });
  }
};

// Also initialize on DOMContentLoaded for faster binding (in case users click before images load)
if (document.readyState === 'interactive' || document.readyState === 'complete') {
  try { if (typeof window.onload === 'function') window.onload(); } catch (e) { console.error(e); }
} else {
  document.addEventListener('DOMContentLoaded', function() {
    try { if (typeof window.onload === 'function') window.onload(); } catch (e) { console.error(e); }
  });
}

// Utilities: Inline receipt preview and printing
function showReceipt(orderId) {
  let receiptSection = document.getElementById('receiptSection');
  let receiptFrame = document.getElementById('receiptFrame');
  let printBtn = document.getElementById('printReceiptBtn');
  let closeBtn = document.getElementById('closeReceiptBtn');

  // If not present in DOM, create it dynamically
  if (!receiptSection || !receiptFrame) {
    const overlay = document.createElement('div');
    overlay.id = 'receiptSection';
    overlay.style.cssText = 'display:none; position:fixed; top:0;left:0;right:0;bottom:0; background:rgba(0,0,0,0.6); z-index:2147483647; align-items:center; justify-content:center;';

    const panel = document.createElement('div');
    panel.style.cssText = 'background:#fff; width:90%; max-width:900px; height:90%; border-radius:8px; box-shadow:0 10px 30px rgba(0,0,0,0.3); display:flex; flex-direction:column;';
    const bar = document.createElement('div');
    bar.style.cssText = 'padding:10px 14px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #eee;';
    const h = document.createElement('h3');
    h.textContent = 'Receipt Preview';
    h.style.margin = '0';
    h.style.fontFamily = 'Arial, sans-serif';

    printBtn = document.createElement('button');
    printBtn.id = 'printReceiptBtn';
    printBtn.textContent = 'Print';
    printBtn.style.cssText = 'margin-right:8px; padding:8px 12px; background:#2b7cff; color:#fff; border:none; border-radius:4px; cursor:pointer;';
    closeBtn = document.createElement('button');
    closeBtn.id = 'closeReceiptBtn';
    closeBtn.textContent = 'Close';
    closeBtn.style.cssText = 'padding:8px 12px; background:#888; color:#fff; border:none; border-radius:4px; cursor:pointer;';
    const btnWrap = document.createElement('div');
    btnWrap.appendChild(printBtn);
    btnWrap.appendChild(closeBtn);

    bar.appendChild(h);
    bar.appendChild(btnWrap);

    const body = document.createElement('div');
    body.style.cssText = 'flex:1;';
    receiptFrame = document.createElement('iframe');
    receiptFrame.id = 'receiptFrame';
    receiptFrame.title = 'Receipt';
    receiptFrame.style.cssText = 'width:100%; height:100%; border:0;';
    receiptFrame.src = 'about:blank';
    body.appendChild(receiptFrame);

    panel.appendChild(bar);
    panel.appendChild(body);
    overlay.appendChild(panel);
    document.body.appendChild(overlay);

    receiptSection = overlay; // rebind
  }

  console.log('[Receipt] Showing receipt for order', orderId);

  const url = '../backend/generate_receipt.php?order_id=' + encodeURIComponent(orderId) + '&stream=1';
  receiptFrame.src = url;
  receiptSection.style.display = 'flex';

  // Attach button handlers (idempotent)
  if (printBtn && !printBtn.dataset.bound) {
    printBtn.addEventListener('click', function() {
      // Try printing the iframe; if blocked, open in new tab
      try {
        const f = document.getElementById('receiptFrame');
        if (f && f.contentWindow) {
          f.contentWindow.focus();
          f.contentWindow.print();
        } else {
          window.open(url, '_blank');
        }
      } catch (e) {
        window.open(url, '_blank');
      }
    });
    printBtn.dataset.bound = '1';
  }
  if (closeBtn && !closeBtn.dataset.bound) {
    closeBtn.addEventListener('click', function() {
      receiptSection.style.display = 'none';
    });
    closeBtn.dataset.bound = '1';
  }

  // Scroll into view (best effort)
  try { receiptSection.scrollIntoView({ behavior: 'smooth' }); } catch (e) {}
}