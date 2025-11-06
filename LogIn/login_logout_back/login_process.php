<?php
session_start();
include __DIR__ . '/../connectdb/connect.php'; // DB connection ($conn)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../Users/User.php");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    $_SESSION['error'] = "Please enter username and password.";
    header("Location: ../Users/User.php");
    exit();
}

/* -------------------------
   1) Try Admins table first
   ------------------------- */
if ($stmt = $conn->prepare("SELECT admin_id, name, username, password, email FROM admins WHERE username = ?")) {
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($admin_id, $admin_name, $admin_username, $admin_password, $admin_email);
        $stmt->fetch();

        // verify hashed password
        if (password_verify($password, $admin_password)) {
            // admin authenticated
            $_SESSION['admin_id'] = $admin_id;
            $_SESSION['admin_username'] = $admin_username;
            $_SESSION['admin_name'] = $admin_name;

            $stmt->close();
            header("Location: ../Admin/Admin.php");
            exit();
        }

        // fallback: plaintext stored in DB (not recommended) -> rehash & update
        if ($password === $admin_password) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($upd = $conn->prepare("UPDATE admins SET password = ? WHERE admin_id = ?")) {
                $upd->bind_param('si', $newHash, $admin_id);
                $upd->execute();
                $upd->close();
            }

            $_SESSION['admin_id'] = $admin_id;
            $_SESSION['admin_username'] = $admin_username;
            $_SESSION['admin_name'] = $admin_name;

            $stmt->close();
            header("Location: ../Admin/Admin.php");
            exit();
        }

        else {
            // wrong password for admin
            $_SESSION['error'] = "❌ Wrong username or password.";
            $stmt->close();
            header("Location: ../Users/User.php");
            exit();
        }
        
    }
    $stmt->close();
}

/* -------------------------
   2) Check Users table
   ------------------------- */
if ($stmt2 = $conn->prepare("SELECT user_id, full_name, username, password, status FROM users WHERE username = ?")) {
    $stmt2->bind_param('s', $username);
    $stmt2->execute();
    $stmt2->store_result();

    if ($stmt2->num_rows > 0) {
        $stmt2->bind_result($user_id, $full_name, $user_username, $user_password, $user_status);
        $stmt2->fetch();

        // If account pending -> don't allow login
        if (strtolower($user_status) === 'pending') {
            $_SESSION['error'] = "Your account is still waiting for admin approval.";
            $stmt2->close();
            header("Location: ../Users/User.php");
            exit();
        }

        // 2a) Normal (secure) case: hashed password
        if (password_verify($password, $user_password)) {
            // set session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $user_username;
            $_SESSION['full_name'] = $full_name;

            // If user must change password on first login, redirect to change page
            if (isset($must_change_password) && $must_change_password == 1) {
                // keep session but send to change password
                $_SESSION['must_change_password'] = 1;
                $stmt2->close();
                header("Location: ../Users/change_password.php");
                exit();
            }

            // update is_online and time_in if those columns exist
            $colCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'is_online'");
            if ($colCheck && $colCheck->num_rows > 0) {
                if ($upd = $conn->prepare("UPDATE users SET is_online = 1, time_in = NOW() WHERE user_id = ?")) {
                    $upd->bind_param('i', $user_id);
                    $upd->execute();
                    $upd->close();
                }
            }

            $stmt2->close();
            header("Location: ../../HomePage/MainHome.php");
            exit();
        }

        // 2b) Fallback: plaintext password in DB (legacy) -> accept and rehash then update
        if ($password === $user_password) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($upd = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?")) {
                $upd->bind_param('si', $newHash, $user_id);
                $upd->execute();
                $upd->close();
            }

            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $user_username;
            $_SESSION['full_name'] = $full_name;

            // If user must change password on first login, redirect to change page
            if (isset($must_change_password) && $must_change_password == 1) {
                $_SESSION['must_change_password'] = 1;
                $stmt2->close();
                header("Location: ../Users/change_password.php");
                exit();
            }
            // update is_online/time_in if present
            $colCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'is_online'");
            if ($colCheck && $colCheck->num_rows > 0) {
                if ($upd2 = $conn->prepare("UPDATE users SET is_online = 1, time_in = NOW() WHERE user_id = ?")) {
                    $upd2->bind_param('i', $user_id);
                    $upd2->execute();
                    $upd2->close();
                }
            }

            $stmt2->close();
            header("Location: ../../HomePage/MainHome.php");
            exit();
        }

        // wrong password of username
        $_SESSION['error'] = "Invalid password.";
        $stmt2->close();
        header("Location: ../Users/User.php");
        exit();
    } else {
        // username not found in users table
        $_SESSION['error'] = "Username not found.";
        $stmt2->close();
        header("Location: ../Users/User.php");
        exit();
    }
} else {
    // prepared statement failed
    $_SESSION['error'] = "Server error. Please try again later.";
    header("Location: ../Users/User.php");
    exit();
}
?>
