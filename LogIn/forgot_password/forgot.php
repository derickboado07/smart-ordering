<?php
/**
 * Combined Change Password & Forgot Password page
 * - If user is logged in (session user_id or admin_id) -> show Change Password form
 * - If not logged in -> allow requesting OTP, verify OTP, and set new password
 *
 * This file handles all steps server-side so the page is self-contained and functional.
 */
session_start();
require __DIR__ . '/../connectdb/connect.php';
require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Helpers
function send_otp_email($to, $otp) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'carlmagante7@gmail.com';
        $mail->Password = 'tqnh pbgd wgio hupi';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('ARAT_KAPE@gmail.com', 'ARAT KAPE');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP';
        $mail->Body = "<p>Your OTP code is: <strong>{$otp}</strong></p>";
        $mail->send();
        return [true, ''];
    } catch (Exception $e) {
        return [false, $mail->ErrorInfo ?? $e->getMessage()];
    }
}

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1) Logged-in: change password
    if ($action === 'change_password' && (isset($_SESSION['user_id']) || isset($_SESSION['admin_id']))) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($new !== $confirm) {
            $_SESSION['fp_error'] = 'New passwords do not match.';
        } elseif (strlen($new) < 8) {
            $_SESSION['fp_error'] = 'Password must be at least 8 characters.';
        } else {
            // determine table and id
            if (isset($_SESSION['user_id'])) {
                $id = $_SESSION['user_id'];
                $stmt = $conn->prepare('SELECT password FROM users WHERE user_id = ?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->bind_result($hash);
                $stmt->fetch();
                $stmt->close();

                if (!password_verify($current, $hash)) {
                    $_SESSION['fp_error'] = 'Current password is incorrect.';
                } else {
                    $newhash = password_hash($new, PASSWORD_DEFAULT);
                    $upd = $conn->prepare('UPDATE users SET password = ? WHERE user_id = ?');
                    $upd->bind_param('si', $newhash, $id);
                    $upd->execute();
                    $upd->close();
                    $_SESSION['fp_success'] = 'Password changed successfully.';
                }
            } else {
                $id = $_SESSION['admin_id'];
                $stmt = $conn->prepare('SELECT password FROM admins WHERE admin_id = ?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->bind_result($hash);
                $stmt->fetch();
                $stmt->close();

                if (!password_verify($current, $hash)) {
                    $_SESSION['fp_error'] = 'Current password is incorrect.';
                } else {
                    $newhash = password_hash($new, PASSWORD_DEFAULT);
                    $upd = $conn->prepare('UPDATE admins SET password = ? WHERE admin_id = ?');
                    $upd->bind_param('si', $newhash, $id);
                    $upd->execute();
                    $upd->close();
                    $_SESSION['fp_success'] = 'Password changed successfully.';
                }
            }
        }
        header('Location: forgot.php');
        exit();
    }

    // 2) Forgot: request OTP
    if ($action === 'send_otp') {
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['fp_error'] = 'Please enter a valid email.';
            header('Location: forgot.php'); exit();
        }

        // check existence in users or admins
        $chk = $conn->prepare('SELECT "user" as t, user_id as id FROM users WHERE email = ? UNION SELECT "admin" as t, admin_id as id FROM admins WHERE email = ?');
        $chk->bind_param('ss', $email, $email);
        $chk->execute();
        $res = $chk->get_result();
        if ($res->num_rows === 0) {
            $_SESSION['fp_error'] = 'Email not found.';
            header('Location: forgot.php'); exit();
        }
        $row = $res->fetch_assoc();
        $_SESSION['reset_target'] = $row['t'];
        $_SESSION['reset_target_id'] = $row['id'];

        $otp = function_exists('random_int') ? random_int(100000, 999999) : rand(100000, 999999);
        $_SESSION['otp'] = (string)$otp;
        $_SESSION['otp_expires'] = time() + 300; // 5 minutes
        $_SESSION['otp_verified'] = false;
        $_SESSION['reset_email'] = $email;

        list($ok, $err) = send_otp_email($email, $otp);
        if ($ok) {
            $_SESSION['fp_success'] = 'OTP sent to your email. It will expire in 5 minutes.';
        } else {
            $_SESSION['fp_error'] = 'Failed to send OTP: ' . $err;
        }
        header('Location: forgot.php'); exit();
    }

    // 3) verify otp
    if ($action === 'verify_otp') {
        $entered = trim($_POST['otp'] ?? '');
        if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_expires'])) {
            $_SESSION['fp_error'] = 'No OTP requested. Please request an OTP first.';
            header('Location: forgot.php'); exit();
        }
        if (time() > $_SESSION['otp_expires']) {
            $_SESSION['fp_error'] = 'OTP expired. Please request a new one.';
            header('Location: forgot.php'); exit();
        }
        if ($entered === (string)($_SESSION['otp'])) {
            $_SESSION['otp_verified'] = true;
            $_SESSION['fp_success'] = 'OTP verified. Please set your new password.';
        } else {
            $_SESSION['fp_error'] = 'Invalid OTP.';
        }
        header('Location: forgot.php'); exit();
    }

    // 4) reset password after OTP verified
    if ($action === 'reset_password') {
        if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true || !isset($_SESSION['reset_email'])) {
            $_SESSION['fp_error'] = 'OTP not verified.';
            header('Location: forgot.php'); exit();
        }
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if ($new !== $confirm) {
            $_SESSION['fp_error'] = 'Passwords do not match.';
            header('Location: forgot.php'); exit();
        }
        if (strlen($new) < 8) {
            $_SESSION['fp_error'] = 'Password must be at least 8 characters.';
            header('Location: forgot.php'); exit();
        }

        $hash = password_hash($new, PASSWORD_DEFAULT);
        $email = $_SESSION['reset_email'];
        if ($_SESSION['reset_target'] === 'user') {
            $upd = $conn->prepare('UPDATE users SET password = ? WHERE email = ?');
        } else {
            $upd = $conn->prepare('UPDATE admins SET password = ? WHERE email = ?');
        }
        $upd->bind_param('ss', $hash, $email);
        $upd->execute();
        $upd->close();

        // clear reset session data
        unset($_SESSION['otp'], $_SESSION['otp_expires'], $_SESSION['otp_verified'], $_SESSION['reset_email'], $_SESSION['reset_target'], $_SESSION['reset_target_id']);
        $_SESSION['fp_success'] = 'Password reset successfully. You may now log in.';
        header('Location: ../Users/User.php'); exit();
    }
}

// Prepare view state
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
$otpRequested = isset($_SESSION['otp']);
$otpVerified = isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true;
$error = $_SESSION['fp_error'] ?? null; unset($_SESSION['fp_error']);
$success = $_SESSION['fp_success'] ?? null; unset($_SESSION['fp_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Change / Forgot Password</title>
  <link rel="stylesheet" href="../Users/design.css">
  <style>
    body{background-image:url('../Images/BG.jpg');background-size:cover;font-family:'Montserrat',sans-serif}
    .card{max-width:420px;margin:60px auto;background:rgba(255,255,255,0.95);padding:22px;border-radius:10px}
    input{width:100%;padding:10px;margin-top:10px;border-radius:6px;border:1px solid #ccc}
    button{margin-top:12px;padding:10px;border-radius:6px;border:0;background:#7b4f2e;color:#fff;width:100%}
    .msg{padding:10px;border-radius:6px;margin-bottom:10px}
    .err{background:#ffe7e6;color:#b00020}
    .ok{background:#e6ffef;color:#0a8a3a}
    .small{font-size:13px;color:#555;margin-top:8px}
    .link{display:inline-block;margin-top:8px}
  </style>
</head>
<body>
  <div class="card">
    <h2><?php echo $isLoggedIn ? 'Change Password' : 'Forgot Password'; ?></h2>
    <?php if ($error): ?>
      <div class="msg err"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="msg ok"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($isLoggedIn): // Change password form for logged-in users ?>
      <form method="POST">
        <input type="hidden" name="action" value="change_password">
        <input type="password" name="current_password" placeholder="Current password" required>
        <input type="password" name="new_password" placeholder="New password (min 8 chars)" required>
        <input type="password" name="confirm_password" placeholder="Confirm new password" required>
        <button type="submit">Change Password</button>
      </form>
      <div class="small">After changing password you will remain logged in.</div>
    <?php else: // Forgot password flow ?>

      <?php if (!$otpRequested): ?>
        <!-- Request OTP -->
        <form method="POST">
          <input type="hidden" name="action" value="send_otp">
          <input type="email" name="email" placeholder="Enter your registered email" required>
          <button type="submit">Send OTP</button>
        </form>
        <div class="small">An email with a 6-digit OTP will be sent. The OTP expires in 5 minutes.</div>
        <a class="link" href="../Users/User.php">Return to Login</a>

      <?php elseif ($otpRequested && !$otpVerified): ?>
        <!-- Verify OTP -->
        <form method="POST">
          <input type="hidden" name="action" value="verify_otp">
          <input type="text" name="otp" placeholder="Enter OTP" required>
          <button type="submit">Verify OTP</button>
        </form>
        <div class="small">If you didn't receive the code, try again after a moment.</div>
        <a class="link" href="?resend=1">Resend OTP</a>

      <?php else: ?>
        <!-- Reset password (after OTP verified) -->
        <form method="POST">
          <input type="hidden" name="action" value="reset_password">
          <input type="password" name="new_password" placeholder="New password (min 8 chars)" required>
          <input type="password" name="confirm_password" placeholder="Confirm new password" required>
          <button type="submit">Set New Password</button>
        </form>
      <?php endif; ?>

    <?php endif; ?>
  </div>

<?php
// handle resend via GET param (simple)
if (isset($_GET['resend']) && isset($_SESSION['reset_email'])) {
    $email = $_SESSION['reset_email'];
    $otp = function_exists('random_int') ? random_int(100000, 999999) : rand(100000, 999999);
    $_SESSION['otp'] = (string)$otp;
    $_SESSION['otp_expires'] = time() + 300;
    list($ok,$err) = send_otp_email($email, $otp);
    $_SESSION['fp_success'] = $ok ? 'OTP resent.' : 'Failed to resend OTP: ' . $err;
    header('Location: forgot.php'); exit();
}
?>
</body>
</html>
