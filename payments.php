<?php
// payments.php
// This page allows property owners to view their payment history.

// --- Get Filter Values (NEW) ---
$filter_property = isset($_GET['filter_property']) ? $conn->real_escape_string($_GET['filter_property']) : '';
$filter_tenant = isset($_GET['filter_tenant']) ? $conn->real_escape_string($_GET['filter_tenant']) : '';

// --- Fetch Data for Dropdowns (NEW) ---
// Fetch all properties for this owner
$sql_owner_props = "SELECT id, address FROM properties WHERE owner_id = '$user_id'";
$result_owner_props = $conn->query($sql_owner_props);

// Fetch all unique tenants who have paid
$sql_owner_tenants = "SELECT DISTINCT u.id, u.full_name 
                      FROM users u 
                      JOIN payments p ON u.id = p.tenant_id 
                      WHERE p.owner_id = '$user_id'";
$result_owner_tenants = $conn->query($sql_owner_tenants);


// --- Fetch Payment History ---
// We select all payment records where the owner_id matches the logged-in user.
$sql_payments = "SELECT 
    p.payment_date,
    p.amount,
    p.payment_month,
    p.payment_year,
    p.payment_method,
    u.full_name AS tenant_name,
    pr.address AS property_address
    FROM payments p
    JOIN users u ON p.tenant_id = u.id
    JOIN properties pr ON p.property_id = pr.id
    WHERE p.owner_id = '$user_id'";

// --- Apply Filters to Query (NEW) ---
if (!empty($filter_property)) {
    $sql_payments .= " AND p.property_id = '$filter_property'";
}
if (!empty($filter_tenant)) {
    $sql_payments .= " AND p.tenant_id = '$filter_tenant'";
}

$sql_payments .= " ORDER BY p.payment_date DESC";

$result_payments = $conn->query($sql_payments);
?>

<div class="content-card">
    <h2>Payment History</h2>

    <!-- NEW FILTER FORM -->
    <form action="dashboard.php" method="GET" class="auth-form" style="text-align: left; margin-bottom: 2rem; display: flex; gap: 1rem; align-items: flex-end;">
        <input type="hidden" name="page" value="payments">
        
        <div class="form-group" style="flex-grow: 1; margin-bottom: 0;">
            <label for="filter_property">Filter by Property</label>
            <select name="filter_property" id="filter_property">
                <option value="">All Properties</option>
                <?php while($row = $result_owner_props->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>" <?php if ($filter_property == $row['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($row['address']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group" style="flex-grow: 1; margin-bottom: 0;">
            <label for="filter_tenant">Filter by Tenant</label>
            <select name="filter_tenant" id="filter_tenant">
                <option value="">All Tenants</option>
                 <?php while($row = $result_owner_tenants->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>" <?php if ($filter_tenant == $row['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($row['full_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-bottom: 0;">Filter</button>
        <a href="dashboard.php?page=payments" class="btn" style="margin-bottom: 0; background-color: #6B7280; color: white;">Clear</a>
    </form>
    <!-- END FILTER FORM -->


    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Tenant</th>
                    <th>Property</th>
                    <th>For Month</th>
                    <th>Method</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result_payments->num_rows > 0): ?>
                    <?php while($row = $result_payments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d M, Y', strtotime($row['payment_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['tenant_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['property_address']); ?></td>
                            <td><?php echo date('F, Y', mktime(0, 0, 0, $row['payment_month'], 1, $row['payment_year'])); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($row['payment_method'])); ?></td>
                            <td>₹<?php echo number_format($row['amount'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">
                            <?php if (!empty($filter_property) || !empty($filter_tenant)): ?>
                                No payments found matching your filters.
                            <?php else: ?>
                                No payment records found.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

