<?php
session_start();

include "../db/config.php";

// Function to check if email is admin
function isAdmin($email) {
    // List of authorized admin emails and their roles
    $admin_emails = [
        'admin@gmail.com' => 'admin',
        'super@gmail.com' => 'admin'
    ];
    
    return isset($admin_emails[$email]) ? $admin_emails[$email] : false;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST["password"]);
    $user_type = trim($_POST["user_type"]);
    $errors = [];

    // Server-side validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    // Check if user is admin first
    $admin_role = isAdmin($email);

    // If validation passed
    if (empty($errors)) {
        if ($admin_role) {
            // Check admin credentials
            $stmt = $conn->prepare("SELECT admin_id, email, password, first_name, last_name, role 
                                  FROM admin_users 
                                  WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $admin = $result->fetch_assoc();
                
                if (password_verify($password, $admin['password'])) {
                    // Set admin session variables
                    $_SESSION['admin_id'] = $admin['admin_id'];
                    $_SESSION['admin_role'] = $admin['role'];
                    $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
                    $_SESSION['email'] = $admin['email'];
                    $_SESSION['user_type'] = 'admin';
                    
                    // Log admin login
                    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action_type) VALUES (?, 'login')");
                    $stmt->bind_param("i", $admin['admin_id']);
                    $stmt->execute();

                    header("Location: ../view/admin_dashboard.php");
                    exit();
                }
            }
        }

    if (empty($user_type) || !in_array($user_type, ['client', 'helper'])) {
        $errors[] = "Please select whether you are a client or helper.";
    }

    // If validation passed
    if (empty($errors)) {
        // Prepare query based on user type
        if ($user_type === 'client') {
            $stmt = $conn->prepare("SELECT client_id, email, password, first_name, last_name, phone_number 
                                  FROM clients 
                                  WHERE email = ?");
        } else {
            $stmt = $conn->prepare("SELECT helper_id, email, password, first_name, last_name, phone_number 
                                  FROM helpers 
                                  WHERE email = ?");
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        // Check if user exists
        if ($stmt->num_rows > 0) {
            if ($user_type === 'client') {
                $stmt->bind_result($user_id, $db_email, $hashed_password, $first_name, $last_name, $phone_number);
            } else {
                $stmt->bind_result($helper_id, $db_email, $hashed_password, $first_name, $last_name, $phone_number);
            }
            $stmt->fetch();

            // Verify password
            if (password_verify($password, $hashed_password)) {
                // Store common session data
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['email'] = $db_email;
                $_SESSION['phone_number'] = $phone_number;
                $_SESSION['user_type'] = $user_type;

                // Store ID based on user type
                if ($user_type === 'client') {
                    $_SESSION['client_id'] = $user_id;
                    header("Location: ../view/service.php");
                } else {
                    $_SESSION['helper_id'] = $helper_id;
                    header("Location: ../view/helper_dashboard.php");
                }
                exit();
            } else {
                $errors[] = "Incorrect password.";
            }
        } else {
            $errors[] = "No " . $user_type . " account found with this email.";
        }
        $stmt->close();
    }

    // Handle errors
    if (!empty($errors)) {
        $_SESSION['login_errors'] = $errors;
        header("Location: ../login.php?error=1");
        exit();
    }
    $conn->close();
}
}
?>