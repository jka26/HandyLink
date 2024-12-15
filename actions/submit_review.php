<?php
session_start();
include("../db/config.php");
include("../utils/helper_functions.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $helper_id = $_POST['helper_id'];
    $client_id = $_SESSION['client_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];

    try {
        // First check if user has already reviewed this helper
        $stmt = $conn->prepare("
            SELECT review_id 
            FROM reviews 
            WHERE helper_id = ? AND client_id = ?
        ");
        $stmt->bind_param("ii", $helper_id, $client_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update existing review
            $stmt = $conn->prepare("
                UPDATE reviews 
                SET rating = ?, comment = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE helper_id = ? AND client_id = ?
            ");
            $stmt->bind_param("isii", $rating, $comment, $helper_id, $client_id);
        } else {
            // Insert new review
            $stmt = $conn->prepare("
                INSERT INTO reviews (helper_id, client_id, rating, comment) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("iiis", $helper_id, $client_id, $rating, $comment);
        }

        if ($stmt->execute()) {
            // Update helper's average rating
            updateHelperRating($helper_id, $conn);
            
            header("Location: ../view/review.php?helper_id=" . $helper_id . "&success=1");
        } else {
            throw new Exception("Error submitting review");
        }

    } catch (Exception $e) {
        error_log($e->getMessage());
        header("Location: ../view/review.php?helper_id=" . $helper_id . "&error=1");
    }
    exit();
}
?>