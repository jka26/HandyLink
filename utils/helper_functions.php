<?php

// Create a file called update_helper_rating.php
function updateHelperRating($helper_id, $conn) {
    // Calculate average rating
    $stmt = $conn->prepare("
        SELECT AVG(rating) as average_rating 
        FROM reviews 
        WHERE helper_id = ?
    ");
    $stmt->bind_param("i", $helper_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $avg = $result->fetch_assoc()['average_rating'];

    // Update helper's avg_rating
    $stmt = $conn->prepare("
        UPDATE helpers 
        SET avg_rating = ? 
        WHERE helper_id = ?
    ");
    $stmt->bind_param("di", $avg, $helper_id);
    $stmt->execute();
}
?>