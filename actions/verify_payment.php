<?php
session_start();
require '../db/config.php';
header("Content-Type: application/json");

// Debug mode
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log the incoming request
    error_log("Payment verification request received: " . file_get_contents('php://input'));
    
    $input = json_decode(file_get_contents('php://input'), true);
    $reference = $input['reference'] ?? null;

    if (!$reference) {
        echo json_encode(['status' => false, 'message' => 'Reference not provided']);
        exit;
    }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $reference = $input['reference'] ?? null;

    if (!$reference) {
        echo json_encode(['status' => false, 'message' => 'Reference not provided']);
        exit;
    }

    // Paystack Secret Key
    $secretKey = 'sk_test_018fa4ccadc14b4ec7bf538d99b65691c7f20fd9';

    // Verify transaction
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.paystack.co/transaction/verify/" . $reference);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $secretKey"
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode === 200) {
        $result = json_decode($response, true);
        if ($result['status'] === true && $result['data']['status'] === 'success') {
            $amount = $result['data']['amount'] / 100; // Convert from kobo to GHS
            $email = $result['data']['customer']['email'];

            // Insert into payment_transactions table
            $stmt = $conn->prepare("
                INSERT INTO payment_transactions (reference, email, amount, status) 
                VALUES (?, ?, ?, 'success')
            ");
            $stmt->bind_param("ssd", $reference, $email, $amount);

            if ($stmt->execute()) {
                echo json_encode(['status' => true, 'message' => 'Payment verified successfully']);
            } else {
                echo json_encode(['status' => false, 'message' => 'Failed to record payment']);
            }
        } else {
            echo json_encode(['status' => false, 'message' => 'Transaction verification failed']);
        }
    } else {
        echo json_encode(['status' => false, 'message' => 'Failed to connect to Paystack API']);
    }

    // Log the PayStack response
    error_log("PayStack API Response: " . $response);
}
}
?>