<?php
// submit_complaint.php
// This page allows a logged-in tenant to submit a complaint about their property.

// Since this file is included by dashboard.php, we already have access to $conn and $_SESSION.
$tenant_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// --- Find the Tenant's Property Information ---
// We need to find which property this tenant is assigned to.
$sql_property_info = "SELECT property_id, owner_id FROM tenants WHERE tenant_id = '$tenant_id' LIMIT 1";
$result_property_info = $conn->query($sql_property_info);

if ($result_property_info && $result_property_info->num_rows > 0) {
    $lease_info = $result_property_info->fetch_assoc();
    $property_id = $lease_info['property_id'];
    $owner_id = $lease_info['owner_id'];
} else {
    // This case should ideally not happen if the data is consistent.
    // It means the logged-in tenant is not assigned to any property.
    $property_id = null;
    $owner_id = null;
}

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_complaint'])) {
    if ($property_id) {
        $subject = $conn->real_escape_string($_POST['subject']);
        $description = $conn->real_escape_string($_POST['description']);

        // Insert the complaint into the database.
        $sql_insert = "INSERT INTO complaints (property_id, tenant_id, owner_id, subject, description) VALUES ('$property_id', '$tenant_id', '$owner_id', '$subject', '$description')";

        if ($conn->query($sql_insert)) {
            $success_message = "Your complaint has been submitted successfully! Your property owner has been notified.";
        } else {
            $error_message = "There was an error submitting your complaint. Please try again.";
        }
    } else {
        $error_message = "Could not submit complaint: You are not currently assigned to a property.";
    }
}
?>

<div class="content-card">
    <h2>Submit a Complaint or Maintenance Request</h2>
    <p>Please provide details about the issue you are experiencing, and we will notify your property owner.</p>

    <!-- Display Success or Error Messages -->
    <?php if (!empty($success_message)): ?>
        <div class="success-message"><?php echo $success_message; ?></div>
    <?php elseif (!empty($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if ($property_id): // Only show the form if the tenant is assigned to a property ?>
        <form action="dashboard.php?page=submit_complaint" method="POST" class="auth-form" style="text-align: left;">
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" required placeholder="e.g., Leaky Faucet in Kitchen">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="6" required placeholder="Please describe the issue in detail..."></textarea>
            </div>
            <button type="submit" name="submit_complaint" class="btn btn-primary">Submit Complaint</button>
        </form>
    <?php else: ?>
        <p>You cannot submit a complaint because you are not currently assigned to a property.</p>
    <?php endif; ?>
</div>

<style>
    /* Adding some specific styles for the textarea */
    .auth-form textarea {
        width: 100%;
        padding: 0.8rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: 0.5rem;
        font-size: 1rem;
        font-family: 'Poppins', sans-serif;
        resize: vertical; /* Allow vertical resizing */
        transition: border-color 0.3s;
    }

    .auth-form textarea:focus {
        outline: none;
        border-color: var(--primary-color);
    }
    .success-message {
        background-color: #D1FAE5; /* Light green */
        color: #065F46; /* Dark green */
        padding: 0.8rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        text-align: center;
    }
</style>

