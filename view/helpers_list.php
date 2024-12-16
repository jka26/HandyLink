<?php
session_start();
include "../db/config.php";

// Get category from URL parameter and trim any whitespace
$category = isset($_GET['category']) ? trim($_GET['category']) : null;

// Debug information
echo "Category from URL: " . $category . "<br>";

// Get helpers for the selected category
$stmt = $conn->prepare("
    SELECT h.helper_id, h.first_name, h.last_name, h.phone_number, h.location, 
           h.avg_rating, h.profile_photo, h.category
    FROM helpers h
    WHERE h.category = ?
");

$stmt->bind_param("s", $category);
$stmt->execute();
$result = $stmt->get_result();

// Debug the query
echo "Number of rows returned: " . $result->num_rows . "<br>";

// Fetch the results
$helpers = $result->fetch_all(MYSQLI_ASSOC);

// Show all categories in database
$cat_query = $conn->query("SELECT DISTINCT category FROM helpers");
echo "<br>Available categories in database:<br>";
while($row = $cat_query->fetch_assoc()) {
    echo "'" . $row['category'] . "'<br>"; // Added quotes to see any hidden spaces
}

// Debug helper data
if (!empty($helpers)) {
    echo "<br>First helper data:<br>";
    print_r($helpers[0]);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>HandyLink - Available Helpers</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/helpers_list.css">
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

    <div class="main-container">
        <div class="header">
            <h2>Available Helpers for <?php echo htmlspecialchars($category); ?></h2>
            <p><?php echo count($helpers); ?> helpers found</p>
        </div>

        <div class="helpers-grid">
            <?php if (empty($helpers)): ?>
                <div class="no-helpers">
                    <p>No helpers available for this category at the moment.</p>
                </div>
            <?php else: ?>
                <?php foreach($helpers as $helper): ?>
                    <div class="helper-card">
                        <div class="helper-photo">
                            <?php if ($helper['profile_photo']): ?>
                                <img src="<?php echo htmlspecialchars($helper['profile_photo']); ?>" alt="Helper photo">
                            <?php else: ?>
                                <div class="placeholder-photo">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="helper-info">
                            <h3><?php echo htmlspecialchars($helper['first_name'] . ' ' . $helper['last_name']); ?></h3>
                            
                            <div class="rating">
                                <?php
                                $rating = round($helper['avg_rating'], 1);
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i - 0.5 <= $rating) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                                <span>(<?php echo $rating; ?>)</span>
                            </div>

                            <p class="location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($helper['location']); ?>
                            </p>
                            
                            <p class="phone">
                                <i class="fas fa-phone"></i>
                                <?php echo htmlspecialchars($helper['phone_number']); ?>
                            </p>

                            <?php
                            // Get helper's availability
                            $stmt = $conn->prepare("
                                SELECT day_of_week, start_time, end_time 
                                FROM helper_availability 
                                WHERE helper_id = ? AND is_available = 1
                                ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
                            ");
                            $stmt->bind_param("i", $helper['helper_id']);
                            $stmt->execute();
                            $availability = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            ?>

                            <div class="availability">
                                <h4>Available Times:</h4>
                                <?php foreach($availability as $time): ?>
                                    <p>
                                        <?php 
                                        echo htmlspecialchars($time['day_of_week']) . ': ' . 
                                             date('g:i A', strtotime($time['start_time'])) . ' - ' . 
                                             date('g:i A', strtotime($time['end_time']));
                                        ?>
                                    </p>
                                <?php endforeach; ?>
                            </div>

                            <div class="actions">
                                <a href="review.php?helper_id=<?php echo $helper['helper_id']; ?>" class="reviews-btn">
                                    View Reviews
                                </a>
                                <button class="book-btn" onclick="bookHelper(<?php echo $helper['helper_id']; ?>)">
                                    Book Now
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function bookHelper(helperId) {
        // Implement booking functionality
        window.location.href = `booking.php?helper_id=${helperId}&category=<?php echo urlencode($category); ?>`;
    }
    </script>
</body>
</html>