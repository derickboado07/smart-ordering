<?php
// Make script robust: return JSON on fatal errors and log them.
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
header('Content-Type: application/json');

set_exception_handler(function($e){
    $msg = 'Server exception: ' . $e->getMessage();
    @file_put_contents(__DIR__ . '/create_user_exceptions.log', date('c') . " - " . $msg . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit();
});

// Load PHPMailer autoload and import classes at file scope
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    include '../connectdb/connect.php';

    // Check admin session
    if (!isset($_SESSION['admin_username'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    // Read input
    $firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
    $surname = isset($_POST['surname']) ? trim($_POST['surname']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    if (empty($firstname) || empty($surname) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please provide valid first name, surname, and email.']);
        exit();
    }

    $full_name = $firstname . ' ' . $surname;
    $email = strtolower($email);

    // Check if email already exists in users table
    $chk = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $chk->bind_param('s', $email);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'That email is already associated with an existing user.']);
        $chk->close();
        exit();
    }
    $chk->close();

    // Also check admins table
    $chk2 = $conn->prepare("SELECT admin_id FROM admins WHERE email = ?");
    $chk2->bind_param('s', $email);
    $chk2->execute();
    $chk2->store_result();
    if ($chk2->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'That email is already used by an administrator.']);
        $chk2->close();
        exit();
    }
    $chk2->close();

    // Helper: build username pattern (FullFirstName + SurnameInitial + _arat)
    function build_base_username($firstname, $surname) {
        $firstname_clean = strtolower(preg_replace('/[^a-z0-9]/', '', $firstname));
        $surname_initial = strtolower(substr(preg_replace('/[^a-z0-9]/', '', $surname), 0, 1));
        $base = $firstname_clean . $surname_initial . '_arat';
        return $base;
    }

    // Ensure unique username
    function generate_unique_username($conn, $base) {
        $username = $base;
        $i = 0;
        while (true) {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 0) {
                $stmt->close();
                break;
            }
            $stmt->close();
            $i++;
            $username = $base . $i;
        }
        return $username;
    }

    // Generate password (3 letters from first name + 3-digit number)
    function generate_temp_password($firstname) {
        $part = substr($firstname, 0, 3);
        if (strlen($part) < 3) $part = str_pad($part, 3, 'x');
        $part = ucfirst(strtolower($part)); // ensures an uppercase letter
        $num = rand(100, 999);
        return $part . $num;
    }

    $base = build_base_username($firstname, $surname);
    $username = generate_unique_username($conn, $base);
    $plain_password = generate_temp_password($firstname);
    $hashed = password_hash($plain_password, PASSWORD_DEFAULT);

    // Insert into users table
    $statusValue = 'approved';
    $is_online = 0;
    $must_change = 1;
    $created_by_admin = 1;

    $cols = ['full_name','username','email','password','status','is_online'];
    $placeholders = ['?','?','?','?','?','?'];
    $types = 'sssssi';
    $values = [$full_name, $username, $email, $hashed, $statusValue, $is_online];

    // Check optional columns
    $colCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'must_change_password'");
    if ($colCheck && $colCheck->num_rows > 0) {
        $cols[] = 'must_change_password';
        $placeholders[] = '?';
        $types .= 'i';
        $values[] = $must_change;
    }

    $colCheck2 = $conn->query("SHOW COLUMNS FROM users LIKE 'created_by_admin'");
    if ($colCheck2 && $colCheck2->num_rows > 0) {
        $cols[] = 'created_by_admin';
        $placeholders[] = '?';
        $types .= 'i';
        $values[] = $created_by_admin;
    }

    $sql = "INSERT INTO users (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB prepare failed: ' . $conn->error]);
        exit();
    }

    $bind_names[] = $types;
    for ($i=0; $i<count($values); $i++) {
        $bind_names[] = &$values[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
    $executed = $stmt->execute();
    if (!$executed) {
        echo json_encode(['success' => false, 'message' => 'DB execute failed: ' . $stmt->error]);
        exit();
    }
    $new_user_id = $stmt->insert_id;
    $stmt->close();

    // Send email with credentials
    $smtp = include __DIR__ . '/smtp_config.php';
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $smtp['host'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['username'] ?? '';
        $mail->Password   = $smtp['password'] ?? '';
        $mail->SMTPSecure = $smtp['secure'] ?? 'tls';
        $mail->Port       = $smtp['port'] ?? 587;

        $fromEmail = $smtp['from_email'] ?? $mail->Username;
        $fromName = $smtp['from_name'] ?? 'ARAT Coffee Admin';

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($email, $full_name);

        $mail->isHTML(true);
        $mail->Subject = 'Your ARAT Coffee Account Details';
        $mail->Body    = "<p>Hello " . htmlspecialchars($firstname) . ",</p>
            <p>Your account has been created by the administrator.</p>
            <p><strong>Username:</strong> " . htmlspecialchars($username) . "<br>
            <strong>Password:</strong> " . htmlspecialchars($plain_password) . "</p>
            <p>Please log in to the system and change your password immediately.</p>
            <p>– ARAT Coffee Team</p>";

        $mail->send();
        $email_sent = true;
    } catch (Exception $e) {
        $email_sent = false;
        $mail_error = $e->getMessage();
        @file_put_contents(__DIR__ . '/mail_errors.log', date('Y-m-d H:i:s') . " - Failed to send email to {$email}: " . $mail_error . "\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Email delivery failed: ' . $mail_error]);
        exit();
    }

    // Return success JSON
    echo json_encode([
        'success' => true,
        'message' => 'User created successfully',
        'user' => [
            'user_id' => $new_user_id,
            'full_name' => $full_name,
            'username' => $username,
            'email' => $email,
            'is_online' => $is_online
        ],
        'email_sent' => $email_sent ?? false,
        'plain_password' => $plain_password
    ]);

} catch (Exception $e) {
    $err = 'Unhandled error: ' . $e->getMessage();
    @file_put_contents(__DIR__ . '/create_user_exceptions.log', date('c') . " - " . $err . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $err]);
    exit();
}
?>
