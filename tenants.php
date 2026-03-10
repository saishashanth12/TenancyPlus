<?php
// tenants.php
// This page allows owners to assign and view their tenants.

$success_message = '';
$error_message = '';

// --- HANDLE FORM SUBMISSIONS ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- HANDLE TENANT ASSIGNMENT ---
    if (isset($_POST['assign_tenant'])) {
        $property_id = $conn->real_escape_string($_POST['property_id']);
        $tenant_id_to_assign = $conn->real_escape_string($_POST['tenant_id']);
        $lease_start_date = $conn->real_escape_string($_POST['lease_start_date']);
        $lease_document_path = NULL;

        // --- NEW FILE UPLOAD LOGIC ---
        if (isset($_FILES['lease_document']) && $_FILES['lease_document']['error'] == 0) {
            $target_dir = "uploads/"; // The folder we created
            $file_name = uniqid() . '_' . basename($_FILES["lease_document"]["name"]);
            $target_file = $target_dir . $file_name;
            $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Check if file is a PDF
            if ($file_type != "pdf") {
                $error_message = "Sorry, only PDF files are allowed for the lease agreement.";
            } else {
                // Try to move the uploaded file to our 'uploads' folder
                if (move_uploaded_file($_FILES["lease_document"]["tmp_name"], $target_file)) {
                    $lease_document_path = $target_file; // Save the path to store in the DB
                } else {
                    $error_message = "Sorry, there was an error uploading your file.";
                }
            }
        }
        // --- END FILE UPLOAD LOGIC ---

        // Only proceed if there was no file upload error
        if (empty($error_message)) {
            // 1. Insert the record into the 'tenants' table (our lease record)
            // UPDATED to include the lease document path
            $sql_insert_lease = "INSERT INTO tenants (property_id, tenant_id, owner_id, lease_start_date, lease_document_path) 
                                 VALUES ('$property_id', '$tenant_id_to_assign', '$user_id', '$lease_start_date', '$lease_document_path')";

            // 2. Update the 'properties' table to mark it as occupied
            $sql_update_property = "UPDATE properties SET status = 'occupied', tenant_id = '$tenant_id_to_assign' WHERE id = '$property_id' AND owner_id = '$user_id'";

            if ($conn->query($sql_insert_lease) === TRUE && $conn->query($sql_update_property) === TRUE) {
                $success_message = "Tenant assigned successfully!";
                if ($lease_document_path) {
                    $success_message .= " Lease document uploaded.";
                }
            } else {
                $error_message = "Error assigning tenant: " . $conn->error;
            }
        }
    }

    // --- HANDLE TENANT REMOVAL ---
    if (isset($_POST['remove_tenant'])) {
        $tenancy_id = $conn->real_escape_string($_POST['tenancy_id']);
        $property_id_to_vacate = $conn->real_escape_string($_POST['property_id']);
        $today = date('Y-m-d');

        // 1. End the lease by setting an end date in the 'tenants' table
        $sql_end_lease = "UPDATE tenants SET lease_end_date = '$today' WHERE id = '$tenancy_id' AND owner_id = '$user_id'";
        
        // 2. Make the property vacant again in the 'properties' table
        $sql_vacate_property = "UPDATE properties SET tenant_id = NULL, status = 'vacant' WHERE id = '$property_id_to_vacate' AND owner_id = '$user_id'";

        if ($conn->query($sql_end_lease) === TRUE && $conn->query($sql_vacate_property) === TRUE) {
            $success_message = "Tenant has been removed and the lease has been ended.";
        } else {
            $error_message = "Error removing tenant. Please try again.";
        }
    }
}

// --- Fetch Data for Forms and Tables ---

// Fetch vacant properties for the dropdown
$sql_vacant_props = "SELECT id, address FROM properties WHERE owner_id = '$user_id' AND status = 'vacant'";
$result_vacant_props = $conn->query($sql_vacant_props);

// Fetch all users with the 'tenant' role for the dropdown
$sql_all_tenants = "SELECT id, full_name, email FROM users WHERE role = 'tenant'";
$result_all_tenants = $conn->query($sql_all_tenants);

// Fetch currently assigned tenants for this owner
$sql_assigned_tenants = "SELECT 
    t.id as tenancy_id,
    t.lease_start_date,
    t.lease_document_path,
    u.full_name,
    u.email,
    p.address,
    p.id as property_id
    FROM tenants t
    JOIN users u ON t.tenant_id = u.id
    JOIN properties p ON t.property_id = p.id
    WHERE t.owner_id = '$user_id' AND t.lease_end_date IS NULL";
$result_assigned_tenants = $conn->query($sql_assigned_tenants);

?>

<!-- Assign Tenant Form -->
<div class="content-card">
    <h2>Assign Tenant to Property</h2>

    <?php if (!empty($success_message)): ?>
        <div class="success-message" style="background-color: #D1FAE5; color: #065F46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;"><?php echo $success_message; ?></div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <!-- 
      THE FIX IS HERE: 
      1. Added 'enctype="multipart/form-data"' to the form tag to allow file uploads.
      2. Added a new 'lease_document' file input field.
    -->
    <form action="dashboard.php?page=tenants" method="POST" class="auth-form" style="text-align: left;" enctype="multipart/form-data">
        <div class="form-group">
            <label for="property_id">Select a Vacant Property</label>
            <select name="property_id" id="property_id" required>
                <option value="">-- Choose a property --</option>
                <?php while($row = $result_vacant_props->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['address']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="tenant_id">Select a Tenant</label>
            <select name="tenant_id" id="tenant_id" required>
                 <option value="">-- Choose a tenant --</option>
                <?php while($row = $result_all_tenants->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['full_name'] . ' (' . $row['email'] . ')'); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="lease_start_date">Lease Start Date</label>
            <input type="date" name="lease_start_date" id="lease_start_date" required>
        </div>
        <!-- NEW FILE UPLOAD FIELD -->
        <div class="form-group">
            <label for="lease_document">Lease Agreement (PDF Only)</label>
            <input type="file" name="lease_document" id="lease_document" accept=".pdf">
        </div>
        <button type="submit" name="assign_tenant" class="btn btn-primary">Assign Tenant</button>
    </form>
</div>

<!-- Currently Assigned Tenants Table -->
<div class="content-card" style="margin-top: 2rem;">
    <h2>Currently Assigned Tenants</h2>
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tenant Name</th>
                    <th>Property</th>
                    <th>Lease Started</th>
                    <th>Lease Document</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result_assigned_tenants->num_rows > 0): ?>
                    <?php while($row = $result_assigned_tenants->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['address']); ?></td>
                            <td><?php echo date('d M, Y', strtotime($row['lease_start_date'])); ?></td>
                            <!-- NEW COLUMN to show lease status -->
                            <td>
                                <?php if (!empty($row['lease_document_path'])): ?>
                                    <a href="<?php echo htmlspecialchars($row['lease_document_path']); ?>" target="_blank">View PDF</a>
                                <?php else: ?>
                                    <span style="color: var(--text-color);">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form action="dashboard.php?page=tenants" method="POST" style="display: inline;">
                                    <input type="hidden" name="tenancy_id" value="<?php echo $row['tenancy_id']; ?>">
                                    <input type="hidden" name="property_id" value="<?php echo $row['property_id']; ?>">
                                    <button type="submit" name="remove_tenant" class="btn" style="background-color: var(--danger-color); color: white;" onclick="return confirm('Are you sure you want to end this lease? This action cannot be undone.');">End Lease</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">You have no tenants currently assigned to properties.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

