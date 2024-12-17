<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Sign Up | HandyLink</title>
		<link rel="stylesheet" href="../assets/signup.css">
		<link rel="icon" type="image/x-con" href="../assets/favicon.ico">
		<meta charset="UTF-8">
  		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="keywords" content="HTML, CSS">
		<meta name="author" content="Jemima Arhin">
	</head>
	<body>
		<div class="signup-box-container">
		    <div class="signup-box">
			<h1>Handylink</h1>
		    <form id="signupForm" method="POST">
                <input type="text" id="firstName" name="first_name" placeholder="First Name" required>
                <br><br>
                <input type="text" id="lastName" name="last_name" placeholder="Last Name" required>
                <br><br>
                <input type="email" id="email" name="email" placeholder="Email Address" required>
                <br><br>
                <div class="phone-input-container">
                    <div class="country-code">
                        <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFwAAAA9CAMAAAAXmf6VAAAAjVBMVEUAazrwCSP/0gAAAAD/1wDwACT3jhUAZzt6lSz/1ACThAD/3AD/2QD/3wD10ADlfRVsYQDvygDbuQC2mQCSfQBaTQA3LgC7oACYggBiVADpxgCEdwAKAACJfADiwQAcGADOsACBbwDGqgBNQwAvKQArJACqkQByYQBIPQAOCAA+NwBVRABjUABsiCyJnyyRifIuAAABFElEQVRYhe2V2U7DQAxFp3hY3MkE0pUmpRtLgS7//3l1lQYoCHEtZV6Qz0M0I1lHzo0ju05CnMlNbnKTm9zkf8ovE+KuVNyqqp3XEO+CptxdKOCCeqyoV8l9nwZZKnkY0iiVXFIhmily0cj9WOT3itYROTezMhH5KJ5uwBsAci6rbs1U5PRQnytgbpDO/WxOP1gA8UCZc1h+U0+QVNAPGlZn7n6EZgadFp8/fqifSnBi4FHkODy5n7G2VfJ82nReti//TP0F/Y9geVgfvdXx8RpaljO/Ec17sXgXewHmAssllXXGMvIDycW3K5dUxrE+LeBc0DXHm7zZcFm2LcE1d4Ox+3rZ734rO8ddJ8QZhmEYxv/lAHE0KI53Mmy7AAAAAElFTkSuQmCC" alt="US Flag">
                        <span></span>
                        <select>
                            <option value="+233">+233</option>
                            <option value="+1">+1</option>
                            <option value="+44">+44</option>
                            <option value="+91">+91</option>
                        </select>
                    </div>
                    <input type="tel" name="phone_number" placeholder="Phone Number" class="phone-number">
                </div>
                <input type="text" id="location" name="location" placeholder="Location" required>
                <br><br>
                <input type="password" id="password" name="password" class="password-input" placeholder="Password" required>
                <br><br>
                <input type="password" id="confirmPassword" name="confirm_password" class="password-input" placeholder="Confirm Password" required>
                <br><br>
                <p>By clicking below and creating an account, I agree to <br> Handylink’s Terms of Service and Privacy Policy.</p>
                <button type="submit" style="width: 70%; background-color: #28a745; border: none; border-radius: 20px; font-size: 16px; cursor: pointer;">
                    Register
                </button>
                <p style="font-family: times new roman; font-size: 15px">Already have an account? <a href="login.php" style="font-size: 15px"> Log In </a></p>
            </form>

            <div id="errorMessages" style="color: red;"></div>
        </div>

        <script>
        document.getElementById("signupForm").addEventListener("submit", function(event) {
            event.preventDefault();

            // Validate form
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const errorMessagesDiv = document.getElementById("errorMessages");
            errorMessagesDiv.innerHTML = '';
            
            let isValid = true;

            // Email validation
            const emailRegex = /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/;
            if (!emailRegex.test(email)) {
                errorMessagesDiv.innerHTML += "Invalid email format.<br>";
                isValid = false;
            }

              //Password validation
            // Get password field and create container for requirements
            const passwordField = document.getElementById("password");
            const errorMsg = document.getElementById("errorMsg"); // Assuming you have this error message container

            // Function to validate password and display requirements
            function validatePassword(password) {
                const requirements = [
                    { test: pw => pw.length >= 8, message: "Minimum length of 8 characters" },
                    { test: pw => /[A-Z]/.test(pw), message: "At least one uppercase letter" },
                    { test: pw => /\d/.test(pw), message: "At least one number" },
                    { test: pw => /[!@#$%^&*(),.?":{}|<>]/.test(pw), message: "At least one special character" }
                ];

                let hasError = false;
                const messages = requirements.map(req => {
                    if (!req.test(password)) {
                        hasError = true;
                    }
                    return `<li style="color: ${req.test(password) ? 'green' : 'red'}">${req.message}</li>`;
                });

                errorMsg.innerHTML = `
                    <div>
                        Password must contain:
                        <ul>${messages.join('')}</ul>
                    </div>`;

                return hasError;
            }

            // Confirm password
            if (password !== confirmPassword) {
                errorMessagesDiv.innerHTML += "Passwords do not match.<br>";
                isValid = false;
            }

            if (isValid) {
                const formData = new FormData(this);
                
                fetch("../actions/register.php", {
                    method: "POST",
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Registration successful!");
                        window.location.href = "../view/login.php";
                    } else {
                        alert("Errors:\n" + data.errors.join("\n"));
                    }
                })
                .catch(error => {
                if (error instanceof SyntaxError) {
                    // Handle JSON parsing errors
                    console.error('JSON Parsing Error:', error);
                    alert('There was a problem processing the server response. Please try again.');
                } else if (error instanceof TypeError) {
                    // Handle network errors
                    console.error('Network Error:', error);
                    alert('Unable to connect to the server. Please check your internet connection and try again.');
                } else {
                    // Handle other types of errors
                    console.error('Registration Error:', error);
                    alert('An unexpected error occurred during registration. Please try again later.');
                }
                // Log the full error for debugging
                console.error('Full error details:', {
                    name: error.name,
                    message: error.message,
                    stack: error.stack
                });
            });
            }
        });
        </script>
    </body>
</html>