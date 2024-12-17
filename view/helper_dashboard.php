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

    // Weekly earnings data - last 7 days
    $stmt = $conn->prepare("
    SELECT DATE(payment_date) as date, SUM(amount) as daily_amount 
    FROM earnings 
    WHERE helper_id = ? 
    AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(payment_date)
    ORDER BY date ASC
    ");
    $stmt->bind_param("i", $helper_id);
    $stmt->execute();
    $weekly_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Monthly earnings data - current month by week
    $stmt = $conn->prepare("
        SELECT 
            CONCAT('Week ', FLOOR(DATEDIFF(payment_date, DATE_SUB(payment_date, INTERVAL DAYOFMONTH(payment_date)-1 DAY))/7) + 1) as week,
            SUM(amount) as weekly_amount
        FROM earnings 
        WHERE helper_id = ? 
        AND MONTH(payment_date) = MONTH(CURDATE())
        AND YEAR(payment_date) = YEAR(CURDATE())
        GROUP BY FLOOR(DATEDIFF(payment_date, DATE_SUB(payment_date, INTERVAL DAYOFMONTH(payment_date)-1 DAY))/7)
        ORDER BY week ASC
    ");
    $stmt->bind_param("i", $helper_id);
    $stmt->execute();
    $monthly_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Prepare data for JavaScript
    $weekly_labels = [];
    $weekly_amounts = [];
    $current_date = new DateTime();

    // Initialize arrays for the last 7 days
    for ($i = 6; $i >= 0; $i--) {
        $date = (new DateTime())->sub(new DateInterval("P{$i}D"));
        $weekly_labels[] = $date->format('D');
        $weekly_amounts[] = 0;
    }

    // Fill in actual data
    foreach ($weekly_data as $data) {
        $date = new DateTime($data['date']);
        $index = 6 - $current_date->diff($date)->days;
        if ($index >= 0 && $index < 7) {
            $weekly_amounts[$index] = floatval($data['daily_amount']);
        }
    }

    // Prepare monthly data
    $monthly_labels = [];
    $monthly_amounts = [];
    $week_count = ceil(date('t') / 7); // Get number of weeks in current month

    // Initialize arrays for all weeks in the month
    for ($i = 1; $i <= $week_count; $i++) {
        $monthly_labels[] = "Week $i";
        $monthly_amounts[] = 0;
    }

    // Fill in actual monthly data
    foreach ($monthly_data as $data) {
        $week_num = intval(substr($data['week'], 5)) - 1; // Extract week number
        if ($week_num < count($monthly_amounts)) {
            $monthly_amounts[$week_num] = floatval($data['weekly_amount']);
        }
    }

    // Convert data to JSON for JavaScript
    $weekly_chart_data = json_encode([
        'labels' => $weekly_labels,
        'amounts' => $weekly_amounts
    ]);

    $monthly_chart_data = json_encode([
        'labels' => $monthly_labels,
        'amounts' => $monthly_amounts
    ]);

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
            <li><a href="helper_profile.php">Create Your Profile</a></li>
            <li><a href="#profile">Profile</a></li>
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
                <div id="calendar"></div>
            </div>
            <div class="working-hours">
                <h3>Working Hours</h3>
                <form id="working-hours-form" method="POST" action="../actions/update_availability.php">
                    <div class="availability-grid">
                        <?php
                        $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        foreach ($weekdays as $day):
                            // Find the availability data for this day
                            $day_data = null;
                            foreach ($days as $availability) {
                                if ($availability['day_of_week'] === $day) {
                                    $day_data = $availability;
                                    break;
                                }
                            }
                            
                            // Set default values if no data found
                            if (!$day_data) {
                                $day_data = [
                                    'day_of_week' => $day,
                                    'start_time' => '09:00',
                                    'end_time' => '17:00',
                                    'is_available' => false
                                ];
                            }
                        ?>
                        <div class="day-schedule">
                            <div class="day-header">
                                <input type="checkbox" name="available_days[]" value="<?php echo $day; ?>" 
                                    <?php echo $day_data['is_available'] ? 'checked' : ''; ?>>
                                <label><?php echo $day; ?></label>
                            </div>
                            <div class="time-slots">
                                <input type="time" name="<?php echo strtolower($day); ?>_start" 
                                    value="<?php echo $day_data['start_time']; ?>"
                                    <?php echo !$day_data['is_available'] ? 'disabled' : ''; ?>>
                                <span>to</span>
                                <input type="time" name="<?php echo strtolower($day); ?>_end" 
                                    value="<?php echo $day_data['end_time']; ?>"
                                    <?php echo !$day_data['is_available'] ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="submit-btn">Update Schedule</button>
                </form>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 HandyLink. All rights reserved.</p>
    </footer>

    <style>
.availability-grid {
    display: grid;
    gap: 1rem;
}

.day-schedule {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 4px;
}

.day-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.time-slots {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

input[type="time"] {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 130px;
}

.submit-btn {
    width: 100%;
    padding: 1rem;
    background-color: #1e3932;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    margin-top: 2rem;
    transition: background-color 0.2s;
}

.submit-btn:hover {
    background-color: #152a25;
}

.earnings-chart {
    height: 200px;
    margin-top: 1rem;
}

.earnings-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.earnings-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>


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
        
            // Handle checkbox changes
            document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const day = this.value.toLowerCase();
                    const timeInputs = document.querySelectorAll(`input[name="${day}_start"], input[name="${day}_end"]`);
                    timeInputs.forEach(input => {
                        input.disabled = !this.checked;
                    });
                });
            });

            // Handle form submission
            document.getElementById('working-hours-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData();

                // Get all days
                const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                
                // Add checked days to available_days array
                const checkedDays = Array.from(document.querySelectorAll('input[name="available_days[]"]:checked')).map(input => input.value);
                checkedDays.forEach(day => {
                    formData.append('available_days[]', day);
                    // Add the corresponding time values
                    formData.append(`${day.toLowerCase()}_start`, document.querySelector(`input[name="${day.toLowerCase()}_start"]`).value);
                    formData.append(`${day.toLowerCase()}_end`, document.querySelector(`input[name="${day.toLowerCase()}_end"]`).value);
                });

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
                    console.error('Error:', error);
                    showNotification('Error updating availability', 'error');
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

    document.addEventListener('DOMContentLoaded', function() {
        // Parse the PHP data
        const weeklyData = <?php echo $weekly_chart_data; ?>;
        const monthlyData = <?php echo $monthly_chart_data; ?>;

        // Weekly Chart
        const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
        new Chart(weeklyCtx, {
            type: 'bar',
            data: {
                labels: weeklyData.labels,
                datasets: [{
                    label: 'Daily Earnings (GH₵)',
                    data: weeklyData.amounts,
                    backgroundColor: 'rgba(30, 57, 50, 0.7)',
                    borderColor: 'rgba(30, 57, 50, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'GH₵' + value.toFixed(2);
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'GH₵' + context.raw.toFixed(2);
                            }
                        }
                    }
                }
            }
        });

        // Monthly Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.labels,
                datasets: [{
                    label: 'Weekly Earnings (GH₵)',
                    data: monthlyData.amounts,
                    backgroundColor: 'rgba(30, 57, 50, 0.1)',
                    borderColor: 'rgba(30, 57, 50, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'GH₵' + value.toFixed(2);
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'GH₵' + context.raw.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
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