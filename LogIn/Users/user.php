<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="design.css" />
  <link rel="icon" type="image/png" href="images/Icon.png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Dancing+Script:wght@700&family=Montserrat:wght@300;600&family=Playfair+Display:wght@700&family=Poiret+One&display=swap" rel="stylesheet" />
  <title>ARAT KAPE</title>
  <style>
    body {
        background-image: url('../Images/BG.jpg');
        background-repeat: no-repeat;
        background-size: cover;
        background-position: center;
        height: 100vh;
        margin: 0;
        padding: 0;
    }
    /* Error message style */
    .error-message {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #f44336;
        color: white;
        padding: 12px 25px;
        border-radius: 5px;
        font-family: 'Montserrat', sans-serif;
        font-size: 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
        z-index: 9999;
        animation: fadeInDown 0.4s ease;
        opacity: 1; /* ensure visible initially */
    }
    .error-message i {
        font-size: 20px;
    }
    @keyframes fadeInDown {
        from { opacity: 0; transform: translate(-50%, -20px); }
        to { opacity: 1; transform: translate(-50%, 0); }
    }
  </style>
</head>
<body>

<?php
// Display error message if set
if(isset($_SESSION['error'])): ?>
    <div id="errorPopup" class="error-message">
        <i class="fas fa-exclamation-triangle"></i>
        <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']); // remove after displaying
        ?>
    </div>
<?php endif; ?>

<!-- image and logo part -->
<div class="quotes">
    <img src="../images/Main-icon.png" />
    <p>Always Rise Above Trials</p>
</div>

<div class="overlay"></div>

<div class="login-container">
    <img src="../images/Main-icon.png" alt="ARAT Logo" class="logo" />
    
    <!-- login process -->
    <form id="login-form" action="../login_logout_back/login_process.php" method="POST">
        <div class="input-group">
            <label for="username">Username</label>
            <div class="input-row">
                <div class="icon-box"><i class="fas fa-user"></i></div>
                <input type="text" id="username" name="username" placeholder="Enter your username" maxlength="16" required />
            </div>
        </div>
        <div class="input-group">
            <label for="password">Password</label>
            <div class="input-row">
                <div class="icon-box"><i class="fas fa-unlock"></i></div>
                <input type="password" id="password" name="password" placeholder="Enter your password" maxlength="50" required />
                <div id="strengthMessage" style="margin-top:5px;font-size:14px;"></div>
            </div>
        </div>

        <!-- forget and register part -->
        <div class="register-link">
            Don't have an account? Please contact your administrator to request an account.
        </div>
        <div class="forgot-link">
            Forgot your password?
            <a href="../forgot_password/forgot.php">Click Here</a>
        </div>

        <button type="submit" class="login-button" name="login">Login</button>
    </form>
</div>

<script src="script.js"></script>

<script>
// Auto-hide error message after 4 seconds
window.addEventListener('DOMContentLoaded', () => {
    const errorDiv = document.getElementById('errorPopup');
    if (errorDiv) {
        setTimeout(() => {
            errorDiv.style.transition = "opacity 0.5s ease";
            errorDiv.style.opacity = 0;
            // remove from DOM after fade
            setTimeout(() => errorDiv.remove(), 500);
        }, 4000);
    }
});
</script>

</body>
</html>
