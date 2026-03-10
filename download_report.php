<?php
// download_report.php
// This script generates and downloads a comprehensive CSV report for the owner.

// Start session and connect to DB
require_once 'config/db_connect.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    // If not logged in or not an owner, redirect to login
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get date filters from the POST request (if provided)
$start_date = isset($_POST['start_date']) && !empty($_POST['start_date']) ? $conn->real_escape_string($_POST['start_date']) : null;
$end_date = isset($_POST['end_date']) && !empty($_POST['end_date']) ? $conn->real_escape_string($_POST['end_date']) : null;

// --- Prepare CSV File ---

// Set the filename
$filename = "tenancy_plus_report_" . date('Ymd_His') . ".csv";

// Set headers to force download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open the output stream
$output = fopen('php://output', 'w');

// --- Section 1: Properties ---
fputcsv($output, ['--- Properties ---']); // Section header
fputcsv($output, ['ID', 'Address', 'City', 'Rent Amount', 'Status']);

$sql_props = "SELECT id, address, city, rent_amount, status FROM properties WHERE owner_id = '$user_id'";
$result_props = $conn->query($sql_props);
if ($result_props->num_rows > 0) {
    while ($row = $result_props->fetch_assoc()) {
        fputcsv($output, $row);
    }
} else {
    fputcsv($output, ['No properties found.']);
}
fputcsv($output, []); // Add an empty row for spacing

// --- Section 2: Tenants (Active Leases) ---
fputcsv($output, ['--- Active Leases ---']);
fputcsv($output, ['Lease ID', 'Tenant Name', 'Tenant Email', 'Property Address', 'Lease Start Date']);

$sql_leases = "SELECT t.id, u.full_name, u.email, p.address, t.lease_start_date 
               FROM tenants t 
               JOIN users u ON t.tenant_id = u.id 
               JOIN properties p ON t.property_id = p.id 
               WHERE t.owner_id = '$user_id' AND t.lease_end_date IS NULL";
$result_leases = $conn->query($sql_leases);
if ($result_leases->num_rows > 0) {
    while ($row = $result_leases->fetch_assoc()) {
        fputcsv($output, $row);
    }
} else {
    fputcsv($output, ['No active leases found.']);
}
fputcsv($output, []); 

// --- Section 3: Payments ---
fputcsv($output, ['--- Payments ---']);
fputcsv($output, ['Payment Date', 'Tenant Name', 'Property Address', 'Amount', 'Payment Month', 'Payment Year', 'Method']);

$sql_payments = "SELECT p.payment_date, u.full_name, pr.address, p.amount, p.payment_month, p.payment_year, p.payment_method 
                 FROM payments p 
                 JOIN users u ON p.tenant_id = u.id 
                 JOIN properties pr ON p.property_id = pr.id 
                 WHERE p.owner_id = '$user_id'";
// Apply date filter
if ($start_date && $end_date) {
    $sql_payments .= " AND p.payment_date BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
} elseif ($start_date) {
    $sql_payments .= " AND p.payment_date >= '$start_date 00:00:00'";
} elseif ($end_date) {
     $sql_payments .= " AND p.payment_date <= '$end_date 23:59:59'";
}
$sql_payments .= " ORDER BY p.payment_date DESC";

$result_payments = $conn->query($sql_payments);
if ($result_payments->num_rows > 0) {
    while ($row = $result_payments->fetch_assoc()) {
        fputcsv($output, $row);
    }
} else {
    fputcsv($output, ['No payments found within the specified date range (if any).']);
}
fputcsv($output, []);

// --- Section 4: Expenses ---
fputcsv($output, ['--- Expenses ---']);
fputcsv($output, ['Expense Date', 'Property Address', 'Category', 'Description', 'Amount']);

$sql_expenses = "SELECT e.expense_date, p.address, e.category, e.description, e.amount 
                 FROM expenses e 
                 JOIN properties p ON e.property_id = p.id 
                 WHERE e.owner_id = '$user_id'";
// Apply date filter
if ($start_date && $end_date) {
    $sql_expenses .= " AND e.expense_date BETWEEN '$start_date' AND '$end_date'";
} elseif ($start_date) {
    $sql_expenses .= " AND e.expense_date >= '$start_date'";
} elseif ($end_date) {
     $sql_expenses .= " AND e.expense_date <= '$end_date'";
}
$sql_expenses .= " ORDER BY e.expense_date DESC";

$result_expenses = $conn->query($sql_expenses);
if ($result_expenses->num_rows > 0) {
    while ($row = $result_expenses->fetch_assoc()) {
        fputcsv($output, $row);
    }
} else {
    fputcsv($output, ['No expenses found within the specified date range (if any).']);
}
fputcsv($output, []);

// --- Section 5: Complaints ---
fputcsv($output, ['--- Complaints ---']);
fputcsv($output, ['Date Submitted', 'Tenant Name', 'Property Address', 'Subject', 'Description', 'Status']);

$sql_complaints = "SELECT c.created_at, u.full_name, p.address, c.subject, c.description, c.status 
                   FROM complaints c 
                   JOIN users u ON c.tenant_id = u.id 
                   JOIN properties p ON c.property_id = p.id 
                   WHERE c.owner_id = '$user_id'";
// Apply date filter
if ($start_date && $end_date) {
    $sql_complaints .= " AND DATE(c.created_at) BETWEEN '$start_date' AND '$end_date'";
} elseif ($start_date) {
    $sql_complaints .= " AND DATE(c.created_at) >= '$start_date'";
} elseif ($end_date) {
     $sql_complaints .= " AND DATE(c.created_at) <= '$end_date'";
}
$sql_complaints .= " ORDER BY c.created_at DESC";

$result_complaints = $conn->query($sql_complaints);
if ($result_complaints->num_rows > 0) {
    while ($row = $result_complaints->fetch_assoc()) {
        fputcsv($output, $row);
    }
} else {
    fputcsv($output, ['No complaints found within the specified date range (if any).']);
}

// Close the output stream
fclose($output);
exit(); // Important to stop the script after generating the file

?>

