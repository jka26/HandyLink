<?php
session_start();
include "../db/config.php";

// Check if client is logged in - uncomment this
if (!isset($_SESSION['client_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch client details - uncomment this
$stmt = $conn->prepare("SELECT * FROM clients WHERE client_id = ?");
$stmt->bind_param("i", $_SESSION['client_id']);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Account | HandyLink</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/account_dashboard.css">
    <link rel="icon" type="image/x-con" href="../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <!-- Top Navigation -->
    <div class="topnav">
        <h1>HandyLink</h1>
        <a href="service.php">Book a Task</a>
        <a href="#" class="active">Account</a>
    </div>

    <div class="container">
        <!-- Side Menu -->
        <div class="sidemenu">
            <div class="profile-brief">
                <div class="profile-image">
                    <i class="fas fa-user"></i>
                </div>
                <h3><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></h3>
            </div>
            
            <nav>
                <a href="#" class="menu-item active" data-section="profile">
                    <i class="fas fa-user-circle"></i> Profile
                </a>
                <a href="#" class="menu-item" data-section="tasks">
                    <i class="fas fa-tasks"></i> My Tasks
                </a>
                <a href="#" class="menu-item" data-section="favorites">
                    <i class="fas fa-heart"></i> Favorite Helpers
                </a>
                <a href="#" class="menu-item" data-section="payments">
                    <i class="fas fa-credit-card"></i> Payment Methods
                </a>
                <a href="#" class="menu-item" data-section="notifications">
                    <i class="fas fa-bell"></i> Notifications
                </a>
                <a href="../actions/logout.php" class="menu-item logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            <div id="content-area">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Load default section (Profile) on page load
        loadSection('profile');

        // Add click handlers to menu items
        document.querySelectorAll('.menu-item').forEach(item => {
            // Skip the logout link from the section loading logic
            if (!item.classList.contains('logout-link')) {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Update active state
                    document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                    this.classList.add('active');

                    // Load content
                    loadSection(this.dataset.section);
                });
            }
        });
    });

    function loadSection(section) {
        fetch(`../account_sections/${section}.php`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Section not found');
                }
                return response.text();
            })
            .then(html => {
                document.getElementById('content-area').innerHTML = html;
                // Only initialize form handlers after content is loaded
                if (document.querySelector('form')) {
                    initializeFormHandlers();
                }
            })
            .catch(error => {
                console.error('Error loading section:', error);
                document.getElementById('content-area').innerHTML = `
                    <div class="error-message">
                        <h3>Content Unavailable</h3>
                        <p>Please try again later</p>
                    </div>
                `;
            });
    }

    function initializeFormHandlers() {
        const form = document.querySelector('#profile-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Create an object from form data
                const formData = {};
                new FormData(form).forEach((value, key) => {
                    formData[key] = value;
                });

                // Send as JSON
                fetch('../actions/update_profile.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Profile updated successfully!', 'success');
                        // Update displayed name if it changed
                        if (formData.first_name || formData.last_name) {
                            const nameElement = document.querySelector('.profile-brief h3');
                            if (nameElement) {
                                nameElement.textContent = `${formData.first_name} ${formData.last_name}`;
                            }
                        }
                    } else {
                        showNotification(data.message || 'Error updating profile', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error saving changes', 'error');
                });
            });
        }
    }

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    </script>
</body>
</html>