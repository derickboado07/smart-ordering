<?php
session_start();
include '../connectdb/connect.php';
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize the response array to return JSON data
$response = ['success'=>false,'message'=>'','user'=>null];

// Check if the admin is logged in; if not, return error
if(!isset($_SESSION['admin_username'])){
    $response['message'] = "You must be logged in as admin.";
    echo json_encode($response);
    exit();
}

// Check if POST request contains action and user ID
if(isset($_POST['action'], $_POST['id'])){
    $user_id = intval($_POST['id']); // Sanitize user ID
    $action = $_POST['action'];      // Get action (approve/reject)

    if($action==='approve'){
        // Update the user's status to 'approved' in the database
        $stmt = $conn->prepare("UPDATE users SET status='approved' WHERE user_id=?");
        $stmt->bind_param('i',$user_id);
        $stmt->execute();
        $stmt->close();

        // Fetch user's information from database
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
        $stmt->bind_param('i',$user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        // Create a new PHPMailer instance to send email
        $mail = new PHPMailer(true);
        try {
            // Server settings for Gmail SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'carlmagante7@gmail.com'; // Sender Gmail
            $mail->Password = 'tqnh pbgd wgio hupi';   // App password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Recipient and email content settings
            $mail->setFrom('ARAT_KAPE@gmail.com','Arat Coffee MCU Admin'); // Sender info
            $mail->addAddress($user['email'],$user['full_name']);     // Recipient info
            $mail->isHTML(true);
            $mail->Subject = 'ARAT KAPE Account Approved';           // Email subject
            $mail->Body = "
                <p>Hello <b>{$user['full_name']}</b>,</p>
                <p>🎉 Your registration has been approved! You can now <a href='http://localhost/ARAT_KAPE/Users/User.php'>log in</a>.</p>
            ";

            // Send the email
            $mail->send();
            $response['message'] = "User approved and email sent.";
        } catch(Exception $e){
            // Catch any errors while sending email
            $response['message'] = "User approved but email could not be sent.";
        }

        // Set success response
        $response['success'] = true;
        $response['user'] = $user;

    } elseif($action==='reject'){
        // Delete pending user from the database if rejected
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id=? AND status='pending'");
        $stmt->bind_param('i',$user_id);
        $stmt->execute();
        $stmt->close();

        // Set response for rejection
        $response['success'] = true;
        $response['message'] = "User registration rejected and removed.";
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>
