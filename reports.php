<?php
// reports.php
// This page allows owners to generate and download CSV reports.

// We only need the database connection here to potentially fetch properties
// require_once 'config/db_connect.php'; // Included via dashboard.php
// check_login(); // Checked by dashboard.php
// $user_id = $_SESSION['user_id']; // Available via dashboard.php

// Fetch properties for potential filtering (optional)
$sql_owner_props = "SELECT id, address FROM properties WHERE owner_id = '$user_id'";
$result_owner_props = $conn->query($sql_owner_props);

?>

<div class="content-card">
    <h2>Generate Reports</h2>
    <p>Select the options below to generate a downloadable CSV report of your data.</p>

    <form action="download_report.php" method="POST" class="auth-form" style="text-align: left; margin-top: 2rem;">
        
        <div class="form-group">
            <label for="report_type">Report Type:</label>
            <select name="report_type" id="report_type" required>
                <option value="all_data">Comprehensive Report (All Data)</option>
                <!-- Add more specific report types later if needed -->
            </select>
        </div>

        <div class="form-group" style="display: flex; gap: 1rem;">
            <div style="flex: 1;">
                <label for="start_date">Start Date (Optional):</label>
                <input type="date" name="start_date" id="start_date">
            </div>
            <div style="flex: 1;">
                 <label for="end_date">End Date (Optional):</label>
                <input type="date" name="end_date" id="end_date">
            </div>
        </div>

        <!-- Optional: Add property filter later if needed 
        <div class="form-group">
            <label for="property_id">Filter by Property (Optional):</label>
            <select name="property_id" id="property_id">
                <option value="">All Properties</option>
                <?php //while($row = $result_owner_props->fetch_assoc()): ?>
                    <option value="<?php //echo $row['id']; ?>"><?php //echo htmlspecialchars($row['address']); ?></option>
                <?php //endwhile; ?>
            </select>
        </div>
        -->

        <button type="submit" name="download_report" class="btn btn-primary">Download Report (CSV)</button>
    </form>
</div>
