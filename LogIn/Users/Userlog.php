<?php
// Registration page intentionally disabled/removed.
// Keep a lightweight placeholder to avoid 404s and guide users back to login.
http_response_code(410); // Gone
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration Disabled</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;background:#f6f7f9;color:#222;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
        .box{max-width:560px;background:#fff;padding:28px;border-radius:8px;box-shadow:0 6px 24px rgba(15,23,42,0.08);text-align:center}
        a.btn{display:inline-block;margin-top:18px;padding:10px 16px;background:#2b7cff;color:#fff;text-decoration:none;border-radius:6px}
    </style>
</head>
<body>
    <div class="box">
        <h1>Registration Disabled</h1>
        <p>Public registration has been turned off. Accounts are created by administrators only.</p>
        <p>Please contact your administrator to request access.</p>
        <a class="btn" href="user.php">Return to Login</a>
    </div>
</body>
</html>
