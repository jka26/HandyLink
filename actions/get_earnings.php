<?php
include "../db/config.php";

if(isset($_GET['category'])) {
    $category = $_GET['category'];
    
    $sql = $conn->prepare("SELECT fee_low, fee_high FROM tasks WHERE title = ?");
    $sql->bind_param("s", $category);
    
    if ($sql->execute()) {
        $result = $sql->get_result();
        if ($row = $result->fetch_assoc()) {
            $response = array(
                'success' => true,
                'fee_low' => $row['fee_low'],
                'fee_high' => $row['fee_high']
            );
        } else {
            $response = array('success' => false, 'message' => 'Category not found');
        }
    } else {
        $response = array('success' => false, 'message' => 'Query failed');
    }
    
    $sql->close();
    $conn->close();
    
    header('Content-Type: application/json');
    echo json_encode($response);
}
?>