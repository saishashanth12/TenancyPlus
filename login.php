<?php
// login.php
// This line includes our database connection file.
require_once 'config/db_connect.php';

// This variable will hold any error messages we want to show the user.
$error_message = '';

// --- SERVER-SIDE LOGIC ---
// This block of PHP code only runs when the user submits a form (POST request).
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // It's a good security practice to "sanitize" user input.
    // This helps prevent malicious code from being entered into the forms.
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // --- HANDLE LOGIN ---
    // We check if the 'login' button was the one that was clicked.
    if (isset($_POST['login'])) {
        // Find a user in the database with the matching email.
        $sql = "SELECT id, password, role FROM users WHERE email = '$email'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) { // Check if a user was found.
            $user = $result->fetch_assoc();
            // Verify the submitted password against the hashed password in the database.
            if (password_verify($password, $user['password'])) {
                // If password is correct, save user's info in the session and redirect.
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                header("Location: dashboard.php"); // Send them to the main dashboard.
                exit();
            } else {
                $error_message = "Invalid password. Please try again.";
            }
        } else {
            $error_message = "No account found with that email address.";
        }
    }

    // --- HANDLE REGISTRATION ---
    // We check if the 'register' button was clicked.
    elseif (isset($_POST['register'])) {
        // Sanitize the rest of the registration form inputs.
        $full_name = $conn->real_escape_string($_POST['full_name']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $role = $conn->real_escape_string($_POST['role']);
        
        // IMPORTANT: Never store plain text passwords. We hash it for security.
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Create the SQL query to insert the new user's data into the 'users' table.
        $sql = "INSERT INTO users (full_name, email, password, phone, role) VALUES ('$full_name', '$email', '$hashed_password', '$phone', '$role')";

        if ($conn->query($sql) === TRUE) {
            // If registration is successful, log them in automatically.
            $_SESSION['user_id'] = $conn->insert_id;
            $_SESSION['role'] = $role;
            header("Location: dashboard.php"); // Send them to the dashboard.
            exit();
        } else {
            // If the email already exists, MySQL will give a specific error (number 1062).
            if ($conn->errno == 1062) {
                $error_message = "An account with this email already exists. Please log in.";
            } else {
                $error_message = "An error occurred during registration. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Tenancy+</title>
    <!-- We will create this stylesheet in the next step -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- This is the main container for the centered login/register card -->
    <div class="auth-container">
        
        <!-- LOGIN CARD -->
        <div class="auth-card" id="login-card">
            <h1>Welcome Back!</h1>
            <p>Please log in to access your dashboard.</p>
            
            <!-- If there's an error, we display it here -->
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- 
              THE FIX IS HERE: 
              The form action now points to 'login.php' instead of 'index.php'
            -->
            <form action="login.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="login-email">Email Address</label>
                    <input type="email" id="login-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" required>
                </div>
                <button type="submit" name="login" class="auth-btn">Log In</button>
            </form>
            <p class="auth-switch">Don't have an account? <a href="#" id="show-register">Sign Up</a></p>
        </div>

        <!-- REGISTRATION CARD (Initially hidden) -->
        <div class="auth-card" id="register-card" style="display: none;">
            <h1>Create Account</h1>
            <p>Join Tenancy+ to manage your properties with ease.</p>

            <!-- 
              THE FIX IS HERE: 
              The form action now points to 'login.php' instead of 'index.php'
            -->
            <form action="login.php" method="POST" class="auth-form">
                 <div class="form-group">
                    <label for="reg-fullname">Full Name</label>
                    <input type="text" id="reg-fullname" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="reg-email">Email Address</label>
                    <input type="email" id="reg-email" name="email" required>
                </div>
                 <div class="form-group">
                    <label for="reg-phone">Phone Number</label>
                    <input type="tel" id="reg-phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="reg-password">Password</label>
                    <input type="password" id="reg-password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="role">I am a...</label>
                    <select name="role" id="role" required>
                        <option value="owner">Property Owner</option>
                        <option value="tenant">Tenant</option>
                    </select>
                </div>
                <button type="submit" name="register" class="auth-btn">Sign Up</button>
            </form>
            <p class="auth-switch">Already have an account? <a href="#" id="show-login">Log In</a></p>
        </div>
    </div>

    <!-- This small JavaScript snippet switches between the login and register cards -->
    <script>
        document.getElementById('show-register').addEventListener('click', function(e) {
            e.preventDefault(); // Prevents the link from trying to navigate
            document.getElementById('login-card').style.display = 'none';
            document.getElementById('register-card').style.display = 'block';
        });
        document.getElementById('show-login').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('register-card').style.display = 'none';
            document.getElementById('login-card').style.display = 'block';
        });
    </script>
</body>
</html>
