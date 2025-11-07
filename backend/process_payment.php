<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

include 'db_connect.php';

// Get the raw POST data
$input = file_get_contents("php://input");
error_log("=== PAYMENT DEBUG ===");
error_log("Raw input received: " . $input);

// Log session data for debugging
error_log("Session data at payment: " . print_r($_SESSION, true));

$data = json_decode($input, true);

if (!$data) {
    error_log("JSON decode failed");
    echo json_encode(["status" => "error", "message" => "Invalid data received"]);
    exit;
}

error_log("Decoded payment data: " . print_r($data, true));

// FIXED: Try multiple sources for order_id
$order_id = $data['order_id'] ?? null;
// Fallback: if only order_ids array was sent, use the first element
if (!$order_id && isset($data['order_ids']) && is_array($data['order_ids']) && count($data['order_ids']) > 0) {
    $order_id = intval($data['order_ids'][0]);
}
// Fallback to session if still not provided
if (!$order_id) {
    $order_id = $_SESSION['pending_order_id'] ?? null;
}
$payment_method = $data['payment_method'] ?? 'cash';
$discount_type = $data['discount_type'] ?? 'none';
$discount_amount = floatval($data['discount_amount'] ?? 0);
$final_total = floatval($data['final_total'] ?? 0);
$cash_amount = floatval($data['cash_amount'] ?? 0);
$change_amount = floatval($data['change_amount'] ?? 0);
$reference_number = $data['reference_number'] ?? null;

error_log("Order ID from various sources:");
error_log(" - From POST data: " . ($data['order_id'] ?? 'NOT SET'));
error_log(" - From Session: " . ($_SESSION['pending_order_id'] ?? 'NOT SET'));
error_log(" - Final Order ID: " . $order_id);

if (!$order_id) {
    error_log("❌ No order ID found in any source");
    echo json_encode([
        "status" => "error", 
        "message" => "No order ID provided. Please start over.",
        "debug" => [
            "post_data" => $data,
            "session_data" => $_SESSION
        ]
    ]);
    exit;
}

// Validate reference number for GCash
if ($payment_method === 'gcash' && empty($reference_number)) {
    error_log("GCash payment missing reference number");
    echo json_encode(["status" => "error", "message" => "GCash reference number is required"]);
    exit;
}

try {
    // Check if order exists
    $check_sql = "SELECT id, staff_name, payment_status FROM orders WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    if (!$check_stmt) {
        throw new Exception("Check prepare failed: " . $conn->error);
    }
    
    $check_stmt->bind_param("i", $order_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $order = $result->fetch_assoc();
    $check_stmt->close();
    
    if (!$order) {
        throw new Exception("Order ID $order_id not found in database");
    }
    
    error_log("Found order in DB: " . print_r($order, true));

    // Store standardized payment method
    $payment_method_display = $payment_method;

    // Update order with payment information
    $update_sql = "UPDATE orders SET
        payment_method = ?,
        discount_type = ?,
        discount_amount = ?,
        total_amount = ?,
        cash_amount = ?,
        change_amount = ?,
        payment_status = 'paid'
        WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_sql);
    
    if (!$update_stmt) {
        throw new Exception("Update prepare failed: " . $conn->error);
    }
    
    $update_stmt->bind_param("ssdddii", 
        $payment_method_display,
        $discount_type, 
        $discount_amount, 
        $final_total, 
        $cash_amount, 
        $change_amount, 
        $order_id
    );
    
    if ($update_stmt->execute()) {
        error_log("✅ Successfully updated order ID: " . $order_id);
        
        // Clear the session after successful payment
        unset($_SESSION['pending_order_id']);
        unset($_SESSION['order_total']);
        // After successful payment, deduct inventory for the order if not already deducted
        try {
            // Ensure the orders table has an inventory_deducted flag
            $dbNameResult = $conn->query("SELECT DATABASE() AS dbname");
            $dbName = $dbNameResult->fetch_assoc()['dbname'];
            $colCheckSql = "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'inventory_deducted'";
            $colCheckStmt = $conn->prepare($colCheckSql);
            $colCheckStmt->bind_param("s", $dbName);
            $colCheckStmt->execute();
            $colCheckRes = $colCheckStmt->get_result()->fetch_assoc();
            $colCheckStmt->close();

            if (intval($colCheckRes['cnt']) === 0) {
                // Add the column
                $conn->query("ALTER TABLE orders ADD COLUMN inventory_deducted TINYINT(1) NOT NULL DEFAULT 0");
                error_log("Added inventory_deducted column to orders table");
            }

            // Check if inventory already deducted for this order
            $dedCheckStmt = $conn->prepare("SELECT inventory_deducted FROM orders WHERE id = ? LIMIT 1");
            $dedCheckStmt->bind_param("i", $order_id);
            $dedCheckStmt->execute();
            $dedRow = $dedCheckStmt->get_result()->fetch_assoc();
            $dedCheckStmt->close();

            if (empty($dedRow) || intval($dedRow['inventory_deducted']) === 1) {
                error_log("Inventory already deducted or order not found for order_id={$order_id}");
            } else {
                // Fetch order items. If order_items has menu_id column use it, otherwise fallback to name lookup.
                $itemsStmt = $conn->prepare("SELECT COALESCE(menu_id, NULL) AS menu_id, item_name, quantity FROM order_items WHERE order_id = ?");
                $itemsStmt->bind_param("i", $order_id);
                $itemsStmt->execute();
                $itemsResult = $itemsStmt->get_result();
                // Begin a transaction to make deductions atomic
                $conn->begin_transaction();
                try {
                    // Prepare statements
                    $menuLookup = $conn->prepare("SELECT id FROM menu WHERE LOWER(name) = LOWER(?) LIMIT 1");
                    $invUpdate = $conn->prepare("UPDATE menu_inventory SET stock_quantity = GREATEST(stock_quantity - ?, 0), last_updated = CURRENT_TIMESTAMP WHERE menu_id = ?");

                    // ingredient update and safe insert-if-missing (only insert if not exists)
                    $ingredientUpdate = $conn->prepare("UPDATE ingredients SET stock_quantity = GREATEST(stock_quantity - ?, 0), last_updated = CURRENT_TIMESTAMP WHERE id = ?");
                    $ingredientInsert = $conn->prepare("INSERT INTO ingredients (id, name, unit, stock_quantity) SELECT ?, 'Unknown', 'unit', 0 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ingredients WHERE id = ?)");

                    $recipeStmt = $conn->prepare("SELECT ingredient_id, quantity_required FROM menu_recipes WHERE menu_id = ?");

                    while ($row = $itemsResult->fetch_assoc()) {
                        $iqty = floatval($row['quantity']);

                        // prefer explicit menu_id if present
                        $mid = intval($row['menu_id'] ?? 0);
                        if ($mid <= 0) {
                            // fallback to lookup by name
                            $iname = $row['item_name'];
                            $menuLookup->bind_param("s", $iname);
                            $menuLookup->execute();
                            $mres = $menuLookup->get_result();
                            if ($mrow = $mres->fetch_assoc()) {
                                $mid = intval($mrow['id']);
                            } else {
                                error_log("Menu lookup: no menu entry found for '{$iname}' during payment deduction");
                                continue;
                            }
                        }

                        // Deduct menu-level inventory (if exists)
                        $invUpdate->bind_param("di", $iqty, $mid);
                        $invUpdate->execute();
                        if ($invUpdate->affected_rows === 0) {
                            // No menu_inventory row exists for this menu_id; do not create one automatically.
                            // This prevents orders from populating the External Inventory list inadvertently.
                            error_log("No menu_inventory row for menu_id={$mid}; skipping menu-level deduction for order {$order_id}");
                        } else {
                            error_log("Deducted {$iqty} units from menu_id={$mid} for order {$order_id}");
                        }

                        // Deduct ingredients per recipe
                        $recipeStmt->bind_param('i', $mid);
                        $recipeStmt->execute();
                        $rres = $recipeStmt->get_result();
                        while ($rrow = $rres->fetch_assoc()) {
                            $ingId = intval($rrow['ingredient_id']);
                            $reqQty = floatval($rrow['quantity_required']);
                            $totalDeduct = $reqQty * $iqty; // per menu qty

                            $ingredientUpdate->bind_param('di', $totalDeduct, $ingId);
                            $ingredientUpdate->execute();
                            if ($ingredientUpdate->affected_rows === 0) {
                                // insert placeholder ingredient row if missing
                                $ingredientInsert->bind_param('ii', $ingId, $ingId);
                                $ingredientInsert->execute();
                                error_log("Inserted missing ingredient id={$ingId} while deducting for menu_id={$mid}");
                            } else {
                                error_log("Deducted {$totalDeduct} units from ingredient_id={$ingId} for order {$order_id}");
                            }
                        }
                    }

                    // Mark as deducted and commit
                    $markStmt = $conn->prepare("UPDATE orders SET inventory_deducted = 1 WHERE id = ?");
                    $markStmt->bind_param("i", $order_id);
                    $markStmt->execute();
                    $markStmt->close();

                    $conn->commit();

                    // close prepared statements
                    $menuLookup->close();
                    $invUpdate->close();
                    // Note: we intentionally do not create missing menu_inventory rows here.
                    $ingredientUpdate->close();
                    $ingredientInsert->close();
                    $recipeStmt->close();
                } catch (Exception $e) {
                    $conn->rollback();
                    error_log('Ingredient/menu deduction transaction failed: '.$e->getMessage());
                }

                $itemsStmt->close();
            }
        } catch (Exception $invExc) {
            error_log("Error during inventory deduction on payment: " . $invExc->getMessage());
        }

        echo json_encode([
            "status" => "success", 
            "message" => "Payment processed successfully",
            "order_id" => $order_id,
            "reference_number" => $reference_number
        ]);
    } else {
        throw new Exception("Failed to update order: " . $update_stmt->error);
    }
    
    $update_stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log("Exception in payment processing: " . $e->getMessage());
    echo json_encode([
        "status" => "error", 
        "message" => "Database error: " . $e->getMessage(),
        "debug_info" => [
            "order_id" => $order_id,
            "input_data" => $data
        ]
    ]);
}
?>