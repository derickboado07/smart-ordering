<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Inventory Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../HomePage/MainHome.css" />
    <link rel="stylesheet" href="Inventory.css" />
</head>
<body>
<?php include '../include/navbar.php'; ?>

    <div class="right-Menu">
        <div class="content-wrapper" style="background: transparent; box-shadow: none;">
            <div class="content-header">
                <h1>Inventory Management</h1>
                <div class="inventory-selector">
                    <label for="inventoryType">Select Inventory Type:</label>
                    <select id="inventoryType">
                        <option value="external">External</option>
                        <option value="internal">Internal</option>
                    </select>
                </div>
            </div>

            <!-- External Inventory Section -->
            <div id="externalInventory" class="inventory-section">
                <div class="controls-section">
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add New Item
                    </button>
                    <div class="search-container">
                        <input type="text" id="searchInput" placeholder="Search items..." onkeyup="filterTable()">
                        <i class="fas fa-search"></i>
                    </div>
                </div>

                <div class="table-container">
                    <table id="inventoryTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Menu Item</th>
                                <th>Category</th>
                                <th>Selling Price</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="inventoryTableBody">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>

                <div id="loadingSpinner" class="loading-spinner" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading...</p>
                </div>
            </div>

            <!-- Internal Inventory Section -->
            <div id="internalInventory" class="inventory-section" style="display: none;">
                <div class="header-actions">
                    <button class="btn btn-primary" id="btnAddIngredient">Add Ingredient</button>
                </div>

                <div class="table-card">
                    <div class="table-container">
                        <table id="ingredientsTable">
                            <thead><tr><th>ID</th><th>Name</th><th>Unit</th><th>Current Price</th><th>Current Stock</th><th>Status</th><th>Last Update</th><th>Actions</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals for External Inventory -->
        <div id="inventoryModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle">Add New Inventory Item</h2>
                    <span class="close" onclick="closeModal()">&times;</span>
                </div>
                <form id="inventoryForm">
                    <input type="hidden" id="itemId" name="id">
                    <div class="form-group">
                        <label for="menuSelectDropdown">Select Menu Item:</label>
                        <select id="menuSelectDropdown" name="menuSelectDropdown">
                            <option value="">Choose a menu item...</option>
                        </select>
                        <input type="text" id="menuSelectReadonly" readonly style="display:none; margin-top:8px;" />
                        <div id="menuCostDisplay" style="display:none; margin-top:6px; color:#666; font-size:0.95rem;">Cost Price: ₱<span id="menuCostValue">0.00</span></div>
                        <input type="hidden" id="menuId" name="menu_id" />
                    </div>
                    <div class="form-group">
                        <label for="stockQuantity">Stock Quantity:</label>
                        <input type="number" id="stockQuantity" name="stock_quantity" min="0" required>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <span id="submitText">Add Item</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="quickUpdateModal" class="modal">
            <div class="modal-content small">
                <div class="modal-header">
                    <h2>Quick Stock Update</h2>
                    <span class="close" onclick="closeQuickUpdateModal()">&times;</span>
                </div>
                <div class="quick-update-content">
                    <p id="quickUpdateItemName"></p>
                    <p>Current Stock: <span id="currentStock"></span></p>
                    <div class="stock-actions">
                        <div class="stock-input-group">
                            <label>Add Stock:</label>
                            <div class="input-with-btn">
                                <input type="number" id="addStockAmount" min="1" value="1">
                                <button class="btn btn-success" onclick="updateStock('add')">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </div>
                        </div>
                        <div class="stock-input-group">
                            <label>Remove Stock:</label>
                            <div class="input-with-btn">
                                <input type="number" id="removeStockAmount" min="1" value="1">
                                <button class="btn btn-warning" onclick="updateStock('remove')">
                                    <i class="fas fa-minus"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="confirmModal" class="modal">
            <div class="modal-content small">
                <div class="modal-header">
                    <h2>Confirm Action</h2>
                </div>
                <div class="confirm-content">
                    <p id="confirmMessage"></p>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmBtn">Confirm</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals for Internal Inventory -->
        <div id="ingredientModal" class="modal" aria-hidden="true">
            <div class="modal-dialog">
                <form id="ingredientForm" class="modal-form">
                    <h2 id="modalTitleIngredient">Add Ingredient</h2>
                    <input type="hidden" id="ingredientId" />
                    <label>Name
                        <input id="ingredientName" name="name" type="text" required />
                    </label>
                    <label>Unit
                        <select id="ingredientUnit" name="unit" required>
                            <option value="ml">ml</option>
                            <option value="pcs">pcs</option>
                            <option value="g">g</option>
                        </select>
                    </label>
                    <label>Stock Quantity
                        <input id="ingredientStock" name="stock_quantity" type="number" step="0.01" min="0" required />
                    </label>
                    <label>Price
                        <input id="ingredientPrice" name="price" type="number" step="0.01" min="0" required />
                    </label>
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-primary" id="saveIngredient">Save</button>
                        <button type="button" class="btn btn-secondary" id="cancelIngredient" onclick="closeIngredientModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Notification Toast -->
        <div id="toast" class="toast">
            <div class="toast-content">
                <i class="toast-icon"></i>
                <span class="toast-message"></span>
            </div>
            <button class="toast-close" onclick="closeToast()">&times;</button>
        </div>
    </div>

    <script src="Inventory.js"></script>
</body>
</html>
