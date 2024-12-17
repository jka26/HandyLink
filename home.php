<!DOCTYPE html>
<html>
    <head>
        <title>HandyLink</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="assets/home.css">
        <link rel="icon" type="image/x-con" href="../assets/favicon.ico">
    </head>
    <body>
        <div class="topnav">
            <h1>Handylink</h1>
            <a href="view/signup.php">Sign Up</a>
            <a href="view/login.php">Log In</a>
            <a href="home.php">Home</a>
            <a href="view/service.php">Services</a>
            <div class="button-container">
                <button onclick="window.location.href='view/helper.php'">Become A Helper</button>
            </div>
        </div>

        <div class="column">
            <h1>We've got all your <br> home tasks covered</h1>
            <div class="search-container">
                <form id="searchForm" action="view/search_results.php" method="GET">
                    <input type="text" 
                        id="searchInput" 
                        name="query" 
                        class="search-bar" 
                        placeholder="What do you need help with?">
                    <button type="submit" class="search-icon">🔍</button>
                </form>
            </div>
        </div>

        <div class="collage-container">
            <div class="collage">
                <img src="assets/collage.png" alt="Tasks Montage" width="100%">
            </div>
        </div>

        <div><h2>Popular Tasks</h2></div>
        <section class="popular-tasks">
            <div class="task-card" data-task-name="Home Cleaning">
                <img src="assets/clean.jpg" alt="home cleaning" class="task-image">
                <h3>Home Cleaning</h3>
                <?php 
                        include "db/config.php";

                        $home = "Home Cleaning";
                        $sql = $conn->prepare("SELECT fee_low from tasks where title = ?");
                        $sql->bind_param("s", $home);
                        if ($sql->execute()){
                            $result = $sql->get_result();
                            $row =  $result->fetch_assoc();

                            echo '<p>Reward starting at GHC'.$row['fee_low'].'</p>';
                        }
                        
                        $sql->close();
                ?>
            </div>
            <div class="task-card" data-task-name="Mount Items">
                <img src="assets/mount.jpg" alt="mount items" class="task-image">
                <h3>Mount Items</h3>
                <?php 
                        include "db/config.php";

                        $mount = "General Mounting";
                        $sql = $conn->prepare("SELECT fee_low from tasks where title = ?");
                        $sql->bind_param("s", $mount);
                        if ($sql->execute()){
                            $result = $sql->get_result();
                            $row =  $result->fetch_assoc();

                            echo '<p>Reward starting at GHC'.$row['fee_low'].'</p>';
                        }
                        
                        $sql->close();
                ?>
            </div>
            <div class="task-card" data-task-name="Minor Plumbing Repairs">
                <img src="assets/plumbing.jpg" alt="plumbing repairs" class="task-image">
                <h3>Minor Plumbing Repairs</h3>
                <?php 
                        include "db/config.php";

                        $plumbing = "Minor Plumbing Repairs";
                        $sql = $conn->prepare("SELECT fee_low from tasks where title = ?");
                        $sql->bind_param("s", $plumbing);
                        if ($sql->execute()){
                            $result = $sql->get_result();
                            $row =  $result->fetch_assoc();

                            echo '<p>Reward starting at GHC'.$row['fee_low'].'</p>';
                        }
                        
                        $sql->close();
                ?>
            </div>
            <div class="task-card" data-task-name="Moving">
                <img src="assets/Home-movers-furniture.png" alt="moving" class="task-image">
                <h3>Moving</h3>
                <?php 
                        include "db/config.php";

                        $moving = "Moving";
                        $sql = $conn->prepare("SELECT fee_low from tasks where title = ?");
                        $sql->bind_param("s", $moving);
                        if ($sql->execute()){
                            $result = $sql->get_result();
                            $row =  $result->fetch_assoc();

                            echo '<p>Reward starting at GHC'.$row['fee_low'].'</p>';
                        }
                        
                        $sql->close();
                ?>
            </div>
            <div class="task-card" data-task-name="Electrical Help">
                <img src="assets/homeowner-electrical.png" alt="electrical help" class="task-image">
                <h3>Electrical Help</h3>
                <?php 
                        include "db/config.php";

                        $electrical = "Electrical Help";
                        $sql = $conn->prepare("SELECT fee_low from tasks where title = ?");
                        $sql->bind_param("s", $electrical);
                        if ($sql->execute()){
                            $result = $sql->get_result();
                            $row =  $result->fetch_assoc();

                            echo '<p>Reward starting at GHC'.$row['fee_low'].'</p>';
                        }
                        
                        $sql->close();
                ?>
            </div>
            <div class="task-card" data-task-name="Assembly">
                <img src="assets/assembly_img.png" alt="assembly" class="task-image">
                <h3>Assembly</h3>
                <?php 
                        include "db/config.php";

                        $assembly = "Furniture Assembly";
                        $sql = $conn->prepare("SELECT fee_low from tasks where title = ?");
                        $sql->bind_param("s", $assembly);
                        if ($sql->execute()){
                            $result = $sql->get_result();
                            $row =  $result->fetch_assoc();

                            echo '<p>Reward starting at GHC'.$row['fee_low'].'</p>';
                        }
                        
                        $sql->close();
                ?>
            </div>
            <div class="task-card" data-task-name="Gardening">
                <img src="assets/gardening.png" alt="gardening" class="task-image">
                <h3>Gardening</h3>
                <?php 
                        include "db/config.php";

                        $garden = "Gardening";
                        $sql = $conn->prepare("SELECT fee_low from tasks where title = ?");
                        $sql->bind_param("s", $garden);
                        if ($sql->execute()){
                            $result = $sql->get_result();
                            $row =  $result->fetch_assoc();

                            echo '<p>Reward starting at GHC'.$row['fee_low'].'</p>';
                        }
                        
                        $sql->close();
                ?>
            </div>
        </section>

        <section>
        <footer class="footer">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Contact Us</h4>
                    <p><i class="fas fa-envelope"></i> Email: jarhin004@gmail.com</p>
                    <p><i class="fas fa-phone"></i> Phone: +233 57 842 3117</p>
                </div>
                
                <div class="footer-section">
                    <h4>Additional Information</h4>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Follow Us</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 Handylink. All rights reserved.</p>
            </div>
        </footer>
        </section>
        
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const searchForm = document.getElementById('searchForm');
            
            // Create suggestions container
            const suggestionsDiv = document.createElement('div');
            suggestionsDiv.className = 'search-suggestions';
            searchInput.parentNode.appendChild(suggestionsDiv);

            searchInput.addEventListener('input', function() {
                const query = this.value;
                
                if (query.length >= 2) {
                    fetch(`ajax/search_suggestions.php?query=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            suggestionsDiv.innerHTML = '';
                            data.forEach(task => {
                                const div = document.createElement('div');
                                div.className = 'suggestion-item';
                                div.textContent = task.title;
                                div.addEventListener('click', () => {
                                    searchInput.value = task.title;
                                    suggestionsDiv.innerHTML = '';
                                    searchForm.submit();
                                });
                                suggestionsDiv.appendChild(div);
                            });
                        });
                } else {
                    suggestionsDiv.innerHTML = '';
                }
            });

            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                    suggestionsDiv.innerHTML = '';
                }
            });

            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const query = searchInput.value.trim();
                if (query) {
                    window.location.href = `view/search_results.php?query=${encodeURIComponent(query)}`;
                }
            });
        });


        </script>
    </body>
</html>