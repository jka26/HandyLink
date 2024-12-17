<?php
session_start();
include "../db/config.php";

// Get all tasks for the client with helper application information
$stmt = $conn->prepare("
    SELECT t.*, 
           COUNT(ta.helper_id) as total_applications,
           GROUP_CONCAT(
               CASE 
                   WHEN ta.status = 'accepted' OR ta.status = 'completed' 
                   THEN CONCAT(h.first_name, ' ', h.last_name) 
               END
           ) as assigned_helper,
           GROUP_CONCAT(ta.status) as application_statuses
    FROM tasks t
    LEFT JOIN task_applications ta ON t.task_id = ta.task_id
    LEFT JOIN helpers h ON ta.helper_id = h.helper_id
    WHERE t.client_id = ?
    GROUP BY t.task_id
    
");

$stmt->bind_param("i", $_SESSION['client_id']);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="section-content">
    <h2>My Tasks</h2>
    
    <?php if (empty($tasks)): ?>
        <p class="no-tasks">You haven't posted any tasks yet.</p>
    <?php else: ?>
        <div class="tasks-list">
            <?php foreach($tasks as $task): ?>
                <div class="task-card">
                    <div class="task-header">
                        <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                        <span class="status <?php echo $task['status']; ?>">
                            <?php echo ucfirst($task['status']); ?>
                        </span>
                    </div>
                    
                    <div class="task-details">
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($task['category']); ?></p>
                        <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($task['task_date'])); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($task['location']); ?></p>
                        <p><strong>Budget:</strong> GH₵<?php echo number_format($task['budget'], 2); ?></p>
                        
                        <?php if ($task['assigned_helper']): ?>
                            <p><strong>Assigned Helper:</strong> <?php echo htmlspecialchars($task['assigned_helper']); ?></p>
                        <?php endif; ?>
                        
                        <p><strong>Applications:</strong> <?php echo $task['total_applications']; ?> helper(s)</p>
                    </div>
                    
                    <div class="task-actions">
                        <button onclick="viewTaskDetails(<?php echo $task['task_id']; ?>)" class="view-btn">
                            View Details
                        </button>
                        
                        <?php if ($task['total_applications'] > 0 && $task['status'] == 'pending'): ?>
                            <button onclick="viewApplications(<?php echo $task['task_id']; ?>)" class="applications-btn">
                                View Applications
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($task['status'] == 'completed'): ?>
                            <button onclick="leaveReview(<?php echo $task['task_id']; ?>)" class="review-btn">
                                Leave Review
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function viewTaskDetails(taskId) {
    // Open modal with task details
    fetch(`get_task_details.php?task_id=${taskId}`)
        .then(response => response.json())
        .then(data => {
            // Show task details in modal
            showTaskDetailsModal(data);
        });
}

function viewApplications(taskId) {
    // Open modal with helper applications
    fetch(`get_task_applications.php?task_id=${taskId}`)
        .then(response => response.json())
        .then(data => {
            // Show applications in modal
            showApplicationsModal(data);
        });
}

function leaveReview(taskId) {
    // Redirect to review page or open review modal
    window.location.href = `review.php?task_id=${taskId}`;
}
</script>

<style>
.task-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.status {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
}

.status.pending {
    background-color: #fff3cd;
    color: #856404;
}

.status.in_progress {
    background-color: #cce5ff;
    color: #004085;
}

.status.completed {
    background-color: #d4edda;
    color: #155724;
}

.task-details p {
    margin-bottom: 0.5rem;
    color: #666;
}

.task-actions {
    margin-top: 1rem;
    display: flex;
    gap: 1rem;
}

.task-actions button {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.875rem;
}

.view-btn {
    background-color: #1e3932;
    color: white;
}

.applications-btn {
    background-color: #0a5930;
    color: white;
}

.review-btn {
    background-color: #4CAF50;
    color: white;
}
</style>