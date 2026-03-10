<?php
// make_payment.php
// This page simulates a payment gateway with multiple options.

// --- Initialization ---
// Ensure user is logged in and is a tenant (already handled by dashboard.php including this)
// $user_id is available from dashboard.php
// $conn is available from dashboard.php

$error_message = '';
// Use session flash messages for success/info on redirect
if (isset($_SESSION['payment_success'])) {
    $success_message = $_SESSION['payment_success'];
    unset($_SESSION['payment_success']);
}
if (isset($_SESSION['payment_info'])) {
    $info_message = $_SESSION['payment_info'];
    unset($_SESSION['payment_info']);
}

// --- Fetch Lease Details to get amount ---
$sql_lease = "SELECT p.rent_amount, t.property_id, p.owner_id
              FROM tenants t
              JOIN properties p ON t.property_id = p.id
              WHERE t.tenant_id = '$user_id' AND t.lease_end_date IS NULL";
$result_lease = $conn->query($sql_lease);
$lease_details = $result_lease->fetch_assoc();

if (!$lease_details) {
    // Should not happen if linked correctly, but good to check
    die("Error: No active lease found for this tenant.");
}
$rent_amount = $lease_details['rent_amount'];
$property_id = $lease_details['property_id'];
$owner_id = $lease_details['owner_id'];
$current_month = date('n');
$current_year = date('Y');

// --- Handle Payment Confirmation (POST Request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_payment'])) {
    
    $payment_method = $conn->real_escape_string($_POST['payment_method']);
    
    // Basic validation for simulated details if that method was chosen
    if ($payment_method === 'Card') {
        // Remove spaces/hyphens for length check
        $card_number = preg_replace('/[\s\-]+/', '', $_POST['card_number'] ?? ''); 
        $expiry = $_POST['expiry'] ?? '';
        $cvv = $_POST['cvv'] ?? '';
        
        // Basic length and format checks
        if (strlen($card_number) < 15 || strlen($card_number) > 16 || !ctype_digit($card_number)) {
             $error_message = "Please enter a valid 15 or 16 digit card number.";
        } elseif (!preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
             $error_message = "Please enter expiry date as MM/YY.";
        } elseif (strlen($cvv) < 3 || strlen($cvv) > 4 || !ctype_digit($cvv)) {
             $error_message = "Please enter a valid 3 or 4 digit CVV.";
        }
    } elseif ($payment_method === 'UPI') {
        // NEW: Basic validation for UPI ID
        $upi_id = trim($_POST['upi_id'] ?? '');
        // Simple check: must contain '@' and be at least 5 chars long
        if (empty($upi_id) || strpos($upi_id, '@') === false || strlen($upi_id) < 5) {
             $error_message = "Please enter a valid UPI ID (e.g., yourname@bank).";
        }
    }

    // If no validation errors, record the payment
    if (empty($error_message)) {
        // Check if already paid this month
         $sql_check_payment = "SELECT id FROM payments WHERE tenant_id = '$user_id' AND payment_month = '$current_month' AND payment_year = '$current_year'";
         $result_check_payment = $conn->query($sql_check_payment);
         
         if ($result_check_payment->num_rows == 0) {
             // Insert the payment record
             $stmt = $conn->prepare("INSERT INTO payments (property_id, tenant_id, owner_id, amount, payment_month, payment_year, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)");
             if ($stmt === false) {
                 error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
                 $error_message = "An error occurred. Please try again later.";
             } else {
                 $stmt->bind_param("iiidiis", $property_id, $user_id, $owner_id, $rent_amount, $current_month, $current_year, $payment_method);
                 
                 if ($stmt->execute()) {
                     // Payment recorded successfully! Set session message and redirect
                     $_SESSION['payment_success'] = "Payment of ₹" . number_format($rent_amount, 2) . " via " . sanitize_output($payment_method) . " for " . date('F, Y') . " was recorded successfully!";
                     header("Location: dashboard.php?page=rent_status");
                     exit();
                 } else {
                     $error_message = "Database error recording payment. Please try again.";
                     error_log("Payment Insert Error: (" . $stmt->errno . ") " . $stmt->error); // Log detailed error
                 }
                 $stmt->close();
             }
         } else {
              // Already paid, redirect back with info
              $_SESSION['payment_info'] = "Rent for " . date('F, Y') . " has already been paid.";
              header("Location: dashboard.php?page=rent_status");
              exit();
         }
    }
    // If there was a validation error, execution continues and the form is redisplayed with the error message
}

?>

<div class="content-card">
    <h2>Make Payment</h2>
    <p>Your rent for <strong><?php echo date('F, Y'); ?></strong> is <strong>₹<?php echo number_format($rent_amount, 2); ?></strong>.</p>
    <p>Please select your preferred payment method:</p>

    <?php if (!empty($error_message)): ?>
        <div class="error-message" style="margin-top: 1rem;"><?php echo sanitize_output($error_message); ?></div>
    <?php endif; ?>
    <?php if (!empty($success_message)): // Display success message from session ?>
        <div class="success-message" style="margin-top: 1rem; background-color: #D1FAE5; color: #065F46; padding: 1rem; border-radius: 0.5rem;"><?php echo sanitize_output($success_message); ?></div>
    <?php endif; ?>
    <?php if (!empty($info_message)): // Display info message from session ?>
         <div class="info-message" style="margin-top: 1rem; background-color: #FEF3C7; color: #92400E; padding: 1rem; border-radius: 0.5rem;"><?php echo sanitize_output($info_message); ?></div>
    <?php endif; ?>


    <!-- Payment Method Selection -->
    <div id="payment-options" style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
        
        <!-- UPI Option -->
        <button type="button" class="btn btn-payment-method" data-method="UPI" style="text-align: left; background-color: #f3f4f6;">
            <strong>UPI / QR Code</strong><br>
            <span style="font-size: 0.9em; color: var(--text-color);">Pay instantly using any UPI app.</span>
        </button>

        <!-- Card Option -->
        <button type="button" class="btn btn-payment-method" data-method="Card" style="text-align: left; background-color: #f3f4f6;">
            <strong>Credit / Debit Card</strong><br>
            <span style="font-size: 0.9em; color: var(--text-color);">Visa, MasterCard, RuPay accepted.</span>
        </button>

         <!-- Add other methods like Net Banking if desired -->

    </div>

    <!-- Hidden Forms for Confirmation -->
    <!-- UPI Confirmation (Simulated) -->
    <!-- THE CHANGES ARE HERE: Added UPI ID input field -->
    <div id="upi-confirmation" style="display: none; margin-top: 1.5rem; border: 1px solid var(--border-color); padding: 1rem; border-radius: 0.5rem;">
        <h4>Confirm UPI Payment</h4>
        <p>Please enter your UPI ID below and confirm that you have completed the payment of ₹<?php echo number_format($rent_amount, 2); ?> externally.</p>
        
        <form action="dashboard.php?page=make_payment" method="POST" class="auth-form" style="text-align: left; margin-top: 1rem;">
            <input type="hidden" name="payment_method" value="UPI">
            <div class="form-group">
                <label for="upi_id">Your UPI ID</label>
                <input type="text" name="upi_id" id="upi_id" placeholder="yourname@bank" required 
                       pattern="^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z]{2,64}$" 
                       title="Please enter a valid UPI ID (e.g., yourname@bank)">
            </div>
            <button type="submit" name="confirm_payment" class="btn btn-primary">Confirm Payment Made</button>
             <button type="button" class="btn btn-cancel-payment" style="background-color: var(--text-color); color: white; margin-left: 0.5rem;">Cancel</button>
        </form>
    </div>

    <!-- Card Details Form (Simulated) -->
    <div id="card-form" style="display: none; margin-top: 1.5rem; border: 1px solid var(--border-color); padding: 1rem; border-radius: 0.5rem;">
        <h4>Enter Card Details</h4>
         <form action="dashboard.php?page=make_payment" method="POST" class="auth-form" style="text-align: left;">
            <input type="hidden" name="payment_method" value="Card">
            <div class="form-group">
                <label for="card_number">Card Number</label>
                <input type="text" name="card_number" id="card_number" placeholder="XXXX XXXX XXXX XXXX" required title="Enter 15 or 16 digit card number">
            </div>
             <div class="form-group" style="display: flex; gap: 1rem;">
                <div style="flex: 1;">
                    <label for="expiry">Expiry (MM/YY)</label>
                    <input type="text" name="expiry" id="expiry" placeholder="MM/YY" required pattern="\d{2}/\d{2}" title="Enter expiry date as MM/YY">
                </div>
                <div style="flex: 1;">
                    <label for="cvv">CVV</label>
                    <input type="text" name="cvv" id="cvv" placeholder="XXX" required pattern="\d{3,4}" title="Enter 3 or 4 digit CVV">
                </div>
            </div>
            <button type="submit" name="confirm_payment" class="btn btn-primary">Pay ₹<?php echo number_format($rent_amount, 2); ?></button>
             <button type="button" class="btn btn-cancel-payment" style="background-color: var(--text-color); color: white; margin-left: 0.5rem;">Cancel</button>
        </form>
    </div>

</div>

<!-- JavaScript to handle showing/hiding payment details -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const paymentOptionsDiv = document.getElementById('payment-options');
        const upiConfirmationDiv = document.getElementById('upi-confirmation');
        const cardFormDiv = document.getElementById('card-form');
        const paymentMethodButtons = document.querySelectorAll('.btn-payment-method');
        const cancelButtons = document.querySelectorAll('.btn-cancel-payment');

        paymentMethodButtons.forEach(button => {
            button.addEventListener('click', function() {
                const method = this.getAttribute('data-method');
                
                // Hide initial options
                paymentOptionsDiv.style.display = 'none';

                // Show the relevant confirmation/form section
                if (method === 'UPI') {
                    upiConfirmationDiv.style.display = 'block';
                } else if (method === 'Card') {
                    cardFormDiv.style.display = 'block';
                } 
                // Add else if for other methods if needed
            });
        });

        cancelButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Hide confirmation/form sections
                upiConfirmationDiv.style.display = 'none';
                cardFormDiv.style.display = 'none';
                // Show initial options again
                paymentOptionsDiv.style.display = 'flex';
                // Clear potential error messages
                const errorDiv = document.querySelector('.error-message');
                if (errorDiv) {
                    // Instead of hiding, maybe clear the text or remove the element
                    errorDiv.textContent = ''; 
                    errorDiv.style.display = 'none'; // Keep hiding if preferred
                }
            });
        });
    });
</script>

