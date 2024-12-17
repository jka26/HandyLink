<?php
header('Content-Type: application/json');
include "../db/config.php";
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

function return_json_error($message) {
    echo json_encode([
        'success' => false,
        'errors' => [$message]
    ]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    return_json_error('Invalid request method');
}

// Sanitize and retrieve form data
$first_name = trim(filter_var($_POST['first_name'], FILTER_SANITIZE_STRING));
$last_name = trim(filter_var($_POST['last_name'], FILTER_SANITIZE_STRING));
$email = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$phone_number = trim(filter_var($_POST['phone_number'], FILTER_SANITIZE_STRING));
$location = trim(filter_var($_POST['location'], FILTER_SANITIZE_STRING));

$errors = [];

// Validation checks
if (empty($first_name) || empty($last_name)) {
    $errors[] = "Name fields cannot be empty";
}

if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format";
} else {
    $stmt = $conn->prepare("SELECT client_id FROM clients WHERE email = ?");
    if (!$stmt) {
        return_json_error("Database error: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "Email is already registered";
    }
    $stmt->close();
}

// Password validation
if (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters long";
}
if (!preg_match("/[A-Z]/", $password)) {
    $errors[] = "Password must contain at least one uppercase letter";
}
if (!preg_match("/\d/", $password)) {
    $errors[] = "Password must include at least one digit";
}
if (!preg_match("/[@$!%*#?&]/", $password)) {
    $errors[] = "Password must contain at least one special character";
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'errors' => $errors
    ]);
    exit;
}

$hashed_password = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO clients (first_name, last_name, email, phone_number, location, password) VALUES (?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    return_json_error("Database error: " . $conn->error);
}

$stmt->bind_param("ssssss", $first_name, $last_name, $email, $phone_number, $location, $hashed_password);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful!'
    ]);
} else {
    return_json_error("Registration failed: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>