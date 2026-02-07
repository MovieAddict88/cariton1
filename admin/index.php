<?php
/**
 * Admin Dashboard - Live Monitoring
 * Car Rental System
 */

$page_title = 'Dashboard';
require_once 'includes/header.php';

$success_message = '';
$error_message = '';

// Handle verification from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_payment'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE payments SET status = 'verified', verified_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$_POST['payment_id']]);

        // Also update booking status if it was pending downpayment
        $stmt = $pdo->prepare("UPDATE bookings SET downpayment_paid = 1, booking_status = 'confirmed'
                             WHERE id = (SELECT booking_id FROM payments WHERE id = ?)");
        $stmt->execute([$_POST['payment_id']]);

        $success_message = 'Payment verified successfully!';
    } catch (PDOException $e) {
        $error_message = 'Error verifying payment: ' . $e->getMessage();
    }
}

// Handle rejection from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_payment'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE payments SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$_POST['payment_id']]);
        $success_message = 'Payment rejected!';
    } catch (PDOException $e) {
        $error_message = 'Error rejecting payment: ' . $e->getMessage();
    }
}

// Get dashboard statistics
try {
    $pdo = getDBConnection();
    
    // Total revenue this month
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM bookings WHERE MONTH(created_at) = MONTH(CURDATE()) AND booking_status = 'completed'");
    $monthly_revenue = $stmt->fetch()['total'] ?? 0;
    
    // Active rentals
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'active'");
    $active_rentals = $stmt->fetch()['count'] ?? 0;
    
    // Pending approvals
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'");
    $pending_payments = $stmt->fetch()['count'] ?? 0;
    
    // Available vehicles
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM vehicles WHERE status = 'available'");
    $available_vehicles = $stmt->fetch()['count'] ?? 0;
    
    // Recent bookings
    $stmt = $pdo->query("SELECT b.*, u.first_name, u.last_name, v.make, v.model 
                         FROM bookings b 
                         LEFT JOIN users u ON b.user_id = u.id 
                         LEFT JOIN vehicles v ON b.vehicle_id = v.id 
                         ORDER BY b.created_at DESC LIMIT 5");
    $recent_bookings = $stmt->fetchAll();
    
    // Recent payments (Pending only)
    $stmt = $pdo->query("SELECT p.*, b.reference_number as booking_ref 
                         FROM payments p 
                         LEFT JOIN bookings b ON p.booking_id = b.id 
                         WHERE p.status = 'pending'
                         ORDER BY p.created_at DESC LIMIT 5");
    $recent_payments = $stmt->fetchAll();
    
    // Daily revenue for chart (last 7 days)
    $stmt = $pdo->query("SELECT DATE(created_at) as date, SUM(total_amount) as revenue 
                         FROM bookings 
                         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                         AND booking_status = 'completed' 
                         GROUP BY DATE(created_at) 
                         ORDER BY date ASC");
    $revenue_chart = $stmt->fetchAll();
    
    // Vehicle status distribution
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM vehicles GROUP BY status");
    $vehicle_status = $stmt->fetchAll();
    
} catch (PDOException $e) {
    // Real-time counts - show 0 when database is empty or has no records
    $monthly_revenue = 0;
    $active_rentals = 0;
    $pending_payments = 0;
    $available_vehicles = 0;
    $recent_bookings = [];
    $recent_payments = [];
    $revenue_chart = [];
    $vehicle_status = [];
}
?>

<div class="flex h-screen overflow-hidden">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- TopAppBar -->
        <header class="sticky top-0 z-30 flex items-center bg-white/80 dark:bg-background-dark/80 backdrop-blur-md p-4 justify-between border-b border-slate-200 dark:border-slate-800 lg:px-8">
            <button class="text-slate-900 dark:text-white flex size-10 items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors lg:hidden" onclick="toggleSidebar()">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h2 class="text-slate-900 dark:text-white text-lg font-bold leading-tight tracking-[-0.015em] flex-1 lg:text-left">Dashboard Overview</h2>
            
            <div class="flex items-center gap-4">
                <div class="hidden md:flex items-center gap-2 bg-slate-100 dark:bg-slate-800 rounded-lg px-3 py-1.5">
                    <span class="material-symbols-outlined text-sm">attach_money</span>
                    <select onchange="window.location.href='?currency='+this.value" class="bg-transparent border-none text-xs font-bold focus:ring-0 outline-none">
                        <?php foreach($CURRENCY_SYMBOLS as $code => $sym): ?>
                            <option value="<?= $code ?>" <?= $selected_currency === $code ? 'selected' : '' ?>><?= $code ?> (<?= $sym ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="size-10 rounded-full overflow-hidden bg-primary/20 flex items-center justify-center border-2 border-primary">
                    <img class="w-full h-full object-cover" src="https://ui-avatars.com/api/?name=Admin+User&background=137fec&color=fff"/>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto pb-24 lg:p-8 admin-content">
            <!-- Welcome Section -->
            <div class="px-4 pt-6 pb-2 lg:px-0">
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Welcome back, Administrator</p>
                <h1 class="text-2xl font-bold dark:text-white">System Status</h1>
            </div>

            <?php if ($success_message): ?>
                <div class="mx-4 lg:mx-0 mt-4 bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-400 text-emerald-700 dark:text-emerald-300 px-4 py-3 rounded-xl">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="mx-4 lg:mx-0 mt-4 bg-rose-100 dark:bg-rose-900/30 border border-rose-400 text-rose-700 dark:text-rose-300 px-4 py-3 rounded-xl">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <!-- Stats Section -->
            <div class="flex overflow-x-auto gap-4 p-4 no-scrollbar lg:px-0 lg:grid lg:grid-cols-4 lg:overflow-visible">
                <!-- Revenue -->
                <div class="flex min-w-[280px] lg:min-w-0 flex-col gap-2 rounded-xl p-6 bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex justify-between items-start">
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Monthly Revenue</p>
                        <div class="size-8 rounded-lg bg-primary/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary text-sm">payments</span>
                        </div>
                    </div>
                    <p class="text-slate-900 dark:text-white tracking-tight text-3xl font-bold leading-tight" data-stat="monthly-revenue">
                        <?= formatCurrency(convertCurrency($monthly_revenue, 'PHP', $selected_currency), $selected_currency) ?>
                    </p>
                    <div class="flex items-center gap-1 mt-1">
                        <span class="material-symbols-outlined text-emerald-500 text-sm">trending_up</span>
                        <p class="text-emerald-500 text-sm font-bold">+12.5% <span class="text-slate-400 dark:text-slate-500 font-normal">vs last month</span></p>
                    </div>
                </div>

                <!-- Active Rentals -->
                <div class="flex min-w-[280px] lg:min-w-0 flex-col gap-2 rounded-xl p-6 bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex justify-between items-start">
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Active Rentals</p>
                        <div class="size-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-emerald-500 text-sm">key</span>
                        </div>
                    </div>
                    <p class="text-slate-900 dark:text-white tracking-tight text-3xl font-bold leading-tight"><?= $active_rentals ?></p>
                    <div class="flex items-center gap-1 mt-1">
                        <span class="material-symbols-outlined text-emerald-500 text-sm">trending_up</span>
                        <p class="text-emerald-500 text-sm font-bold">+5.2% <span class="text-slate-400 dark:text-slate-500 font-normal">this week</span></p>
                    </div>
                </div>

                <!-- Pending -->
                <div class="flex min-w-[280px] lg:min-w-0 flex-col gap-2 rounded-xl p-6 bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex justify-between items-start">
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Pending Approvals</p>
                        <div class="size-8 rounded-lg bg-orange-500/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-orange-500 text-sm">pending_actions</span>
                        </div>
                    </div>
                    <p class="text-slate-900 dark:text-white tracking-tight text-3xl font-bold leading-tight"><?= $pending_payments ?></p>
                    <div class="flex items-center gap-1 mt-1">
                        <span class="material-symbols-outlined text-orange-500 text-sm">notification_important</span>
                        <p class="text-orange-500 text-sm font-bold">Requires Action</p>
                    </div>
                </div>

                <!-- Fleet -->
                <div class="flex min-w-[280px] lg:min-w-0 flex-col gap-2 rounded-xl p-6 bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex justify-between items-start">
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Available Fleet</p>
                        <div class="size-8 rounded-lg bg-purple-500/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-purple-500 text-sm">directions_car</span>
                        </div>
                    </div>
                    <p class="text-slate-900 dark:text-white tracking-tight text-3xl font-bold leading-tight"><?= $available_vehicles ?></p>
                    <div class="flex items-center gap-1 mt-1">
                        <span class="material-symbols-outlined text-emerald-500 text-sm">check_circle</span>
                        <p class="text-emerald-500 text-sm font-bold">Ready to rent</p>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 px-4 py-2 lg:px-0">
                <!-- Revenue Chart -->
                <div class="lg:col-span-2 bg-white dark:bg-surface-dark rounded-xl p-4 sm:p-6 border border-slate-200 dark:border-slate-800 shadow-sm chart-card">
                    <div class="flex items-center justify-between mb-4 sm:mb-6">
                        <div>
                            <h2 class="text-slate-900 dark:text-white text-base sm:text-lg font-bold leading-tight tracking-[-0.015em]">Revenue Trends</h2>
                            <p class="text-slate-500 dark:text-slate-400 text-xs">Last 7 days revenue</p>
                        </div>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Fleet Status -->
                <div class="bg-white dark:bg-surface-dark rounded-xl p-4 sm:p-6 border border-slate-200 dark:border-slate-800 shadow-sm chart-card">
                    <h2 class="text-slate-900 dark:text-white text-base sm:text-lg font-bold mb-4">Fleet Status</h2>
                    <div class="chart-wrap">
                        <canvas id="fleetChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 px-4 py-6 lg:px-0">
                <!-- Recent Bookings -->
                <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                        <h2 class="text-lg font-bold">Recent Bookings</h2>
                        <a href="bookings.php" class="text-primary text-sm font-bold">View All</a>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php if(empty($recent_bookings)): ?>
                            <div class="p-8 text-center text-slate-500">No recent bookings found.</div>
                        <?php else: ?>
                            <?php foreach ($recent_bookings as $booking): ?>
                                <div class="px-6 py-4 flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="size-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-slate-400">confirmation_number</span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-sm"><?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?></p>
                                            <p class="text-xs text-slate-500"><?= htmlspecialchars($booking['make'] . ' ' . $booking['model']) ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-sm"><?= formatCurrency(convertCurrency($booking['total_amount'], 'PHP', $selected_currency), $selected_currency) ?></p>
                                        <div class="flex flex-col items-end gap-0.5 mt-1">
                                            <p class="text-[9px] font-bold text-emerald-600">DP: <?= formatCurrency(convertCurrency($booking['downpayment_amount'], 'PHP', $selected_currency), $selected_currency) ?></p>
                                            <p class="text-[9px] font-bold text-orange-600">Bal: <?= formatCurrency(convertCurrency($booking['balance_amount'], 'PHP', $selected_currency), $selected_currency) ?></p>
                                        </div>
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase <?= 
                                            $booking['booking_status'] === 'completed' ? 'bg-green-100 text-green-700' : 
                                            ($booking['booking_status'] === 'active' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700')
                                        ?>">
                                            <?= $booking['booking_status'] ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pending Payments -->
                <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                        <h2 class="text-lg font-bold">Pending Approvals</h2>
                        <a href="payments.php?status=pending" class="text-primary text-sm font-bold">View All</a>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php if(empty($recent_payments)): ?>
                            <div class="p-8 text-center text-slate-500">No pending approvals.</div>
                        <?php else: ?>
                            <?php foreach ($recent_payments as $payment): ?>
                                <div class="px-6 py-4 flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="size-10 rounded-lg bg-orange-100 dark:bg-orange-900/20 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-orange-600">receipt_long</span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-sm"><?= htmlspecialchars($payment['booking_ref']) ?></p>
                                            <p class="text-xs text-slate-500"><?= htmlspecialchars(ucfirst($payment['payment_method'])) ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="text-right">
                                            <p class="font-bold text-sm"><?= formatCurrency(convertCurrency($payment['amount'], 'PHP', $selected_currency), $selected_currency) ?></p>
                                        </div>
                                        <?php if ($payment['status'] === 'pending'): ?>
                                            <div class="flex gap-2">
                                                <form method="POST">
                                                    <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                                    <button type="submit" name="verify_payment" class="bg-primary text-white text-[10px] font-bold px-3 py-1.5 rounded-lg hover:bg-primary/90 transition-colors cursor-pointer">Approve</button>
                                                </form>
                                                <form method="POST">
                                                    <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                                    <button type="submit" name="reject_payment" class="bg-rose-500 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg hover:bg-rose-600 transition-colors cursor-pointer">Reject</button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-[10px] font-bold uppercase text-slate-400">Verified</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Charts initialization
    document.addEventListener('DOMContentLoaded', function() {
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($revenue_chart, 'date') ?: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode(array_map(function($r) use ($selected_currency) { 
                        return round(convertCurrency($r['revenue'], 'PHP', $selected_currency), 2); 
                    }, $revenue_chart) ?: [1200, 1900, 1500, 2500, 2200, 3000, 2800]) ?>,
                    borderColor: '#137fec',
                    backgroundColor: 'rgba(19, 128, 236, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(156, 163, 175, 0.1)' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // Fleet Status Chart
        const fleetCtx = document.getElementById('fleetChart').getContext('2d');
        new Chart(fleetCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($vehicle_status, 'status') ?: ['Available', 'Rented', 'Maintenance']) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($vehicle_status, 'count') ?: [45, 124, 12]) ?>,
                    backgroundColor: ['#10b981', '#137fec', '#f59e0b', '#ef4444']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } },
                cutout: '70%'
            }
        });
    });
</script>

<script>
// Real-time dashboard updates
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh dashboard stats every 30 seconds
    setInterval(function() {
        fetch('ajax_refresh_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update dashboard stats
                    const stats = data.data;
                    
                    // Update stats cards if elements exist
                    const monthlyRevenueEl = document.querySelector('[data-stat="monthly-revenue"]');
                    const activeRentalsEl = document.querySelector('[data-stat="active-rentals"]');
                    const pendingPaymentsEl = document.querySelector('[data-stat="pending-payments"]');
                    const availableVehiclesEl = document.querySelector('[data-stat="available-vehicles"]');
                    
                    if (monthlyRevenueEl) {
                        monthlyRevenueEl.textContent = formatCurrency(stats.monthly_revenue);
                    }
                    if (activeRentalsEl) {
                        activeRentalsEl.textContent = stats.bookings_month; // Using monthly as active rentals proxy
                    }
                    if (pendingPaymentsEl) {
                        pendingPaymentsEl.textContent = stats.pending_payments;
                    }
                    if (availableVehiclesEl) {
                        availableVehiclesEl.textContent = stats.available_vehicles;
                    }
                    
                    // Update charts if they exist
                    if (window.revenueChart) {
                        // Refresh revenue chart with new data
                        refreshRevenueChart();
                    }
                    if (window.fleetChart) {
                        // Refresh fleet chart with new data
                        refreshFleetChart();
                    }
                }
            })
            .catch(error => console.log('Dashboard refresh failed:', error));
    }, 30000);
    
    // Helper function to format currency
    function formatCurrency(amount) {
        return '₱' + parseFloat(amount).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    // Helper functions to refresh charts
    function refreshRevenueChart() {
        // This would fetch new revenue data and update the chart
        // Implementation depends on your charting library
    }
    
    function refreshFleetChart() {
        // This would fetch new fleet data and update the chart
        // Implementation depends on your charting library
    }
});
</script>

<?php include 'includes/footer.php'; ?>
