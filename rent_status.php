<?php
// rent_status.php
// This page shows the tenant their current lease details and payment status.

// We need to check if the user is a tenant who is assigned to a property.
// UPDATED QUERY: Now also selects 'lease_document_path'
$sql_lease = "SELECT 
    p.address, p.city, p.rent_amount, p.owner_id,
    u.full_name AS owner_name, u.email AS owner_email, u.phone AS owner_phone,
    t.lease_document_path 
    FROM tenants t
    JOIN properties p ON t.property_id = p.id
    JOIN users u ON p.owner_id = u.id
    WHERE t.tenant_id = '$user_id' AND t.lease_end_date IS NULL"; // Ensure lease is active

$result_lease = $conn->query($sql_lease);
$lease_details = $result_lease->fetch_assoc();


// Check if rent has been paid for the current month
$rent_paid_this_month = false;
if ($lease_details) {
    $current_month = date('n');
    $current_year = date('Y');
    $sql_check_payment = "SELECT id FROM payments WHERE tenant_id = '$user_id' AND payment_month = '$current_month' AND payment_year = '$current_year'";
    $result_check_payment = $conn->query($sql_check_payment);
    if ($result_check_payment->num_rows > 0) {
        $rent_paid_this_month = true;
    }
}

?>

<div class="content-card">
    <h2>My Rent Status</h2>

    <?php if ($lease_details): ?>
        <div class="lease-info">
            <p><strong>Property Address:</strong> <?php echo htmlspecialchars($lease_details['address'] . ', ' . $lease_details['city']); ?></p>
            <p><strong>Monthly Rent:</strong> ₹<?php echo number_format($lease_details['rent_amount'], 2); ?></p>
            <hr style="margin: 1rem 0;">
            <p><strong>Property Owner:</strong> <?php echo htmlspecialchars($lease_details['owner_name']); ?></p>
            <p><strong>Owner's Email:</strong> <?php echo htmlspecialchars($lease_details['owner_email']); ?></p>
            <p><strong>Owner's Phone:</strong> <?php echo htmlspecialchars($lease_details['owner_phone']); ?></p>

            <!-- NEW LEASE DOWNLOAD BUTTON -->
            <?php if (!empty($lease_details['lease_document_path'])): ?>
                <a href="<?php echo htmlspecialchars($lease_details['lease_document_path']); ?>" class="btn btn-primary" target="_blank" style="margin-top: 1rem;">Download Lease Agreement (PDF)</a>
            <?php else: ?>
                <p style="margin-top: 1rem; font-style: italic; color: var(--text-color);">No lease document has been uploaded for this tenancy.</p>
            <?php endif; ?>
        </div>

        <div class="payment-status" style="margin-top: 2rem;">
            <h3>Payment for <?php echo date('F, Y'); ?></h3>
            <?php if ($rent_paid_this_month): ?>
                <p style="color: #059669;"><strong>Status: Paid</strong></p>
                <p>Thank you for your payment this month!</p>
            <?php else: ?>
                <p style="color: #D97706;"><strong>Status: Due</strong></p>
                <a href="dashboard.php?page=make_payment" class="btn btn-primary" style="margin-top: 1rem;">Pay Rent Now (₹<?php echo number_format($lease_details['rent_amount'], 2); ?>)</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p>You are not currently assigned to any property. If you believe this is an error, please contact your property owner.</p>
    <?php endif; ?>
</div>

