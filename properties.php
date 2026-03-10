<?php
// properties.php
// This is the page for owners to manage their properties.

$success_message = '';
$error_message = '';

// --- HANDLE ADD PROPERTY FORM ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_property'])) {
    // Sanitize user input
    $address = $conn->real_escape_string($_POST['address']);
    $city = $conn->real_escape_string($_POST['city']);
    $rent_amount = $conn->real_escape_string($_POST['rent_amount']);

    // SQL query to insert the new property
    $sql_insert = "INSERT INTO properties (owner_id, address, city, rent_amount, status) 
                   VALUES ('$user_id', '$address', '$city', '$rent_amount', 'vacant')";
    
    if ($conn->query($sql_insert) === TRUE) {
        $success_message = "New property added successfully!";
    } else {
        $error_message = "Error adding property: " . $conn->error;
    }
}

// --- FETCH PROPERTIES FOR THE TABLE ---

// NEW: Check if a search term is provided
$search_term = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Modify the base SQL query
$sql_properties = "SELECT id, address, city, rent_amount, status 
                   FROM properties 
                   WHERE owner_id = '$user_id'";

// NEW: If there is a search term, add a WHERE clause to filter by address
if (!empty($search_term)) {
    $sql_properties .= " AND address LIKE '%$search_term%'";
}

$sql_properties .= " ORDER BY created_at DESC";

$result_properties = $conn->query($sql_properties);

?>

<!-- Add New Property Form -->
<div class="content-card">
    <h2>Add New Property</h2>

    <?php if (!empty($success_message)): ?>
        <div class="success-message" style="background-color: #D1FAE5; color: #065F46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;"><?php echo $success_message; ?></div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form action="dashboard.php?page=properties" method="POST" class="auth-form" style="text-align: left;">
        <div class="form-group">
            <label for="address">Property Address</label>
            <input type="text" name="address" id="address" placeholder="e.g., 123 Main St" required>
        </div>
        <div class="form-group">
            <label for="city">City</label>
            <input type="text" name="city" id="city" placeholder="e.g., Mumbai" required>
        </div>
        <div class="form-group">
            <label for="rent_amount">Monthly Rent (in ₹)</label>
            <input type="number" name="rent_amount" id="rent_amount" step="0.01" min="0" placeholder="e.g., 25000" required>
        </div>
        <button type="submit" name="add_property" class="btn btn-primary">Add Property</button>
    </form>
</div>

<!-- My Properties List -->
<div class="content-card" style="margin-top: 2rem;">
    
    <!-- NEW SEARCH FORM -->
    <div class="properties-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2>My Properties</h2>
        <form action="dashboard.php" method="GET" style="display: flex; gap: 0.5rem;">
            <input type="hidden" name="page" value="properties">
            <input type="text" name="search" class="auth-form" placeholder="Search by address..." value="<?php echo htmlspecialchars($search_term); ?>" style="padding: 0.5rem; margin-bottom: 0;">
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Search</button>
        </form>
    </div>
    <!-- END SEARCH FORM -->

    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Address</th>
                    <th>City</th>
                    <th>Rent (₹)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result_properties && $result_properties->num_rows > 0): ?>
                    <?php while($row = $result_properties->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['address']); ?></td>
                            <td><?php echo htmlspecialchars($row['city']); ?></td>
                            <td>₹<?php echo number_format($row['rent_amount'], 2); ?></td>
                            <td>
                                <span style="font-weight: 600; color: <?php echo $row['status'] == 'occupied' ? 'var(--danger-color)' : 'var(--secondary-color)'; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">
                            <?php if (!empty($search_term)): ?>
                                No properties found matching '<?php echo htmlspecialchars($search_term); ?>'.
                            <?php else: ?>
                                You have not added any properties yet.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

