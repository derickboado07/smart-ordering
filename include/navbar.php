<link rel="stylesheet" href="../include/navbar.css" />

<?php
// Check user type and display appropriate navbar
$isAdmin = isset($_SESSION['admin_username']);
?>

<div class="left-navbar">
    <img src="../Images/Icon.png" alt="Logo" />

    <?php if ($isAdmin): ?>
        <!-- Admin Navbar: Show all links -->
        <a href="../HomePage/MainHome.php" class="icon">
          <i class="fa-solid fa-house"></i>
          <h6>Home</h6>
        </a>
        <a href="../CoffeeMenu/HomeMenu.php" class="icon">
          <i class="fa-solid fa-mug-saucer"></i>
          <h6>Menu</h6>
        </a>
        <a href="http://localhost/smart-ordering/Inventory/ProductConfiguration.php" class="icon">
          <i class="fa-solid fa-cogs"></i>
          <h6>Customization</h6>
        </a>
        <a href="../List-Orders/Orderlist.php" class="icon">
          <i class="fa-solid fa-list"></i>
          <h6>Orders</h6>
        </a>
        <a href="../Inventory/Inventory.php" class="icon">
          <i class="fa-solid fa-warehouse"></i>
          <h6>Inventory</h6>
        </a>
        <a href="../SalesDashboard/SalesReportPage.php" class="icon">
          <i class="fa-solid fa-wallet"></i><h6>Sales</h6>
        </a>
    <?php else: ?>
        <!-- User Navbar: Show limited links -->
        <a href="../HomePage/MainHome.php" class="icon">
          <i class="fa-solid fa-house"></i>
          <h6>Home</h6>
        </a>
        <a href="../CoffeeMenu/HomeMenu.php" class="icon">
          <i class="fa-solid fa-mug-saucer"></i>
          <h6>Menu</h6>
        </a>
        <a href="../List-Orders/Orderlist.php" class="icon">
          <i class="fa-solid fa-list"></i>
          <h6>Orders</h6>
        </a>
    <?php endif; ?>

    <a href="../LogIn/login_logout_back/logout.php" class="icons" id="signOutBtn">
      <i class="fa-solid fa-arrow-right-from-bracket"></i>
      <h6>Sign-Out</h6>
    </a>
  </div>
