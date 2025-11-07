<?php
session_start();
include '../connectdb/connect.php';

if (!isset($_SESSION['admin_username'])) {
    header("Location: ../Users/User.php");
    exit();
}

// Fetch pending users
$pendingUsers = $conn->query("SELECT * FROM users WHERE status='pending' ORDER BY user_id ASC");

// Fetch approved users
$approvedUsers = $conn->query("
    SELECT *,
        CASE WHEN is_online=1 THEN 'Online' ELSE 'Offline' END AS status_text
    FROM users WHERE status='approved' ORDER BY full_name ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Admin Dashboard</title>
<link rel="stylesheet" href="Admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

<style>
/* Extra CSS for Refresh Button */
.refresh-btn {
    background-color: orange;
    color: white;
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    position: absolute;
    right: 30px;
    top: 30px;
    transition: background 0.2s;
}
.refresh-btn:hover {
    background-color: darkorange;
}
#notificationMessage {
    display: none;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 5px;
    font-weight: bold;
}
#notificationMessage.success { background-color: #2ecc71; color: white; }
#notificationMessage.error { background-color: #e74c3c; color: white; }
/* Create User styles */
.create-user-section { margin-top:20px; margin-bottom:20px; padding:18px; border:1px solid #ddd; border-radius:8px; max-width:920px; background:#fff; }
.create-user-row { display:flex; gap:12px; align-items:center; }
.create-user-row input[type="text"], .create-user-row input[type="email"] { flex:1; padding:12px 14px; font-size:16px; height:48px; border:1px solid #ccc; border-radius:6px; }
.create-user-row button[type="submit"] { padding:12px 20px; background:#2ecc71; color:white; border:none; border-radius:6px; cursor:pointer; font-size:16px; height:48px; }
.create-user-section h2 { margin:0 0 8px 0; font-size:20px; }
.create-user-note { margin-top:10px; color:#666; }

/* Responsive improvements for Create User + Refresh button */
@media (max-width: 768px) {
    .refresh-btn { position: static; right: auto; top: auto; display: inline-block; margin: 8px 0 0 auto; }
    .create-user-row { flex-wrap: wrap; }
    .create-user-row input[type="text"], .create-user-row input[type="email"] { flex: 1 1 100%; min-width: 0; }
    .create-user-row button[type="submit"] { width: 100%; margin-top: 8px; }
}
@media (max-width: 480px) {
    .refresh-btn { width: 100%; text-align: center; }
    .create-user-section h2 { font-size: 24px; }
}
</style>
</head>
<body>

<?php include 'include/navbar.php'; ?>

<!-- MAIN CONTENT -->
<div class="main-content">
    <!-- 🔄 Refresh Button on the Top Right -->
    <button class="refresh-btn" onclick="refreshDashboard()">⟳ Refresh Dashboard</button>

    
    <div id="notificationMessage"></div>

    <!-- Create User (Admin only) -->
    <section class="create-user-section">
        <h2 style="font-size: 32px;">Create User</h2>
        <form id="createUserForm">
            <div class="create-user-row">
                <input type="text" name="surname" id="cu_surname" placeholder="Surname" required />
                <input type="text" name="firstname" id="cu_firstname" placeholder="First Name" required />
                <input type="email" name="email" id="cu_email" placeholder="Email" required />
                <button type="submit">Create</button>
            </div>
        </form>
        <p class="create-user-note">Admin will create accounts and users will receive credentials by email. On first login users must change their password.</p>
    </section>

    <!-- Pending Users Table -->
    <?php if ($pendingUsers->num_rows > 0): ?>
        <div class="table-wrapper">
        <table id="pendingUsersTable">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $pendingUsers->fetch_assoc()): ?>
                <tr id="pendingRow<?= $row['user_id']; ?>">
                    <td><?= $row['user_id']; ?></td>
                    <td><?= htmlspecialchars($row['full_name']); ?></td>
                    <td><?= htmlspecialchars($row['username']); ?></td>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td>
                        <button class="approve" onclick="handleUser('approve', <?= $row['user_id']; ?>)">Approve</button>
                        <button onclick="handleUser('reject', <?= $row['user_id']; ?>)">Reject</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    <?php else: ?>
        
    <?php endif; ?>

    <!-- Approved Users Table -->
    <h1 style="margin-top:40px;">Active Users</h1>
    <div class="table-wrapper">
    <table id="usersTable">
        <thead>
            <tr>
                <th>User ID</th>
                <th>Full Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Status</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="approvedUsersBody">
            <?php while($user = $approvedUsers->fetch_assoc()): ?>
            <tr id="userRow<?= $user['user_id']; ?>">
                <td><?= $user['user_id']; ?></td>
                <td><?= htmlspecialchars($user['full_name']); ?></td>
                <td><?= htmlspecialchars($user['username']); ?></td>
                <td><?= htmlspecialchars($user['email']); ?></td>
                <td class="<?= strtolower($user['status_text']); ?>"><?= $user['status_text']; ?></td>
                <td><?= htmlspecialchars($user['time_in'] ?? '—'); ?></td>
                <td><?= htmlspecialchars($user['time_out'] ?? '—'); ?></td>
                <td>
                    <a href="edit_user.php?id=<?= $user['user_id']; ?>" class="edit-btn">Edit</a>
                    <a href="remove_user.php?id=<?= $user['user_id']; ?>"
                       class="remove-btn"
                       onclick="return confirm('Are you sure you want to remove this user?');">
                       Remove
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<script>
// ✅ Function to Approve or Reject Users
function handleUser(action, userId) {
    if (!confirm(`${action === 'approve' ? 'Approve' : 'Reject'} this user?`)) return;
    const formData = new FormData();
    formData.append('action', action);
    formData.append('id', userId);

    fetch('notification.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        const msgDiv = document.getElementById('notificationMessage');
        msgDiv.innerText = data.message;
        msgDiv.className = data.success ? 'success' : 'error';
        msgDiv.style.display = 'block';
        setTimeout(() => msgDiv.style.display = 'none', 4000);

        if (data.success) {
            const row = document.getElementById('pendingRow' + userId);
            if (row) row.remove();
            if (action === 'approve') addUserToTable(data.user);
        }
    })
    .catch(() => {
        const msgDiv = document.getElementById('notificationMessage');
        msgDiv.innerText = 'Server error. Please try again.';
        msgDiv.className = 'error';
        msgDiv.style.display = 'block';
    });
}

// ✅ Add Approved User Dynamically to Table
function addUserToTable(user) {
    // Use the approved users tbody (matches server-rendered table)
    const tbody = document.getElementById('approvedUsersBody') || document.querySelector('#usersTable tbody');
    const row = document.createElement('tr');
    row.id = 'userRow' + user.user_id;

    // Derive status text/class similar to server-side rendering
    const statusText = (user.is_online == 1 || user.is_online === '1') ? 'Online' : 'Offline';
    const statusClass = statusText.toLowerCase();

    // Use time_in/time_out if available, otherwise show em-dash
    const timeIn = user.time_in ? user.time_in : '—';
    const timeOut = user.time_out ? user.time_out : '—';

    row.innerHTML = `
        <td>${user.user_id}</td>
        <td>${user.full_name}</td>
        <td>${user.username}</td>
        <td>${user.email}</td>
        <td class="${statusClass}">${statusText}</td>
        <td>${timeIn}</td>
        <td>${timeOut}</td>
        <td>
            <a href="edit_user.php?id=${user.user_id}" class="edit-btn">Edit</a>
            <a href="remove_user.php?id=${user.user_id}" 
               class="remove-btn"
               onclick="return confirm('Are you sure you want to remove this user?');">
               Remove
            </a>
        </td>
    `;
    tbody.appendChild(row);
}

// ✅ REFRESH DASHBOARD FUNCTION
function refreshDashboard() {
    if (!confirm('Refresh the dashboard and update all user statuses?')) return;

    fetch('refresh_dashboard.php')
    .then(res => res.json())
    .then(data => {
        const msgDiv = document.getElementById('notificationMessage');
        msgDiv.innerText = data.message;
        msgDiv.className = data.success ? 'success' : 'error';
        msgDiv.style.display = 'block';
        setTimeout(() => msgDiv.style.display = 'none', 4000);

        if (data.success) {
            // Reload the page to show updated statuses
            location.reload();
        }
    })
    .catch(() => {
        const msgDiv = document.getElementById('notificationMessage');
        msgDiv.innerText = 'Error refreshing dashboard.';
        msgDiv.className = 'error';
        msgDiv.style.display = 'block';
    });
}

// Create User form handler
document.getElementById('createUserForm').addEventListener('submit', function(e){
    e.preventDefault();
    const form = e.currentTarget;
    const fd = new FormData(form);
    fetch('create_user.php', { method: 'POST', body: fd })
    .then(r => r.text())
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (err) {
            throw new Error('Server response (non-JSON): ' + text);
        }
        return data;
    })
    .then(data => {
        const msgDiv = document.getElementById('notificationMessage');
        // Build message
        let message = data.message || (data.success ? 'User created' : 'Error');
        if (data.email_sent === false) {
            message += ' — email delivery failed. Temporary password is shown below for manual delivery.';
        }

        msgDiv.innerText = message;
        msgDiv.className = data.success ? 'success' : 'error';
        msgDiv.style.display = 'block';
        setTimeout(() => msgDiv.style.display = 'none', 8000);

        if (data.success && data.user) {
            addUserToTable(data.user);
            form.reset();

            // If email failed, show credentials to admin to copy/send manually
            if (data.email_sent === false) {
                const cred = `Username: ${data.user.username}\nPassword: ${data.plain_password}`;
                // show a prompt-like alert so admin can copy credentials
                alert('Email delivery failed. Please copy credentials and send to user manually:\n\n' + cred);
            }
        }
    })
    .catch((err) => {
        const msgDiv = document.getElementById('notificationMessage');
        msgDiv.innerText = err.message || 'Server error creating user.';
        msgDiv.className = 'error';
        msgDiv.style.display = 'block';
        console.error('Create user error:', err);
    });
});
</script>
</body>
</html>
