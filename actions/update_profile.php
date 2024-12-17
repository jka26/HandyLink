<?php
session_start();
include "../db/config.php";

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Get PUT data
$_PUT = json_decode(file_get_contents('php://input'), true);

$client_id = $_SESSION['client_id'];
$first_name = trim($_PUT['first_name']);
$last_name = trim($_PUT['last_name']);
$email = trim($_PUT['email']);
$phone_number = trim($_PUT['phone_number']);
$location = trim($_PUT['location']);

try {
    // Log the received data
    error_log("Received data: " . print_r($_PUT, true));

    // Update the user profile
    $stmt = $conn->prepare("
        UPDATE clients 
        SET first_name = ?, 
            last_name = ?, 
            email = ?, 
            phone_number = ?,
            location = ?
        WHERE client_id = ?
    ");
    
    $stmt->bind_param("sssssi", 
        $first_name, 
        $last_name, 
        $email,
        $phone_number,
        $location,
        $client_id
    );
    
    if ($stmt->execute()) {
        // Update session variables
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Profile updated successfully'
        ]);
    } else {
        throw new Exception("Error updating profile");
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while updating your profile'
    ]);
}
?>