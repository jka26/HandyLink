<!DOCTYPE html>
<html>
<head>
    <title>HandyLink - Become a Helper</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/service.css">
    <link rel="icon" type="image/x-con" href="../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="topnav">
            <h1>Handylink</h1>
            <a href="../actions/logout.php">Logout</a>
            <a href="#">Account</a>
            <a href="#">My Tasks</a>
            <a href="service.php">Book a Task</a>
            <a href="#">Get GH₵25</a>
    </div>

    <div class="booking-container">
        <h1>Book Your Next Task</h1>
        
        <div class="search-container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Describe one task, e.g. fix my leaky pipes">
                
                <!-- Dropdown panel -->
                <div class="search-dropdown" id="searchDropdown">
                    <p class="search-instruction">Enter a few words above to start booking</p>
                    
                    <div class="projects-header">
                        <span>Popular Projects</span>
                        <span>Project GH₵</span>
                    </div>
                    
                    <div class="projects-list">
                        <?php
                        include "../db/config.php";
                        
                        // Array of task titles
                        $tasks = array(
                            "Home Cleaning",
                            "Furniture Assembly",
                            "Moving",
                            "Electrical Help",
                            "Gardening",
                            "Minor Plumbing Repairs",
                            "General Mounting"
                        );

                        foreach ($tasks as $task) {
                            // Prepare and execute query for each task
                            $sql = $conn->prepare("SELECT fee_low, fee_high FROM tasks WHERE title = ?");
                            $sql->bind_param("s", $task);
                            
                            if ($sql->execute()) {
                                $result = $sql->get_result();
                                if ($row = $result->fetch_assoc()) {
                                    ?>
                                    <div class="project-item">
                                        <span class="project-name"><?php echo $task; ?></span>
                                        <div class="project-price">
                                            <span class="avg-label">Avg. Project:</span>
                                            <span class="price-range">GH₵<?php echo $row['fee_low']; ?> – GH₵<?php echo $row['fee_high']; ?></span>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            $sql->close();
                        }
                        $conn->close();
                        ?>
                    </div>
                </div>
            </div>
        </div><br>

        <div class="quick-tasks">
            <button class="task-button">Furniture Assembly</button>
            <button class="task-button">Moving</button>
            <button class="task-button">Truck Assisted Help Moving</button>
        </div>

        <div class="quick-tasks">
            <button class="task-button">Electrical Help</button>
            <button class="task-button">Gardening</button>
            <button class="task-button">General Mounting</button>
        </div>

        <div class="quick-tasks">
            <button class="task-button">Cleaning</button>
            <button class="task-button secondary">See More</button>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const dropdown = document.getElementById('searchDropdown');

        // Show dropdown when clicking the search input
        searchInput.addEventListener('click', function() {
            dropdown.style.display = 'block';
        });

        // Hide dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

        // Handle project item clicks
        const projectItems = document.querySelectorAll('.project-item');
        projectItems.forEach(item => {
            item.addEventListener('click', function() {
                const projectName = this.querySelector('.project-name').textContent;
                searchInput.value = projectName;
                dropdown.style.display = 'none';
                // Redirect or handle the selection as needed
            });
        });
    });
    </script>
</body>
</html>