<?php
// expenses.php
// This page allows owners to log and view expenses for their properties.

$success_message = '';
$error_message = '';

// --- HANDLE ADD EXPENSE FORM ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_expense'])) {
    // Sanitize user input
    $property_id = $conn->real_escape_string($_POST['property_id']);
    $expense_date = $conn->real_escape_string($_POST['expense_date']);
    $category = $conn->real_escape_string($_POST['category']);
    $amount = $conn->real_escape_string($_POST['amount']);
    $description = $conn->real_escape_string($_POST['description']);

    // Validate that the property belongs to the owner
    $sql_check_prop = "SELECT id FROM properties WHERE id = '$property_id' AND owner_id = '$user_id'";
    $result_check = $conn->query($sql_check_prop);

    if ($result_check->num_rows == 1) {
        // Property is valid, insert the expense
        $sql_insert = "INSERT INTO expenses (owner_id, property_id, expense_date, category, amount, description) 
                       VALUES ('$user_id', '$property_id', '$expense_date', '$category', '$amount', '$description')";
        
        if ($conn->query($sql_insert) === TRUE) {
            $success_message = "Expense of ₹" . number_format($amount, 2) . " for '" . htmlspecialchars($category) . "' was logged successfully!";
        } else {
            $error_message = "Error logging expense. Please try again.";
        }
    } else {
        // User does not own this property or property doesn't exist
        $error_message = "Invalid property selected.";
    }
}

// --- Fetch Data for Forms and Tables ---

// Fetch all properties for this owner (for the dropdown)
$sql_owner_props = "SELECT id, address FROM properties WHERE owner_id = '$user_id'";
$result_owner_props = $conn->query($sql_owner_props);

// Fetch all past expenses for this owner
$sql_expenses = "SELECT 
    e.expense_date, e.category, e.amount, e.description,
    p.address AS property_address
    FROM expenses e
    JOIN properties p ON e.property_id = p.id
    WHERE e.owner_id = '$user_id'
    ORDER BY e.expense_date DESC";
$result_expenses = $conn->query($sql_expenses);

?>

<!-- Add Expense Form -->
<div class="content-card">
    <h2>Log a New Expense</h2>

    <?php if (!empty($success_message)): ?>
        <div class="success-message" style="background-color: #D1FAE5; color: #065F46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;"><?php echo $success_message; ?></div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form action="dashboard.php?page=expenses" method="POST" class="auth-form" style="text-align: left;">
        <div class="form-group">
            <label for="property_id">For Property</label>
            <select name="property_id" id="property_id" required>
                <option value="">-- Choose a property --</option>
                <?php while($row = $result_owner_props->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['address']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="expense_date">Date of Expense</label>
            <input type="date" name="expense_date" id="expense_date" required>
        </div>

        <div class="form-group">
            <label for="category">Category</label>
            <select name="category" id="category" required>
                <option value="">-- Select a category --</option>
                <option value="Repairs">Repairs</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Taxes">Property Taxes</option>
                <option value="Insurance">Insurance</option>
                <option value="Utilities">Utilities (if paid by owner)</option>
                <option value="Other">Other</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="amount">Amount (in ₹)</label>
            <input type="number" name="amount" id="amount" step="0.01" min="0" placeholder="e.g., 1500.50" required>
        </div>

        <div class="form-group">
            <label for="description">Description (Optional)</label>
            <input type="text" name="description" id="description" placeholder="e.g., Replaced kitchen faucet">
        </div>

        <button type="submit" name="add_expense" class="btn btn-primary">Log Expense</button>
    </form>
</div>

<!-- Past Expenses Table -->
<div class="content-card" style="margin-top: 2rem;">
    <h2>Expense History</h2>
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Property</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result_expenses->num_rows > 0): ?>
                    <?php while($row = $result_expenses->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d M, Y', strtotime($row['expense_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['property_address']); ?></td>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>₹<?php echo number_format($row['amount'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No expenses logged yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
