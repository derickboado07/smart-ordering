<?php
session_start();
require '../connectdb/connect.php';
require '../vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// If email or new password session not set, redirect
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['new_password'])) {
    header("Location: forgot.php");
    exit();
}

$email = $_SESSION['reset_email'];
$new_password = $_SESSION['new_password'];

// Initialize or increment resend counter
if (!isset($_SESSION['otp_resend_count'])) {
    $_SESSION['otp_resend_count'] = 0;
}

$max_resends = 3;

// Check if resend limit reached
if ($_SESSION['otp_resend_count'] >= $max_resends) {
    $_SESSION['error'] = "⚠️ You have reached the maximum number of OTP resends ($max_resends).";
    header("Location: verify_otp.php");
    exit();
}

// Generate new OTP and set expiration
$new_otp = rand(100000, 999999);
$_SESSION['otp'] = $new_otp;
$_SESSION['otp_expires'] = time() + (5 * 60); // valid for 5 mins

// Increment resend count
$_SESSION['otp_resend_count']++;

// Send new OTP email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'YOUR_EMAIL@gmail.com'; // replace with your email
    $mail->Password = 'YOUR_APP_PASSWORD';    // replace with your app password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('YOUR_EMAIL@gmail.com', 'Arat Coffee MCU');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Your New OTP Code';
    $mail->Body = "
        <p>Hello,</p>
        <p>Your new OTP code is: <b>$new_otp</b></p>
        <p>This code is valid for 5 minutes.</p>
        <p>You have used <b>{$_SESSION['otp_resend_count']}</b> of <b>$max_resends</b> resend attempts.</p>
    ";

    $mail->send();

    $_SESSION['error'] = "✅ New OTP sent to your email. ({$_SESSION['otp_resend_count']} / $max_resends)";
    header("Location: verify_otp.php");
    exit();
} catch (Exception $e) {
    $_SESSION['error'] = "❌ Failed to resend OTP. Please try again.";
    header("Location: verify_otp.php");
    exit();
}
