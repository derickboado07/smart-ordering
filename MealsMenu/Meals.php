<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../LogIn/Users/user.php");
    exit();
}

include '../backend/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Meals Menu</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="../Shared/Menu.css" />
</head>
<body class="meals-page">

<?php include '../include/navbar.php'; ?>

<nav class="top-category-links">
  <a href="../CoffeeMenu/HomeMenu.php">Coffee</a>
  <a href="../PastriesMenu/PastriesMenu.php">Pastries</a>
  <a href="../MealsMenu/Meals.php">Meals</a>
</nav>

<main class="right-Menu">
  <header class="meals-header"><span>MEALS</span></header>

  <!-- Dishes -->
  <section id="espresso">
    <h2 class="meals-type-title">Dishes</h2>
    <div class="meals-grid">
      <?php
      // UPDATED: Select id from menu table and filter by meals category
      $sql = "SELECT id, name, price, image FROM menu WHERE category = 'meals' AND status = 'active' ORDER BY name";
      if ($res = mysqli_query($conn, $sql)) {
        if (mysqli_num_rows($res) > 0) {
          while ($row = mysqli_fetch_assoc($res)) {
            $id = htmlspecialchars($row['id'], ENT_QUOTES); // NEW: Get menu id
            $name = htmlspecialchars($row['name'], ENT_QUOTES);
            $price = htmlspecialchars($row['price'], ENT_QUOTES);
            $img = htmlspecialchars("../Images/" . $row['image'], ENT_QUOTES);
            echo "<div class='Option' data-id='{$id}' data-name='{$name}' data-price='{$price}' data-image='{$img}'>
                    <img src='{$img}' alt='{$name}'>
                    <div class='meals-overlay'>{$name}</div>
                  </div>";
          }
        } else {
          echo "<p>No dishes available.</p>";
        }
      } else {
        echo "<p>Error loading dishes.</p>";
      }
      ?>
    </div>
  </section>
</main>

<div class="order-panel" id="orderPanel">
  <div class="order-header">
    <img src="../Images/Icon.png" alt="AratCoffee Logo" class="order-logo">
    <h2>AratCoffee <span style="color: #6d3b2d;">Order</span></h2>
    <button class="close-order" id="closeOrder">&times;</button>
  </div>

  <div class="order-content">
    <div id="orderItemsContainer">
      <div class="empty-order" id="emptyOrderMessage">
        <i class="fas fa-bowl-food" style="font-size: 40px; margin-bottom: 10px;"></i>
        <p>Your order is empty</p>
        <p>Select items from the menu to get started</p>
      </div>
    </div>
    <div class="final-total">
      TOTAL = ₱<span id="orderTotal">0.00</span>
    </div>
    <button class="confirm-btn" type="button">CONFIRM ORDER</button>
  </div>
</div>

<div class="overlay" id="overlay"></div>

<script src="../Shared/menushared.js"></script>
</body>
</html>
