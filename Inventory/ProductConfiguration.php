<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header("Location: ../LogIn/Users/User.php");
    exit();
}
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Product Configuration</title>
    <link rel="stylesheet" href="../HomePage/MainHome.css">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="ProductConfiguration.css">
  </head>
  <body>
    <?php include '../include/navbar.php'; ?>

    <div class="right-Menu">
        <div class="content-wrapper" style="background: transparent; box-shadow: none;">
            <div class="content-header">
                <h1>Product Configuration</h1>
                <div class="inventory-selector">
                    <label for="modeSelect">Select Configuration Type:</label>
                    <select id="modeSelect">
                        <option value="products">Add Product</option>
                        <option value="recipes">Recipe Editor</option>
                    </select>
                </div>
            </div>

        <!-- Products Section -->
        <div id="productsSection" class="section">
          <div class="content-header">
            <h2>Products</h2>
            <div class="header-actions">
              <input id="searchInput" placeholder="Search products..." />
              <select id="categoryFilter">
                <option value="">All Categories</option>
                <option value="espresso">Espresso</option>
                <option value="pastries">Pastries</option>
                <option value="meals">Meals</option>
                <option value="signature">Signature</option>
              </select>
              <button id="btnAddProduct" class="btn btn-primary">Add Product</button>
            </div>
          </div>

          <div class="recipe-area">
            <div class="table-container">
              <table id="productsTable">
                <thead><tr><th>Image</th><th>ID</th><th>Name</th><th>Category</th><th>Selling Price</th><th>Actions</th></tr></thead>
                <tbody></tbody>
              </table>
            </div>
            <div class="table-footer">
              <button id="prevPage" class="btn">Prev</button>
              <span id="pageInfo"></span>
              <button id="nextPage" class="btn">Next</button>
            </div>
          </div>

          <!-- Product Modal -->
          <div id="productModal" class="modal" aria-hidden="true">
            <div class="modal-dialog">
              <form id="productForm" class="modal-form">
                <h2 id="modalTitle">Add Product</h2>
                <input type="hidden" id="productId" />
                <label>Name <input id="productName" required /></label>
                <label>Category
                  <select id="productCategory" required>
                    <option value="espresso">Espresso</option>
                    <option value="pastries">Pastries</option>
                    <option value="meals">Meals</option>
                    <option value="signature">Signature</option>
                    <option value="espresso">Espresso</option>
                    <option value="signature">Signature</option>
                  </select>
                </label>
                <label>Selling Price <input id="productPrice" type="number" step="0.01" required /></label>
                <label>Cost Price <input id="productCostPrice" type="number" step="0.01" required value="0.00" /></label>
                <label>Image <input id="productImage" type="file" accept="image/*" /></label>
                <div class="modal-actions">
                  <button type="button" class="btn btn-secondary" id="cancelProduct">Cancel</button>
                  <button type="submit" class="btn btn-primary" id="saveProduct">Save</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Recipes Section -->
        <div id="recipesSection" class="section" style="display: none;">
          <div class="content-header">
            <h2>Recipe Editor</h2>
          </div>

          <div class="controls">
            <div class="control-group">
              <label for="menuSelect">Select Menu Item</label>
              <select id="menuSelect">
                <option value="">-- Select Menu --</option>
              </select>
            </div>
            <div class="control-buttons">
              <button id="loadRecipeBtn" class="btn secondary">Load Recipe</button>
              <button id="newRecipeBtn" class="btn">New Recipe</button>
            </div>
          </div>

          <div class="recipe-area">
            <div class="table-container">
              <table id="recipeTable">
                <thead>
                  <tr>
                    <th>Ingredient</th>
                    <th>Quantity Required</th>
                    <th>Unit</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- rows inserted dynamically -->
                </tbody>
              </table>
            </div>

            <div class="recipe-actions">
              <button id="addRowBtn" class="btn">Add Ingredient</button>
              <button id="saveRecipeBtn" class="btn primary">Save Recipe</button>
              <button id="clearRecipeBtn" class="btn secondary">Clear All</button>
            </div>

            <button id="scrollToTopBtn" class="scroll-to-top-btn" title="Scroll to Top">
              <i class="fas fa-arrow-up"></i>
            </button>
            <button id="scrollToBottomBtn" class="scroll-to-bottom-btn" title="Scroll to Bottom">
              <i class="fas fa-arrow-down"></i>
            </button>
          </div>
        </div>

      </div>
    </div>

    <script src="ProductConfiguration.js"></script>
  </body>
</html>
