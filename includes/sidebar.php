<?php
// includes/sidebar.php
// This file generates the navigation sidebar for the dashboard.
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-logo">Tenancy+</a>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <!-- Common link for all users -->
            <li>
                <a href="dashboard.php?page=dashboard" class="nav-link">Dashboard</a>
            </li>

            <?php // --- Role-Specific Links --- ?>

            <?php if ($user_role == 'owner'): ?>
                <li>
                    <a href="dashboard.php?page=properties" class="nav-link">My Properties</a>
                </li>
                <li>
                    <a href="dashboard.php?page=tenants" class="nav-link">My Tenants</a>
                </li>
                <li>
                    <a href="dashboard.php?page=payments" class="nav-link">Payments</a>
                </li>
                <li>
                    <a href="dashboard.php?page=expenses" class="nav-link">Expenses</a>
                </li>
                <li>
                    <a href="dashboard.php?page=complaints" class="nav-link">View Complaints</a>
                </li>
                <!-- NEW LINK ADDED HERE -->
                <li>
                    <a href="dashboard.php?page=reports" class="nav-link">Reports</a>
                </li>
            
            <?php elseif ($user_role == 'tenant'): ?>
                <li>
                    <a href="dashboard.php?page=rent_status" class="nav-link">Rent Status</a>
                </li>
                <li>
                    <a href="dashboard.php?page=submit_complaint" class="nav-link">Submit Complaint</a>
                </li>
            <?php endif; ?>

            <!-- --- Common Links for All Users --- -->
            <li style="border-top: 1px solid var(--border-color); margin-top: 8px; padding-top: 8px;">
                <a href="dashboard.php?page=profile" class="nav-link">My Profile</a>
            </li>
            <li>
                <a href="logout.php" class="nav-link logout-link">Logout</a>
            </li>
        </ul>
    </nav>
</aside>

