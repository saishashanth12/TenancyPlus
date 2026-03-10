<?php
// profile.php
// This page allows logged-in users to update their profile and change their password.

// We already have $conn and $user_id from dashboard.php

$success_message = '';
$error_message = '';

// --- HANDLE FORM SUBMISSIONS ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Handle Profile Details Update ---
    if (isset($_POST['update_details'])) {
        $full_name = $conn->real_escape_string($_POST['full_name']);
        $phone = $conn->real_escape_string($_POST['phone']);

        $sql_update_details = "UPDATE users SET full_name = '$full_name', phone = '$phone' WHERE id = '$user_id'";
        
        if ($conn->query($sql_update_details) === TRUE) {
            $success_message = "Your details have been updated successfully!";
        } else {
            $error_message = "Error updating details. Please try again.";
        }
    }

    // --- Handle Password Change ---
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // 1. Fetch the user's current hashed password from the database
        $sql_get_pass = "SELECT password FROM users WHERE id = '$user_id'";
        $result_pass = $conn->query($sql_get_pass);
        $user_data = $result_pass->fetch_assoc();
        $hashed_password = $user_data['password'];

        // 2. Verify the current password is correct
        if (password_verify($current_password, $hashed_password)) {
            
            // 3. Check if new password and confirm password match
            if ($new_password === $confirm_password) {
                
                // 4. Hash the new password
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // 5. Update the password in the database
                $sql_update_pass = "UPDATE users SET password = '$new_hashed_password' WHERE id = '$user_id'";
                if ($conn->query($sql_update_pass) === TRUE) {
                    $success_message = "Password changed successfully!";
                } else {
                    $error_message = "Error changing password. Please try again.";
                }
            } else {
                $error_message = "New password and confirm password do not match.";
            }
        } else {
            $error_message = "Incorrect current password.";
        }
    }
}

// Fetch the user's current data to display in the form
$sql_user_data = "SELECT full_name, email, phone FROM users WHERE id = '$user_id'";
$result_user_data = $conn->query($sql_user_data);
$user = $result_user_data->fetch_assoc();

?>

<?php if (!empty($success_message)): ?>
    <div class="success-message" style="background-color: #D1FAE5; color: #065F46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;"><?php echo $success_message; ?></div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>
    <div class="error-message"><?php echo $error_message; ?></div>
<?php endif; ?>

<!-- Update Details Form -->
<div class="content-card">
    <h2>My Profile</h2>
    <form action="dashboard.php?page=profile" method="POST" class="auth-form" style="text-align: left;">
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email Address (Cannot be changed)</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
        </div>
         <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
        </div>
        <button type="submit" name="update_details" class="btn btn-primary">Update Details</button>
    </form>
</div>

<!-- Change Password Form -->
<div class="content-card" style="margin-top: 2rem;">
    <h2>Change Password</h2>
     <form action="dashboard.php?page=profile" method="POST" class="auth-form" style="text-align: left;">
        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" name="current_password" id="current_password" required>
        </div>
        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" name="new_password" id="new_password" required>
        </div>
         <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
        </div>
        <button type="submit" name="change_password" class="btn btn-primary" style="background-color: var(--danger-color);">Change Password</button>
    </form>
</div>
