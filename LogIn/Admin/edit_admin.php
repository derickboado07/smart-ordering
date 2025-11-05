<?php
session_start();
include '../connectdb/connect.php';

if (!isset($_SESSION['admin_username'])) {
    header("Location: ../Users/user.php");
    exit();
}

// Fetch current admin details
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT name, username, email FROM admins WHERE admin_id = ?");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

    // Update admin details
    $stmt = $conn->prepare("UPDATE admins SET name = ?, username = ?, email = ? WHERE admin_id = ?");
    $stmt->bind_param('sssi', $name, $username, $email, $admin_id);
    $stmt->execute();
    $stmt->close();

    // Update session
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_name'] = $name;

    header("Location: Admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Admin Details</title>
    <link rel="stylesheet" href="Admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>

        .edit-form {
    position: fixed;          /* Make it stay in place */
    top: 50%;                 /* Move it down 50% of the viewport */
    left: 60%;                /* Move it right 50% of the viewport */
    transform: translate(-50%, -50%);  /* Adjust position to truly center */
    z-index: 1000;            /* Ensure it appears above other elements */
    max-width: 500px;
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
        }
        .edit-form h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .edit-form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .edit-form button {
            width: 100%;
            padding: 10px;
            background-color: #2ecc71;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .edit-form button:hover {
            background-color: #27ae60;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #333;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<?php include 'include/navbar.php'; ?>

<!-- MAIN CONTENT -->
<div class="main-content" style="margin-left: 120px;">
    <div class="edit-form">
        <h2>Edit Admin Details</h2>
        <form method="POST">
            <input type="text" name="name" placeholder="Name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
            <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
            <button type="submit">Update Details</button>
        </form>
        <a href="Admin.php" class="back-link">Back to Dashboard</a>
    </div>
</div>

</body>
</html>
