<?php
require_once '../includes/admin_auth.php';
require_once '../db/config.php';
//requireAdminAuth();

// Fetch statistics
try {
    // Total users count
    $stmt = $conn->prepare("
        SELECT 
        (SELECT COUNT(*) FROM clients) as total_clients,
        (SELECT COUNT(*) FROM helpers) as total_helpers,
        (SELECT COUNT(*) FROM tasks WHERE status = 'completed') as completed_tasks,
        (SELECT COUNT(*) FROM tasks WHERE status = 'pending') as pending_tasks
    ");
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();

    // Recent users
    $stmt = $conn->prepare("
        (SELECT 'client' as type, client_id as user_id, first_name, last_name, email, created_at 
         FROM clients 
         ORDER BY created_at DESC LIMIT 5)
        UNION ALL
        (SELECT 'helper' as type, helper_id as user_id, first_name, last_name, email, created_at 
         FROM helpers 
         ORDER BY created_at DESC LIMIT 5)
        ORDER BY created_at DESC LIMIT 10
    ");
    $stmt->execute();
    $recent_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log($e->getMessage());
    $error_message = "Error loading dashboard data";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard | HandyLink</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/admin_dashboard.css">
    <!-- Add Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav>
        <div class="logo">HandyLink Admin</div>
        <ul>
            <li><a href="#dashboard" class="active">Dashboard</a></li>
            <li><a href="#users">Users</a></li>
            <li><a href="#tasks">Tasks</a></li>
            <li><a href="#reports">Reports</a></li>
            <li><a href="#settings">Settings</a></li>
        </ul>
        <div class="user-menu">
            <span class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
            <a href="../actions/logout.php">Logout</a>
        </div>
    </nav>

    <main>
        <!-- Quick Stats -->
        <section id="quick-stats">
            <div class="stat-card">
                <h3>Total Clients</h3>
                <p class="count"><?php echo $stats['total_clients']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Helpers</h3>
                <p class="count"><?php echo $stats['total_helpers']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Completed Tasks</h3>
                <p class="count"><?php echo $stats['completed_tasks']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Tasks</h3>
                <p class="count"><?php echo $stats['pending_tasks']; ?></p>
            </div>
        </section>

        <!-- User Management -->
        <section id="user-management">
            <div class="section-header">
                <h2>User Management</h2>
                <div class="actions">
                    <input type="text" id="userSearch" placeholder="Search users...">
                    <button onclick="exportUsers()">Export Users</button>
                </div>
            </div>

            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('all')">All Users</button>
                <button class="tab-btn" onclick="showTab('clients')">Clients</button>
                <button class="tab-btn" onclick="showTab('helpers')">Helpers</button>
                <button class="tab-btn" onclick="showTab('blocked')">Blocked</button>
            </div>

            <div class="user-list">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Joined</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo ucfirst($user['type']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td><span class="status active">Active</span></td>
                            <td class="actions">
                                <button onclick="viewUser(<?php echo $user['user_id']; ?>)" class="view-btn">View</button>
                                <button onclick="blockUser(<?php echo $user['user_id']; ?>)" class="block-btn">Block</button>
                                <button onclick="notifyUser(<?php echo $user['user_id']; ?>)" class="notify-btn">Notify</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- System Activity -->
        <section id="system-activity">
            <h2>System Activity</h2>
            <div class="chart-container">
                <canvas id="activityChart"></canvas>
            </div>
        </section>
    </main>

    <!-- Modals -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>User Details</h2>
            <div id="userDetails"></div>
        </div>
    </div>

    <div id="notifyModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Send Notification</h2>
            <form id="notifyForm">
                <div class="form-group">
                    <label>Subject:</label>
                    <input type="text" name="subject" required>
                </div>
                <div class="form-group">
                    <label>Message:</label>
                    <textarea name="message" required></textarea>
                </div>
                <button type="submit">Send Notification</button>
            </form>
        </div>
    </div>

    <script>
        // Activity Chart
        const ctx = document.getElementById('activityChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'New Users',
                    data: [12, 19, 3, 5, 2, 3],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // User Management Functions
        function showTab(type) {
            // Implement tab switching logic
        }

        function viewUser(userId) {
            fetch(`../actions/get_user_details.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('userDetails').innerHTML = `
                            <p><strong>Name:</strong> ${data.user.first_name} ${data.user.last_name}</p>
                            <p><strong>Email:</strong> ${data.user.email}</p>
                            <p><strong>Phone:</strong> ${data.user.phone}</p>
                            <p><strong>Joined:</strong> ${data.user.created_at}</p>
                            <!-- Add more user details -->
                        `;
                        document.getElementById('userModal').style.display = 'block';
                    }
                });
        }

        function blockUser(userId) {
            if (confirm('Are you sure you want to block this user?')) {
                fetch('../actions/block_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ user_id: userId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI to reflect blocked status
                        location.reload();
                    }
                });
            }
        }

        function notifyUser(userId) {
            document.getElementById('notifyModal').style.display = 'block';
            document.getElementById('notifyForm').onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('user_id', userId);

                fetch('../actions/send_notification.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('notifyModal').style.display = 'none';
                        alert('Notification sent successfully!');
                    }
                });
            };
        }

        function exportUsers() {
            window.location.href = '../actions/export_users.php';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>