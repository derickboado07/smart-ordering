<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
.left-navbar {
  width: 120px;
  background-color: black;
  color: white;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding-top: 100px;
  position: fixed;
  height: 100vh;
  box-shadow: 2px 0 5px rgba(0,0,0,0.1);
  z-index: 1000;
}

.left-navbar img {
  position: absolute;
  top: 10px;
  width: 70px;
  height: 70px;
  border-radius: 50%;
  object-fit: cover;
}

.icon, .icons {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: 30px;
  text-decoration: none;
  color: white;
  padding: 10px;
  border-radius: 8px;
  transition: all 0.3s ease;
  width: 80px;
  background-color: transparent;
}

.icon i, .icons i {
  font-size: 24px;
  color: #b87333;
  margin-bottom: 5px;
}

.icon h6, .icons h6 {
  margin: 0;
  font-size: 14px;
  font-weight: 500;
}

/* Hover effect */
.icon:hover, .icons:hover {
  background-color: rgba(184, 115, 51, 0.1);
  transform: translateY(-2px);
}

.icon:hover i, .icons:hover i {
  color: #6ce5e8;
}

.icon:hover h6, .icons:hover h6 {
  color: #6ce5e8;
}

/* Active state for current page */
.icon.active, .icons.active {
  background-color: rgba(108, 229, 232, 0.1);
  border-left: 3px solid #6ce5e8;
}

.icon.active i, .icons.active i {
  color: #6ce5e8;
}

.icon.active h6, .icons.active h6 {
  color: #6ce5e8;
}

.fade-in-icon {
  animation: fadeIn 0.5s ease-out forwards;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* Chrome-specific fix for red background on icons */
@supports (-webkit-appearance: none) {
  .icon, .icons {
    background: none !important;
    background-color: transparent !important;
  }
  .icon i, .icons i {
    background: none !important;
    background-color: transparent !important;
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .left-navbar {
    width: 60px;
    padding-top: 80px;
  }

  .left-navbar img {
    width: 50px;
    height: 50px;
  }

  .icon, .icons {
    width: 50px;
    padding: 8px;
  }

  .icon i, .icons i {
    font-size: 18px;
  }

  .icon h6, .icons h6 {
    font-size: 10px;
  }
}

@media (max-width: 480px) {
  .left-navbar {
    display: none; /* Hide navbar on very small screens */
  }
}
</style>

<div class="left-navbar">
    <img src="../../Images/Icon.png" alt="Arat Coffee Logo" />

    <!-- Admin-specific menu items -->
    <a href="/smart-ordering/LogIn/Admin/Admin.php" class="icon active">
      <i class="fa-solid fa-tachometer-alt"></i>
      <h6>Dashboard</h6>
    </a>
    <a href="/smart-ordering/LogIn/Admin/edit_user.php" class="icon">
      <i class="fa-solid fa-user-edit"></i>
      <h6>Edit User</h6>
    </a>
    <a href="/smart-ordering/LogIn/Admin/edit_admin.php" class="icon">
      <i class="fa-solid fa-user-shield"></i>
      <h6>Edit Admin</h6>
    </a>
    <a href="/smart-ordering/Inventory/ProductConfiguration.php" class="icon">
      <i class="fa-solid fa-cogs"></i>
      <h6>Customization</h6>
    </a>
    <a href="/smart-ordering/Inventory/Inventory.php" class="icon">
      <i class="fa-solid fa-warehouse"></i>
      <h6>Inventory</h6>
    </a>
    <a href="/smart-ordering/SalesDashboard/SalesReportPage.php" class="icon">
      <i class="fa-solid fa-chart-line"></i>
      <h6>Sales</h6>
    </a>
    <a href="/smart-ordering/LogIn/Admin/admin_logout.php" class="icons">
      <i class="fa-solid fa-arrow-right-from-bracket"></i>
      <h6>Sign-Out</h6>
    </a>
  </div>
