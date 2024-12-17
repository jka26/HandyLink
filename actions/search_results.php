<?php
include "../db/config.php";

$query = isset($_GET['query']) ? $_GET['query'] : '';
$searchTerm = "%$query%";

$sql = $conn->prepare("SELECT * FROM tasks WHERE title LIKE ? OR description LIKE ?");
$sql->bind_param("ss", $searchTerm, $searchTerm);
$sql->execute();
$result = $sql->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Results - HandyLink</title>
    <link rel="stylesheet" href="../assets/home.css">
</head>
<body>
    <!-- Include your navigation -->
    <div class="search-results">
        <h2>Search Results for "<?php echo htmlspecialchars($query); ?>"</h2>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="task-grid">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="task-card">
                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                        <p>Reward starting at GHC<?php echo htmlspecialchars($row['fee_low']); ?></p>
                        <a href="book_task.php?task=<?php echo urlencode($row['title']); ?>" class="book-button">Book Now</a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No tasks found matching your search.</p>
        <?php endif; ?>
    </div>
</body>
</html>