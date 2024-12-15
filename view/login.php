<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Login | HandyLink</title>
		<link rel="stylesheet" href="../assets/login.css">
		<link rel="icon" type="image/x-con" href="../assets/favicon.ico">
		<meta charset="UTF-8">
  		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="keywords" content="HTML, CSS">
		<meta name="author" content="Jemima Arhin">
	</head>
	<body>
    <div class="login-box-container">
		    <div class="login-box">
			<h1>Handylink</h1>
		    <p>New to Handylink?<a href="signup.php"> Sign up here </a></p>
			<form id="loginForm" method="post" action="../actions/login_action.php">
		       
		        <!-- Email Field -->
                <div class="input-field">
                    <label for="email">Email Address</label>
                    <input type="text" id="email" name="email" placeholder="Email Address" required>
                </div>
                <!-- Password Field -->
                <div class="input-field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                </div>

                <!-- Radio buttons -->
                <div class="input-field radio-group">
                    <label>I am a:</label><br>
                    <label>
                        <input type="radio" name="user_type" value="client" required checked>
                        Client
                    </label>
                    <label>
                        <input type="radio" name="user_type" value="helper">
                        Helper
                    </label>
                </div><br>

                <div>
                    <a href="#" style="color: black;">Forgot password?</a>
                </div><br>

                <button type="submit">Log in</button>
                <p class="error" id="errorMessage" style="color: red;"></p>
            </form>
        </div>

        <script>
    
            document.getElementById("loginForm").addEventListener("submit", function(event) {
              event.preventDefault(); // Prevent form submission
                    
              //Clear previous error messages
              // document.getElementById("emailError").style.display = 'none';
              // document.getElementById("passwordError").style.display = 'none';
              const errorMsg = document.getElementById("errorMessage");
              errorMsg.innerHTML = '';
        
              let hasError = false;
        
              // Validate email format
              const email = document.getElementById("email").value;
              const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
              if (!emailPattern.test(email)) {
                errorMsg.innerHTML += "Invalid email.<br>";
                hasError = true;
                // document.getElementById("emailError").style.display = 'block';
                // return;
              }
        
              // Validate password
            const password = document.getElementById("password").value;
            const passwordRequirements = [
                { test: pw => pw.length >= 8, message: "Minimum length of 8 characters" },
                { test: pw => /[A-Z]/.test(pw), message: "At least one uppercase letter" },
                { test: pw => /\d/.test(pw), message: "At least one number" },
                { test: pw => /[!@#$%^&*(),.?":{}|<>]/.test(pw), message: "At least one special character" }
            ];

            const failedRequirements = passwordRequirements
                .filter(req => !req.test(password))
                .map(req => `<li>${req.message}</li>`);

            if (failedRequirements.length > 0) {
                errorMsg.innerHTML += `
                    <div style="color: red;">
                        Password must contain:
                        <ul>${failedRequirements.join('')}</ul>
                    </div>`;
                hasError = true;
            }
        
              // If there are no errors, proceed with form submission (here, we just log to console)
              if (!hasError) {
                console.log("Form submitted successfully!");
                document.getElementById("loginForm").submit();
              }
            });
          </script>
    </body>
</html>
