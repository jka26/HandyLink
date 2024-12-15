<?php
include "../db/config.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone_number = trim($_POST['phone_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $location = trim($_POST['location']);
    $category = trim($_POST['category']);
    $errors = [];

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT helper_id FROM helpers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Email already registered";
    }
    $stmt->close();

    // Validate password
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if (!preg_match("/[A-Z]/", $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match("/[0-9]/", $password)) {
        $errors[] = "Password must contain at least one number";
    }
    if (!preg_match("/[!@#$%^&*]/", $password)) {
        $errors[] = "Password must contain at least one special character";
    }

    // Validate phone number (basic validation, adjust as needed)
    if (!preg_match("/^\+?[1-9]\d{1,14}$/", $phone_number)) {
        $errors[] = "Invalid phone number format";
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into helpers table
        $stmt = $conn->prepare("INSERT INTO helpers (first_name, last_name, email, phone_number, password, location, category) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $first_name, $last_name, $email, $phone_number, $hashed_password, $location, $category);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Registration failed: ' . $conn->error
            ]);
        }
        $stmt->close();
    } else {
        echo json_encode([
            'success' => false,
            'message' => implode("\n", $errors)
        ]);
    }

    $conn->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>