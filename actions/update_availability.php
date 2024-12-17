<?php
session_start();
include "../db/config.php";

// Check if helper is logged in
if (!isset($_SESSION['helper_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$helper_id = $_SESSION['helper_id'];
$available_days = $_POST['available_days'] ?? [];

try {
    // Start transaction
    $conn->begin_transaction();

    // First, remove all existing availability for this helper
    $stmt = $conn->prepare("DELETE FROM helper_availability WHERE helper_id = ?");
    $stmt->bind_param("i", $helper_id);
    $stmt->execute();

    // Insert new availability for each selected day
    if (!empty($available_days)) {
        $stmt = $conn->prepare("
            INSERT INTO helper_availability 
            (helper_id, day_of_week, start_time, end_time, is_available) 
            VALUES (?, ?, ?, ?, 1)
        ");

        foreach ($available_days as $day) {
            $start_time = $_POST[strtolower($day) . '_start'];
            $end_time = $_POST[strtolower($day) . '_end'];

            // Validate time inputs
            if (!empty($start_time) && !empty($end_time)) {
                if ($start_time >= $end_time) {
                    throw new Exception("Invalid time range for $day");
                }

                $stmt->bind_param("isss", 
                    $helper_id,
                    $day,
                    $start_time,
                    $end_time
                );
                $stmt->execute();
            }
        }
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Availability updated successfully'
    ]);

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>