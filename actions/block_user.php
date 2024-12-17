<?php
session_start();
include "../db/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $data['user_id'];
    $user_type = $data['user_type'];
    
    try {
        // Determine which table to update based on user type
        $table = ($user_type === 'client') ? 'clients' : 'helpers';
        $id_field = ($user_type === 'client') ? 'client_id' : 'helper_id';
        
        // Update user status
        $stmt = $conn->prepare("UPDATE $table SET status = 'blocked' WHERE $id_field = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User blocked successfully']);
        } else {
            throw new Exception("Error blocking user");
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Error blocking user: ' . $e->getMessage()
        ]);
    }
}
?>