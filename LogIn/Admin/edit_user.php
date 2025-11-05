<?php
session_start();
include '../connectdb/connect.php';

if (!isset($_SESSION['admin_username'])) {
    header("Location: ../Users/user.php");
    exit();
}

$user_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($user_id <= 0) {
    header("Location: Admin.php");
    exit();
}

// Fetch user details
$stmt = $conn->prepare("SELECT full_name, username, email FROM users WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: Admin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

    // Update user details
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ?, email = ? WHERE user_id = ?");
    $stmt->bind_param('sssi', $full_name, $username, $email, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: Admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User Details</title>
    <link rel="stylesheet" href="Admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        .edit-form {
    position: fixed;                /* Keeps it in the same place even when scrolling */
    top: 50%;                       /* Moves it halfway down the screen */
    left: 60%;                      /* Moves it halfway across the screen */
    transform: translate(-50%, -50%); /* Centers it perfectly */
    z-index: 1000;                  /* Brings it to the front */
    max-width: 600px;
    background: #fff;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    border: 1px solid #e0e0e0;
}
        .edit-form h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-size: 24px;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        .edit-form input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        .edit-form input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0,123,255,0.3);
        }
        .edit-form button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.3s ease;
            margin-top: 10px;
        }
        .edit-form button:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            transform: translateY(-1px);
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .back-link:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        .form-row {
            display: flex;
            gap: 20px;
        }
        .form-row .form-group {
            flex: 1;
        }
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<?php include 'include/navbar.php'; ?>

<!-- MAIN CONTENT -->
<div class="main-content" style="margin-left: 120px;">
    <div class="edit-form">
        <h2>Edit User Details</h2>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <button type="submit">Update Details</button>
        </form>
        <a href="Admin.php" class="back-link">Back to Dashboard</a>
    </div>
</div>

</body>
</html>
