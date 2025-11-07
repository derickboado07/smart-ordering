<?php
session_start();
require '../connectdb/connect.php';
require '../vendor/autoload.php'; // for PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$email = $_POST['email'];
$new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

// Check if email exists in either admin or users table
$check_user = $conn->prepare("SELECT email FROM users WHERE email = ? UNION SELECT email FROM admins WHERE email = ?");
$check_user->bind_param("ss", $email, $email);
$check_user->execute();
$result = $check_user->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Email not found!";
    header("Location: forgot.php");
    exit();
}

// Generate OTP
$otp = rand(100000, 999999);

// Store OTP and new password temporarily in session
$_SESSION['reset_email'] = $email;
$_SESSION['otp'] = $otp;
$_SESSION['new_password'] = $new_password;
$_SESSION['otp_expires'] = time() + (5 * 60); // 5 minutes expiration

// Send email with PHPMailer
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';  
    $mail->SMTPAuth = true;
    $mail->Username = 'carlmagante7@gmail.com'; // your Gmail
    $mail->Password = 'tqnh pbgd wgio hupi';  // app password (not your Gmail password!)
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('ARAT_KAPE@gmail.com', 'Arat Coffee MCU');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = "Password Reset OTP";
    $mail->Body = "<p>Your OTP code is: <strong>$otp</strong></p>";

    $mail->send();
    header("Location: verify_otp.php");
} catch (Exception $e) {
    $_SESSION['error'] = "Email could not be sent. Error: {$mail->ErrorInfo}";
    header("Location: forgot.php");
}
