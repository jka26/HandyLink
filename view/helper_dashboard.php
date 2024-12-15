<?php
include("../db/config.php");
session_start();

// Check if helper is logged in
if (!isset($_SESSION['helper_id'])) {
    header("Location: login.php");
    exit;
}

$helper_id = $_SESSION['helper_id'];
$helper_name = $_SESSION['first_name'];

try {
     // Initialize all variables with default values
     $today_earnings = 0;
     $tasks_completed = 0;
     $rating = 0;
     $response_rate = 0;
     $weekly_earnings = 0;
     $monthly_earnings = 0;
     $new_requests = array();
     $upcoming_tasks = array();
     $recent_payments = array();
     $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');

    // Quick Stats
    // Today's Earnings
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as today_earnings 
                           FROM earnings 
                           WHERE helper_id = ? 
                           AND DATE(payment_date) = CURDATE()");
    $stmt->bind_param("i", $helper_id);
    $stmt->execute();
    $today_earnings = $stmt->get_result()->fetch_assoc()['today_earnings'];

    // Tasks Completed
    $stmt = $conn->prepare("SELECT COUNT(*) as completed_count 
                           FROM tasks 
                           WHERE helper_id = ? 
                           AND status = 'completed'");
    $stmt->bind_param("i", $helper_id);
    $stmt->execute();
    $tasks_completed = $stmt->get_result()->fetch_assoc()['completed_count'];

    // Average Rating
    $stmt = $conn->prepare("SELECT COALESCE(AVG(rating), 0) as avg_rating 
                           FROM reviews 
                           WHERE helper_id = ?");
    $stmt->bind_param("i", $helper_id);
    $stmt->execute();
    $rating = number_format($stmt->get_result()->fetch_assoc()['avg_rating'], 1);

    // Response Rate
    $stmt = $conn->prepare("SELECT response_rate 
                           FROM helpers 
                           WHERE helper_id = ?");
    $stmt->bind_param("i", $helper_id);
    $stmt->execute();
    $response_rate = $stmt->get_result()->fetch_assoc()['response_rate'];

    // New Task Requests
    $stmt = $conn->prepare("SELECT t.task_id, t.title, t.location, t.budget, 
                                  c.name as client_name
                           FROM tasks t
                           JOIN clients c ON t.client_id = c.client_id
                           WHERE t.helper_id = ? 
                           AND t.status = 'pending'");
    $stmt->bind_param("i", $helper_id);
    $stmt->execute();
    $new_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Upcoming Tasks
    $stmt = $conn->prepare("SELECT t.task_id, t.title, t.date, t.time, 
                                  t.location, c.name as client_name
                           FROM tasks t
                           JOIN clients c ON t.client_id = c.client_id
                           WHERE t.helper_id = ? 
                           AND t.status IN ('accepted', 'in_progress')
                           ORDER BY t.date, t.time");
    $stmt->bind_param("i", $helper_id);
    $stmt->execute();
    $upcoming_tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Weekly Earnings
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as weekly_earnings 
                           FROM earnings 
                           WHERE helper_id = ? 
                           AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $stmt->bind_param("i", $helper_id);
    $stmt->execute();
    $weekly_earnings = $stmt->get_result()->fetch_assoc()['weekly_earnings'];

    // Monthly Earnings
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as monthly_earnings 
                           FROM earnings 
                           WHERE helper_id = ? 
                           AND MONTH(payment_date) = MONTH(CURDATE())
                           AND YEAR(payment_date) = YEAR(CURDATE())");
    $stmt->bind_param("i", $helper_id);
    $stmt->execute();
    $monthly_earnings = $stmt->get_result()->fetch_assoc()['monthly_earnings'];

    // Recent Payments
    $stmt = $conn->prepare("SELECT e.payment_date, t.title as task, 
                                  c.name as client, e.amount, e.status
                           FROM earnings e
                           JOIN tasks t ON e.task_id = t.task_id
                           JOIN clients c ON t.client_id = c.client_id
                           WHERE e.helper_id = ?
                           ORDER BY e.payment_date DESC
                           LIMIT 10");
    $stmt->bind_param("i", $helper_id);
    $stmt->execute();
    $recent_payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Availability
    $stmt = $conn->prepare("SELECT day_of_week, start_time, end_time, is_available
                           FROM helper_availability
                           WHERE helper_id = ?
                           ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 
                                        'Wednesday', 'Thursday', 'Friday', 
                                        'Saturday', 'Sunday')");
    $stmt->bind_param("i", $helper_id);
    $stmt->execute();
    $days = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Format money values
    $today_earnings = number_format($today_earnings, 2);
    $weekly_earnings = number_format($weekly_earnings, 2);
    $monthly_earnings = number_format($monthly_earnings, 2);

    // Get Working Hours
    $stmt = $conn->prepare("SELECT day_of_week, start_time, end_time, is_available
                           FROM helper_availability
                           WHERE helper_id = ?
                           ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 
                                        'Wednesday', 'Thursday', 'Friday', 
                                        'Saturday', 'Sunday')");
    $stmt->bind_param("i", $helper_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $days = $result->fetch_all(MYSQLI_ASSOC);

    // If no availability records exist, create default array
    if (empty($days)) {
        $days = array();
        $default_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        foreach ($default_days as $day) {
            $days[] = array(
                'day_of_week' => $day,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'is_available' => false
            );
        }
    }

    // Set default response rate if not available
    $response_rate = 100; // You can modify this based on your business logic

} catch (Exception $e) {
    error_log($e->getMessage());
    // Set default values in case of database errors
    $today_earnings = 0;
    $tasks_completed = 0;
    $rating = 0;
    $response_rate = 0;
    $weekly_earnings = 0;
    $monthly_earnings = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Helper Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/helper_dashboard.css">
    <link rel="icon" type="image/x-con" href="../assets/favicon.ico">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script></body>
</head>
<body>
    <?php if (isset($error_message)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <nav>
        <div class="logo">HandyLink</div>
        <ul>
            <li><a href="helper_dashboard.php">Dashboard</a></li>
            <li><a href="#tasks">Tasks</a></li>
            <li><a href="#earnings">Earnings</a></li>
            <li><a href="#profile">Profile</a></li>
            <li><a href="#messages">Messages</a></li>
        </ul>
        <div class="user-menu">
            <span class="user-name"><?php echo 'Welcome! '.htmlspecialchars($helper_name); ?></span>
            <a href="../actions/logout.php">Logout</a>
        </div>
    </nav>

    <main>
        <section id="quick-stats">
            <div class="stat-card">
                <h3>Today's Earnings</h3>
                <p class="amount">GH₵<?php echo $today_earnings; ?></p>
            </div>
            <div class="stat-card">
                <h3>Tasks Completed</h3>
                <p class="count"><?php echo $tasks_completed; ?></p>
            </div>
            <div class="stat-card">
                <h3>Rating</h3>
                <p class="rating"><?php echo $rating; ?> / 5.0</p>
            </div>
            <div class="stat-card">
                <h3>Response Rate</h3>
                <p class="percentage"><?php echo $response_rate; ?>%</p>
            </div>
        </section>

        <section id="task-management">
            <h2>Task Management</h2>
            
            <div class="task-section">
                <h3>New Requests</h3>
                <div class="task-list">
                    <?php if (empty($new_requests)): ?>
                        <p>No new task requests.</p>
                    <?php else: ?>
                        <?php foreach($new_requests as $request): ?>
                        <div class="task-card">
                            <h4><?php echo htmlspecialchars($request['title']); ?></h4>
                            <p class="client">Client: <?php echo htmlspecialchars($request['client_name']); ?></p>
                            <p class="location">Location: <?php echo htmlspecialchars($request['location']); ?></p>
                            <p class="budget">Budget: $<?php echo number_format($request['budget'], 2); ?></p>
                            <div class="actions">
                                <button class="accept" data-task-id="<?php echo $request['task_id']; ?>">Accept</button>
                                <button class="decline" data-task-id="<?php echo $request['task_id']; ?>">Decline</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="task-section">
                <h3>Upcoming Tasks</h3>
                <div class="task-list">
                    <?php if (empty($upcoming_tasks)): ?>
                        <p>No upcoming tasks scheduled.</p>
                    <?php else: ?>
                        <?php foreach($upcoming_tasks as $task): ?>
                        <div class="task-card">
                            <h4><?php echo htmlspecialchars($task['title']); ?></h4>
                            <p class="date">Date: <?php echo date('M d, Y', strtotime($task['date'])); ?></p>
                            <p class="time">Time: <?php echo date('g:i A', strtotime($task['time'])); ?></p>
                            <p class="client">Client: <?php echo htmlspecialchars($task['client_name']); ?></p>
                            <p class="location">Location: <?php echo htmlspecialchars($task['location']); ?></p>
                            <div class="actions">
                                <button class="view-details" data-task-id="<?php echo $task['task_id']; ?>">View Details</button>
                                <button class="message-client" data-task-id="<?php echo $task['task_id']; ?>">Message Client</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section id="earnings">
            <h2>Earnings Overview</h2>
            <div class="earnings-cards">
                <div class="earnings-card">
                    <h3>Weekly Earnings</h3>
                    <p class="amount">GH₵<?php echo $weekly_earnings; ?></p>
                    <div class="earnings-chart">
                        <canvas id="weeklyChart"></canvas>
                    </div>
                </div>
                <div class="earnings-card">
                    <h3>Monthly Earnings</h3>
                    <p class="amount">GH₵<?php echo $monthly_earnings; ?></p>
                    <div class="earnings-chart">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="recent-payments">
                <h3>Recent Payments</h3>
                <?php if (empty($recent_payments)): ?>
                    <p>No recent payments to display.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Task</th>
                                <th>Client</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_payments as $payment): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                <td><?php echo htmlspecialchars($payment['task']); ?></td>
                                <td><?php echo htmlspecialchars($payment['client']); ?></td>
                                <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($payment['status'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <section id="availability">
            <h2>Availability Settings</h2>
            <div class="calendar-container">
            <div class="calendar-container">
                <div id="calendar"></div>
            </div>
            </div>
            <div class="working-hours">
                <h3>Working Hours</h3>
                <form id="working-hours-form">
                    <?php foreach($days as $day): ?>
                    <div class="day-schedule">
                        <label>
                            <input type="checkbox" name="working_days[]" 
                                   value="<?php echo htmlspecialchars($day['day_of_week']); ?>"
                                   <?php echo $day['is_available'] ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($day['day_of_week']); ?>
                        </label>
                        <select name="<?php echo $day['day_of_week']; ?>_start">
                            <?php
                            for ($hour = 0; $hour < 24; $hour++) {
                                $time = sprintf("%02d:00", $hour);
                                $selected = ($time === $day['start_time']) ? 'selected' : '';
                                echo "<option value=\"{$time}\" {$selected}>{$time}</option>";
                            }
                            ?>
                        </select>
                        to
                        <select name="<?php echo $day['day_of_week']; ?>_end">
                            <?php
                            for ($hour = 0; $hour < 24; $hour++) {
                                $time = sprintf("%02d:00", $hour);
                                $selected = ($time === $day['end_time']) ? 'selected' : '';
                                echo "<option value=\"{$time}\" {$selected}>{$time}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <?php endforeach; ?>
                    <button type="submit">Update Schedule</button>
                </form>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 HandyLink. All rights reserved.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
        // Handle task acceptance
        document.querySelectorAll('.accept').forEach(button => {
            button.addEventListener('click', function() {
                const taskId = this.dataset.taskId;
                if (confirm('Are you sure you want to accept this task?')) {
                    fetch('../actions/update_task_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            task_id: taskId,
                            action: 'accept'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the task card and show success message
                            this.closest('.task-card').remove();
                            showNotification('Task accepted successfully!', 'success');
                            // Refresh the page to update statistics
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showNotification(data.message || 'Error accepting task', 'error');
                        }
                    })
                    .catch(error => {
                        showNotification('Error accepting task', 'error');
                        console.error('Error:', error);
                    });
                }
            });
        });

        // Handle task decline
        document.querySelectorAll('.decline').forEach(button => {
            button.addEventListener('click', function() {
                const taskId = this.dataset.taskId;
                if (confirm('Are you sure you want to decline this task?')) {
                    fetch('../actions/update_task_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            task_id: taskId,
                            action: 'decline'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.closest('.task-card').remove();
                            showNotification('Task declined successfully!', 'success');
                        } else {
                            showNotification(data.message || 'Error declining task', 'error');
                        }
                    })
                    .catch(error => {
                        showNotification('Error declining task', 'error');
                        console.error('Error:', error);
                    });
                }
            });
        });

        // Handle view details button
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                const taskId = this.dataset.taskId;
                fetch(`../actions/get_task_details.php?task_id=${taskId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showTaskDetailsModal(data.task);
                        } else {
                            showNotification('Error loading task details', 'error');
                        }
                    })
                    .catch(error => {
                        showNotification('Error loading task details', 'error');
                        console.error('Error:', error);
                    });
            });
        });

        // Handle message client button
        document.querySelectorAll('.message-client').forEach(button => {
            button.addEventListener('click', function() {
                const taskId = this.dataset.taskId;
                // Open message modal or redirect to messaging page
                window.location.href = `messages.php?task_id=${taskId}`;
            });
        });

        // Handle working hours form submission
        document.getElementById('working-hours-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../actions/update_availability.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Availability updated successfully!', 'success');
                } else {
                    showNotification(data.message || 'Error updating availability', 'error');
                }
            })
            .catch(error => {
                showNotification('Error updating availability', 'error');
                console.error('Error:', error);
            });
        });

        // Notification function
        function showNotification(message, type = 'info') {
            // Create notification element if it doesn't exist
            let notification = document.getElementById('notification');
            if (!notification) {
                notification = document.createElement('div');
                notification.id = 'notification';
                document.body.appendChild(notification);
            }

            // Add styles based on type
            notification.className = `notification ${type}`;
            notification.textContent = message;

            // Show notification
            notification.style.display = 'block';
            
            // Hide after 3 seconds
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        // Task details modal function
        function showTaskDetailsModal(task) {
            // Create modal HTML
            const modalHTML = `
                <div id="taskDetailsModal" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>Task Details</h2>
                        <div class="task-details">
                            <p><strong>Title:</strong> ${task.title}</p>
                            <p><strong>Client:</strong> ${task.client_name}</p>
                            <p><strong>Date:</strong> ${task.date}</p>
                            <p><strong>Time:</strong> ${task.time}</p>
                            <p><strong>Location:</strong> ${task.location}</p>
                            <p><strong>Budget:</strong> $${task.budget}</p>
                            <p><strong>Description:</strong> ${task.description}</p>
                        </div>
                    </div>
                </div>
            `;

            // Add modal to page
            document.body.insertAdjacentHTML('beforeend', modalHTML);

            const modal = document.getElementById('taskDetailsModal');
            const closeBtn = modal.querySelector('.close');

            // Show modal
            modal.style.display = 'block';

            // Close modal when clicking X
            closeBtn.onclick = function() {
                modal.remove();
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                if (event.target === modal) {
                    modal.remove();
                }
            }
        }
    });

    // Add CSS for notifications and modal
    const style = document.createElement('style');
    style.textContent = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 4px;
            color: white;
            z-index: 1000;
            display: none;
        }
        .notification.success {
            background-color: #4CAF50;
        }
        .notification.error {
            background-color: #f44336;
        }
        .notification.info {
            background-color: #2196F3;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            width: 70%;
            max-width: 500px;
            border-radius: 4px;
            position: relative;
        }
        .close {
            position: absolute;
            right: 10px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
        }
        .task-details p {
            margin: 10px 0;
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>