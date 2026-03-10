<?php
// complaints.php
// This page allows owners to view and manage complaints.

$success_message = '';
$error_message = '';

// --- Handle Status Update ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $complaint_id = $conn->real_escape_string($_POST['complaint_id']);
    $new_status = $conn->real_escape_string($_POST['status']);

    // Check if this complaint belongs to the logged-in owner
    $sql_update = "UPDATE complaints SET status = '$new_status' WHERE id = '$complaint_id' AND owner_id = '$user_id'";
    
    if ($conn->query($sql_update) === TRUE && $conn->affected_rows > 0) {
        $success_message = "Complaint status updated successfully!";
    } else {
        $error_message = "Error updating status or complaint not found.";
    }
}

// --- Get Filter Values (NEW) ---
$filter_property = isset($_GET['filter_property']) ? $conn->real_escape_string($_GET['filter_property']) : '';
$filter_status = isset($_GET['filter_status']) ? $conn->real_escape_string($_GET['filter_status']) : '';

// --- Fetch Data for Dropdowns (NEW) ---
// Fetch all properties for this owner
$sql_owner_props = "SELECT id, address FROM properties WHERE owner_id = '$user_id'";
$result_owner_props = $conn->query($sql_owner_props);


// --- Fetch Complaints ---
// We join with users and properties to get names and addresses
$sql_complaints = "SELECT 
    c.id, c.subject, c.description, c.status, c.created_at,
    u.full_name AS tenant_name,
    p.address AS property_address
    FROM complaints c
    JOIN users u ON c.tenant_id = u.id
    JOIN properties p ON c.property_id = p.id
    WHERE c.owner_id = '$user_id'";

// --- Apply Filters to Query (NEW) ---
if (!empty($filter_property)) {
    $sql_complaints .= " AND c.property_id = '$filter_property'";
}
if (!empty($filter_status)) {
    $sql_complaints .= " AND c.status = '$filter_status'";
}

$sql_complaints .= " ORDER BY c.created_at DESC";

$result_complaints = $conn->query($sql_complaints);
?>

<div class="content-card">
    <h2>View Complaints</h2>

    <?php if (!empty($success_message)): ?>
        <div class="success-message" style="background-color: #D1FAE5; color: #065F46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;"><?php echo $success_message; ?></div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <!-- NEW FILTER FORM -->
    <form action="dashboard.php" method="GET" class="auth-form" style="text-align: left; margin-bottom: 2rem; display: flex; gap: 1rem; align-items: flex-end;">
        <input type="hidden" name="page" value="complaints">
        
        <div class="form-group" style="flex-grow: 1; margin-bottom: 0;">
            <label for="filter_property">Filter by Property</label>
            <select name="filter_property" id="filter_property">
                <option value="">All Properties</option>
                <?php mysqli_data_seek($result_owner_props, 0); // Reset pointer for this loop ?>
                <?php while($row = $result_owner_props->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>" <?php if ($filter_property == $row['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($row['address']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group" style="flex-grow: 1; margin-bottom: 0;">
            <label for="filter_status">Filter by Status</label>
            <select name="filter_status" id="filter_status">
                <option value="">All Statuses</option>
                <option value="pending" <?php if ($filter_status == 'pending') echo 'selected'; ?>>Pending</option>
                <option value="in_progress" <?php if ($filter_status == 'in_progress') echo 'selected'; ?>>In Progress</option>
                <option value="resolved" <?php if ($filter_status == 'resolved') echo 'selected'; ?>>Resolved</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-bottom: 0;">Filter</button>
        <a href="dashboard.php?page=complaints" class="btn" style="margin-bottom: 0; background-color: #6B7280; color: white;">Clear</a>
    </form>
    <!-- END FILTER FORM -->


    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Tenant</th>
                    <th>Property</th>
                    <th>Complaint</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result_complaints->num_rows > 0): ?>
                    <?php while($row = $result_complaints->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d M, Y', strtotime($row['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($row['tenant_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['property_address']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['subject']); ?></strong>
                                <p><?php echo htmlspecialchars($row['description']); ?></p>
                            </td>
                            <td>
                                <!-- Form to update status -->
                                <form action="dashboard.php?page=complaints" method="POST">
                                    <input type="hidden" name="complaint_id" value="<?php echo $row['id']; ?>">
                                    <select name="status" class="auth-form" style="padding: 0.5rem; margin-bottom: 0.5rem;">
                                        <option value="pending" <?php if ($row['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                                        <option value="in_progress" <?php if ($row['status'] == 'in_progress') echo 'selected'; ?>>In Progress</option>
                                        <option value="resolved" <?php if ($row['status'] == 'resolved') echo 'selected'; ?>>Resolved</option>
                                    </select>
                            </td>
                            <td>
                                    <button type="submit" name="update_status" class="btn" style="padding: 0.5rem 1rem;">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">
                            <?php if (!empty($filter_property) || !empty($filter_status)): ?>
                                No complaints found matching your filters.
                            <?php else: ?>
                                No complaints found.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

