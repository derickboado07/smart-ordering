<?php
session_start();
include '../connectdb/connect.php';

// Only allow a logged-in user who must change password
if (!isset($_SESSION['user_id'])) {
    header('Location: User.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if ($new === '' || $confirm === '') {
        $error = 'Please fill both fields.';
    } elseif ($new !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Hash and update
        $hash = password_hash($new, PASSWORD_DEFAULT);
        if ($upd = $conn->prepare("UPDATE users SET password = ?, must_change_password = 0 WHERE user_id = ?")) {
            $upd->bind_param('si', $hash, $user_id);
            if ($upd->execute()) {
                $upd->close();
                // clear must_change flag in session
                unset($_SESSION['must_change_password']);
                header('Location: ../../HomePage/MainHome.php');
                exit();
            } else {
                $error = 'Failed to update password. Please try again.';
            }
        } else {
            $error = 'Server error.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Change Password</title>
    <style>body{font-family: Arial, sans-serif; padding:30px;} .box{max-width:420px;margin:40px auto;padding:20px;border:1px solid #ddd;border-radius:6px;} input{width:100%;padding:8px;margin:8px 0;} .err{color:#c00;margin:8px 0;} </style>
</head>
<body>
    <div class="box">
        <h2>Change Password</h2>
        <p>Please change your temporary password to continue.</p>
        <?php if (!empty($error)): ?>
            <div class="err"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <label>New Password</label>
            <input type="password" name="new_password" required minlength="6" />
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required minlength="6" />
            <button type="submit" style="padding:8px 12px;">Change Password</button>
        </form>
    </div>
</body>
</html>
