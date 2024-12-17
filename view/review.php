<?php
session_start();
include("../db/config.php");
include("../utils/helper_functions.php");

// Fetch helper details
$helper_id = isset($_GET['helper_id']) ? $_GET['helper_id'] : null;
if (!$helper_id) {
    header("Location: service.php");
    exit();
}

// Get helper info
$stmt = $conn->prepare("SELECT first_name, last_name, avg_rating FROM helpers WHERE helper_id = ?");
$stmt->bind_param("i", $helper_id);
$stmt->execute();
$helper = $stmt->get_result()->fetch_assoc();

// Get existing reviews
$stmt = $conn->prepare("
    SELECT r.*, c.first_name, c.last_name 
    FROM reviews r 
    JOIN clients c ON r.client_id = c.client_id 
    WHERE r.helper_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $helper_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reviews | HandyLink</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/review.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav>
        <div class="logo">HandyLink</div>
        <div class="nav-links">
            <a href="service.php">Home</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
        </div>
    </nav>

    <main>
        <div class="review-container">
            <div class="helper-info">
                <h1><?php echo htmlspecialchars($helper['first_name'] . ' ' . $helper['last_name']); ?></h1>
                <div class="rating-summary">
                    <div class="average-rating">
                        <span class="rating-number"><?php echo number_format($helper['avg_rating'], 1); ?></span>
                        <div class="stars">
                            <?php
                            $rating = $helper['avg_rating'];
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
                        </div>
                        <span class="review-count"><?php echo count($reviews); ?> reviews</span>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['client_id'])): ?>
            <div class="write-review">
                <h2>Write a Review</h2>
                <form id="reviewForm" action="../actions/submit_review.php" method="POST">
                    <input type="hidden" name="helper_id" value="<?php echo $helper_id; ?>">
                    <div class="rating-input">
                        <span>Your Rating:</span>
                        <div class="star-rating">
                            <?php for($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" />
                            <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <textarea name="comment" placeholder="Share your experience..." required></textarea>
                    </div>
                    <button type="submit">Submit Review</button>
                </form>
            </div>
            <?php endif; ?>

            <div class="reviews-list">
                <h2>Reviews</h2>
                <?php if (empty($reviews)): ?>
                    <p class="no-reviews">No reviews yet. Be the first to leave a review!</p>
                <?php else: ?>
                    <?php foreach($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <span class="reviewer-name">
                                        <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                    </span>
                                    <span class="review-date">
                                        <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="review-rating">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $review['rating']) {
                                            echo '<i class="fas fa-star"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="review-content">
                                <?php echo htmlspecialchars($review['comment']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 HandyLink. All rights reserved.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('reviewForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const rating = form.querySelector('input[name="rating"]:checked');
                    if (!rating) {
                        e.preventDefault();
                        alert('Please select a rating');
                    }
                });
            }
        });
    </script>
</body>
</html>