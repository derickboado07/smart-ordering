<?php
session_start();
include '../connectdb/connect.php';
require '../vendor/autoload.php'; // PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* -------------------------
   0️⃣ Sanitize inputs
   ------------------------- */
$surname   = trim($_POST['surname']);
$firstname = trim($_POST['firstname']);
$username  = trim($_POST['username']);
$email     = trim($_POST['email']);
$password  = trim($_POST['password']);
$confirm   = trim($_POST['confirm_password']);



/* -------------------------
   2️⃣ Check password confirmation
   ------------------------- */
if ($password !== $confirm) {
    echo "<script>
    alert('❌ Passwords do not match!');
   window.location.href='../Users/user.php';
    </script>";
    exit();
}

/* -------------------------
   3️⃣ Hash the password
   ------------------------- */
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

/* -------------------------
   4️⃣ Combine full name
   ------------------------- */
$full_name = $firstname . ' ' . $surname;

/* -------------------------
   5️⃣ Insert new user as pending
   ------------------------- */
$stmt = $conn->prepare("
    INSERT INTO users (full_name, username, email, password, status, is_online)
    VALUES (?, ?, ?, ?, 'pending', 0)
");
$stmt->bind_param('ssss', $full_name, $username, $email, $hashed_password);
$stmt->execute();
$new_user_id = $stmt->insert_id;
$stmt->close();

/* -------------------------
   6️⃣ Insert admin notification in DB
   ------------------------- */
$admin_id = 1; // Assuming admin ID is 1
$message = "New user '$full_name' registered and is awaiting approval.";
$stmt = $conn->prepare("
    INSERT INTO admin_notification (admin_id, user_id, message, is_read, created_at)
    VALUES (?, ?, ?, 0, NOW())
");
$stmt->bind_param('iis', $admin_id, $new_user_id, $message);
$stmt->execute();
$stmt->close();

/* -------------------------
   7️⃣ Send Gmail notification to admin
   ------------------------- */
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'ARAT_KAPE@gmail.com'; // Your Gmail
    $mail->Password   = 'tqnh pbgd wgio hupi'; // Gmail App Password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('ARAT_KAPE@gmail.com', 'ARAT KAPE Admin');
    $mail->addAddress('ARAT_KAPE@gmail.com', 'Admin'); // Admin email

    $mail->isHTML(true);
    $mail->Subject = 'New User Registration Pending Approval';
    $mail->Body    = "
        <p>Hello Admin,</p>
        <p>New user <b>$full_name</b> has registered and is awaiting your approval.</p>
        <p>Please log in to the Admin Panel to approve or reject the user.</p>
    ";
    $mail->send();
} catch (Exception $e) {
    // Email fails are non-blocking; user is still pending
}

/* -------------------------
   8️⃣ Notify user that registration was successful
   ------------------------- */
echo "<script>
alert('✅ Your registration was sent! Please wait for admin approval.');
window.location.href='../Users/user.php';
</script>";
?>
