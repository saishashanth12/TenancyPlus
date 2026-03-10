<?php
// dashboard_home.php
// This is the main overview page for both owners and tenants.

// --- OWNER-SPECIFIC DATA ---
if ($user_role == 'owner') {
    // 1. Fetch Stats Cards Data
    $sql_total_props = "SELECT COUNT(id) AS total_props FROM properties WHERE owner_id = '$user_id'";
    $total_props = $conn->query($sql_total_props)->fetch_assoc()['total_props'];

    $sql_occupied_props = "SELECT COUNT(id) AS occupied_props FROM properties WHERE owner_id = '$user_id' AND status = 'occupied'";
    $occupied_props = $conn->query($sql_occupied_props)->fetch_assoc()['occupied_props'];
    
    // NEW: Calculate vacant properties for the pie chart
    $vacant_props = $total_props - $occupied_props;

    $sql_pending_complaints = "SELECT COUNT(id) AS pending_complaints FROM complaints WHERE owner_id = '$user_id' AND status = 'pending'";
    $pending_complaints = $conn->query($sql_pending_complaints)->fetch_assoc()['pending_complaints'];
    
    $sql_total_tenants = "SELECT COUNT(id) AS total_tenants FROM tenants WHERE owner_id = '$user_id' AND lease_end_date IS NULL";
    $total_tenants = $conn->query($sql_total_tenants)->fetch_assoc()['total_tenants'];

    // 2. Fetch Action Items Data
    $sql_complaints_list = "SELECT c.id, c.subject, p.address 
                            FROM complaints c
                            JOIN properties p ON c.property_id = p.id
                            WHERE c.owner_id = '$user_id' AND c.status = 'pending'
                            ORDER BY c.created_at DESC LIMIT 5";
    $result_complaints_list = $conn->query($sql_complaints_list);
    
    $current_month = date('n');
    $current_year = date('Y');
    $sql_rent_due_list = "SELECT u.full_name, p.address
                          FROM tenants t
                          JOIN users u ON t.tenant_id = u.id
                          JOIN properties p ON t.property_id = p.id
                          WHERE t.owner_id = '$user_id' AND t.lease_end_date IS NULL
                          AND NOT EXISTS (
                              SELECT 1 FROM payments pm 
                              WHERE pm.tenant_id = t.tenant_id 
                              AND pm.payment_month = '$current_month' 
                              AND pm.payment_year = '$current_year'
                          )";
    $result_rent_due_list = $conn->query($sql_rent_due_list);

    // 3. --- CHART DATA LOGIC ---
    // We will prepare data for the last 6 months
    $chart_labels = [];
    $income_data = [];
    $expense_data = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $month = date('n', strtotime("-$i months"));
        $year = date('Y', strtotime("-$i months"));
        $month_name = date('M', strtotime("-$i months")); // e.g., "Oct"
        
        $chart_labels[] = $month_name;

        // Get total income (payments) for this month
        $sql_income = "SELECT SUM(amount) AS total_income FROM payments WHERE owner_id = '$user_id' AND payment_month = '$month' AND payment_year = '$year'";
        $income_result = $conn->query($sql_income)->fetch_assoc();
        $income_data[] = $income_result['total_income'] ?? 0; // Use 0 if no income

        // Get total expenses for this month
        $sql_expenses = "SELECT SUM(amount) AS total_expenses FROM expenses WHERE owner_id = '$user_id' AND MONTH(expense_date) = '$month' AND YEAR(expense_date) = '$year'";
        $expense_result = $conn->query($sql_expenses)->fetch_assoc();
        $expense_data[] = $expense_result['total_expenses'] ?? 0; // Use 0 if no expenses
    }
    
    // Convert PHP arrays to JSON strings for JavaScript
    $chart_labels_json = json_encode($chart_labels);
    $income_data_json = json_encode($income_data);
    $expense_data_json = json_encode($expense_data);

    // NEW: Data for the Pie Chart
    $pie_chart_labels_json = json_encode(['Occupied', 'Vacant']);
    $pie_chart_data_json = json_encode([$occupied_props, $vacant_props]);

}
?>

<!-- HTML for the page -->

<?php if ($user_role == 'owner'): ?>
<!-- OWNER DASHBOARD -->
    
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <h3>Total Properties</h3>
            <p class="stat-value"><?php echo $total_props; ?></p>
        </div>
        <div class="stat-card success">
            <h3>Occupied Units</h3>
            <p class="stat-value"><?php echo $occupied_props; ?></p>
        </div>
        <div class="stat-card warning">
            <h3>Total Tenants</h3>
            <p class="stat-value"><?php echo $total_tenants; ?></p>
        </div>
         <div class="stat-card danger">
            <h3>Pending Complaints</h3>
            <p class="stat-value"><?php echo $pending_complaints; ?></p>
        </div>
    </div>

    <!-- 
      NEW CHART GRID
      This holds both charts and places them side-by-side.
    -->
    <div class="chart-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem;">

        <!-- Bar Chart Card -->
        <div class="content-card">
            <h2>Financial Overview (Last 6 Months)</h2>
            <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
                <canvas id="financialChart"></canvas>
            </div>
        </div>

        <!-- NEW Pie Chart Card -->
        <div class="content-card">
            <h2>Property Status</h2>
            <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
                <canvas id="propertyPieChart"></canvas>
            </div>
        </div>
        
    </div>
    <!-- END CHART GRID -->


    <!-- Action Items Widget -->
    <div class="content-card" style="margin-top: 2rem;">
        <h2>Action Items</h2>
        
        <h3 style="margin-top: 1.5rem; font-size: 1.2rem; color: var(--danger-color);">Overdue Rent Payments (<?php echo date('F'); ?>)</h3>
        <div class="data-table-container" style="max-height: 200px; overflow-y: auto;">
            <table class="data-table">
                <tbody>
                    <?php if ($result_rent_due_list->num_rows > 0): ?>
                        <?php while($row = $result_rent_due_list->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong> at <?php echo htmlspecialchars($row['address']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td style="text-align: center;">All rent payments are up to date!</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h3 style="margin-top: 1.5rem; font-size: 1.2rem; color: var(--warning-color);">Pending Complaints</h3>
        <div class="data-table-container" style="max-height: 200px; overflow-y: auto;">
             <table class="data-table">
                <tbody>
                    <?php if ($result_complaints_list->num_rows > 0): ?>
                        <?php while($row = $result_complaints_list->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['subject']); ?></strong> at <?php echo htmlspecialchars($row['address']); ?></td>
                                <td style="text-align: right;">
                                    <a href="dashboard.php?page=complaints" class="btn" style="padding: 5px 10px; font-size: 0.9rem;">View</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="2" style="text-align: center;">No pending complaints.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>


<?php elseif ($user_role == 'tenant'): ?>
<!-- TENANT DASHBOARD -->
    
    <div class="content-card">
        <h2>Quick Actions</h2>
        <p>Welcome to your dashboard. Here are some quick links to manage your tenancy.</p>
        <div class="quick-actions" style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <a href="dashboard.php?page=rent_status" class="btn btn-primary">View Rent Status</a>
            <a href="dashboard.php?page=submit_complaint" class="btn" style="background-color: var(--secondary-color); color: white;">Submit a Complaint</a>
        </div>
    </div>

<?php endif; ?>


<!-- 
  NEW JAVASCRIPT FOR BOTH CHARTS
  This script will only run if the user is an owner.
-->
<?php if ($user_role == 'owner'): ?>
<script>
    // This script runs after the page is loaded
    document.addEventListener("DOMContentLoaded", function() {
        
        // --- 1. FINANCIAL BAR CHART ---
        const barCtx = document.getElementById('financialChart').getContext('2d');
        const chartLabels = <?php echo $chart_labels_json; ?>;
        const incomeData = <?php echo $income_data_json; ?>;
        const expenseData = <?php echo $expense_data_json; ?>;

        const financialChart = new Chart(barCtx, {
            type: 'bar', // We are making a bar chart
            data: {
                labels: chartLabels, // X-axis labels (e.g., "Jan", "Feb")
                datasets: [
                    {
                        label: 'Income (₹)',
                        data: incomeData,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)', // Blue
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Expenses (₹)',
                        data: expenseData,
                        backgroundColor: 'rgba(239, 68, 68, 0.7)', // Red
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true, // Make it responsive
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            // Format the y-axis to show "₹"
                            callback: function(value, index, values) {
                                return '₹' + value;
                            }
                        }
                    }
                }
            }
        });

        // --- 2. PROPERTY STATUS PIE CHART ---
        const pieCtx = document.getElementById('propertyPieChart').getContext('2d');
        const pieChartLabels = <?php echo $pie_chart_labels_json; ?>;
        const pieChartData = <?php echo $pie_chart_data_json; ?>;

        const propertyPieChart = new Chart(pieCtx, {
            type: 'pie', // This time, it's a pie chart
            data: {
                labels: pieChartLabels,
                datasets: [{
                    label: 'Property Status',
                    data: pieChartData,
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.7)', // Green for Occupied
                        'rgba(209, 213, 219, 0.7)' // Grey for Vacant
                    ],
                    borderColor: [
                        'rgba(16, 185, 129, 1)',
                        'rgba(209, 213, 219, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom', // Move labels to the bottom
                    }
                }
            }
        });

    });
</script>
<?php endif; ?>

