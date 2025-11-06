<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>User Registration</title>
    <link rel="stylesheet" href="userdesign.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<style>
body {
    margin: 0;
    padding: 0;
    min-height: 100vh;
    display: flex;
    overflow: hidden;
    opacity: 0;
    transition: opacity 0.8s ease-in-out;
}

body.loaded {
    opacity: 1;
}
        
.left-half {
    flex: 4;
    background-image: url('../images/BG.jpg');
    background-size: cover;
    position: relative;
    z-index: 1;
}
</style>
<body>  
    <div class="left-half">
        <div class="logo">
            <img src="../Images/Icon.png" alt="Logo" />
            <h1>User Registration</h1>
            <p>This user registration form is intended for newly hired employees of Arat Kape MCU. 
               It is part of the official process for granting access to the company’s web-based system.</p>
        </div>
        <div class="out-container">
            <a href="user.php" class="inner-container">
                <h1 class="back-text">GO BACK</h1>
                <i class="fa-solid fa-arrow-left back-icon"></i>
            </a>
        </div>
    </div>

    <div class="right-half">
        <div class="form-container">
            <h2>Registration Disabled</h2>
            <p>Public registration has been disabled. Accounts are now created by administrators only.</p>
            <p>Please contact your administrator to request an account.</p>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
