<?php
session_start();
include "../db/config.php";

function isAdmin($email) {
    $admin_emails = [
        'admin@gmail.com' => 'admin',
        'super@gmail.com' => 'admin'
    ];
    return isset($admin_emails[$email]) ? $admin_emails[$email] : false;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST["password"]);
    $user_type = isset($_POST["user_type"]) ? trim($_POST["user_type"]) : 'admin';
    // echo $user_type;
    $errors = [];

    // Server-side validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    // Only proceed if no validation errors
    if (empty($errors)) {

        // Check for admin first, regardless of radio selection
        if (isAdmin($email) && $user_type == "admin") {
            // Admin login logic
            $stmt = $conn->prepare("SELECT admin_id, email, password, first_name, last_name, role FROM admin_users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
           

            if ($result->num_rows > 0) {
                // echo 'admin reached 2';
                $admin = $result->fetch_assoc();
                // echo $password. ' - '. $admin['password'] .'';
                if (password_verify($password, $admin['password'])) {
                    // echo 'admin reached 3';
                    // echo $admin['admin_id'];
                    $_SESSION['admin_id'] = $admin['admin_id'];
                    $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
                    $_SESSION['email'] = $admin['email'];
                    $_SESSION['role'] = 'admin';
                    
                    header("Location: ../view/admin_dashboard.php");
                    exit();
                } else {
                    $errors[] = "Invalid password";
                    // echo json_encode($errors);
                }
            } else {
                $errors[] = "Invalid admin credentials";
            }
       } 
    elseif ($user_type != 'admin') {
            // Regular user login logic
            $table = ($user_type === 'client') ? 'clients' : 'helpers';
            $id_field = ($user_type === 'client') ? 'client_id' : 'helper_id';
            
            $stmt = $conn->prepare("SELECT $id_field, email, password, first_name, last_name, phone_number FROM $table WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['phone_number'] = $user['phone_number'];
                    $_SESSION['user_type'] = $user_type;
                    
                    // Set type-specific ID
                    if ($user_type === 'client') {
                        $_SESSION['client_id'] = $user[$id_field];
                        header("Location: ../view/service.php");
                    } else {
                        $_SESSION['helper_id'] = $user[$id_field];
                        header("Location: ../view/helper_dashboard.php");
                    }
                    exit();
                } else {
                    $errors[] = "Invalid password";
                }
            } else {
                $errors[] = "No account found with this email";
            }
        } else {
            $errors[] = "Please select whether you are a client or helper";
        }
    }
    
    // If we get here, there were errors
    if (!empty($errors)) {
        $_SESSION['login_errors'] = $errors;
        header("Location: ../view/login.php?error=1");
        exit();
    }
}

// If somehow we get here without any action
header("Location: ../login.php");
exit();
?>