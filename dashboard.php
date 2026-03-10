<?php
// dashboard.php
// This is the central hub for logged-in users.

require_once 'config/db_connect.php';
check_login();

$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$sql = "SELECT full_name FROM users WHERE id = $user_id";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
$full_name = $user['full_name'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Tenancy+</title>
    <link rel="stylesheet" href="css/style.css">
    
    <!-- This imports the Chart.js library so we can create charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body>
    
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="dashboard-header">
                <h1>Welcome back, <?php echo htmlspecialchars($full_name); ?>!</h1>
                <p>Here's your overview for today.</p>
            </header>

            <div class="dashboard-widgets">
                <?php
                // This is the routing logic.
                // It checks for a 'page' parameter in the URL.
                $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard'; 

                if ($user_role == 'owner') {
                    switch ($page) {
                        case 'properties': include 'properties.php'; break;
                        case 'tenants': include 'tenants.php'; break;
                        case 'payments': include 'payments.php'; break;
                        case 'expenses': include 'expenses.php'; break; 
                        case 'complaints': include 'complaints.php'; break;
                        // NEW ROUTE ADDED HERE
                        case 'reports': include 'reports.php'; break;
                        case 'profile': include 'profile.php'; break;
                        case 'dashboard': default: include 'dashboard_home.php'; break;
                    }
                } elseif ($user_role == 'tenant') {
                    switch ($page) {
                        case 'submit_complaint': include 'submit_complaint.php'; break;
                        case 'rent_status': include 'rent_status.php'; break;
                        case 'make_payment': include 'make_payment.php'; break;
                        case 'profile': include 'profile.php'; break;
                        case 'dashboard': default: include 'dashboard_home.php'; break;
                    }
                }
                ?>
            </div>
        </main>
    </div>

    <?php include 'chatbot.php'; ?>

</body>
</html>

