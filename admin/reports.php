<?php
/**
 * Reports & Analytics - Admin Page
 */

$page_title = 'Reports';
require_once 'includes/header.php';

$message = '';
$error = '';

try {
    $pdo = getDBConnection();
    
    // Real-time data from database
    // Monthly revenue for last 6 months
    $stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%b') as month, SUM(total_amount) as revenue 
                         FROM bookings 
                         WHERE booking_status = 'completed' 
                         AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) 
                         GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
                         ORDER BY created_at ASC");
    $revenue_by_month = [];
    while ($row = $stmt->fetch()) {
        $revenue_by_month[$row['month']] = $row['revenue'] ?? 0;
    }
    
    // Fill missing months with 0
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $current_month = date('n') - 1; // 0-based index
    $last_6_months = [];
    for ($i = 5; $i >= 0; $i--) {
        $month_idx = ($current_month - $i + 12) % 12;
        $month_name = $months[$month_idx];
        $last_6_months[$month_name] = $revenue_by_month[$month_name] ?? 0;
    }
    
    // Top performing vehicles
    $stmt = $pdo->query("SELECT v.make, v.model, COUNT(b.id) as trips, SUM(b.total_amount) as revenue
                         FROM vehicles v 
                         LEFT JOIN bookings b ON v.id = b.vehicle_id AND b.booking_status = 'completed'
                         GROUP BY v.id 
                         HAVING trips > 0 
                         ORDER BY revenue DESC 
                         LIMIT 5");
    $top_vehicles = $stmt->fetchAll();
    
    // If no data, show empty state
    if (empty($top_vehicles)) {
        $top_vehicles = [];
    }
    
    // Recent activity stats
    $stmt = $pdo->query("SELECT 
                            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as bookings_week,
                            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as bookings_month,
                            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as bookings_today
                         FROM bookings");
    $activity_stats = $stmt->fetch();
    
    // Export to PDF
    if (isset($_POST['export_pdf'])) {
        generateReportPDF($pdo, $last_6_months, $top_vehicles, $activity_stats);
        exit;
    }
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $revenue_by_month = $last_6_months = [];
    $top_vehicles = [];
    $activity_stats = ['bookings_week' => 0, 'bookings_month' => 0, 'bookings_today' => 0];
}

function generateReportPDF($pdo, $revenue_data, $vehicles, $stats) {
    // Simple HTML to PDF generation using TCPDF-like approach
    $html = '<html><head><title>Cariton Reports</title>';
    $html .= '<style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { color: #137fec; }
                .stat { margin: 10px 0; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
              </style></head><body>';
    
    $html .= '<h1>Cariton Rental Services - Monthly Report</h1>';
    $html .= '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
    
    $html .= '<h2>Activity Summary</h2>';
    $html .= '<div class="stat">Bookings Today: ' . ($stats['bookings_today'] ?? 0) . '</div>';
    $html .= '<div class="stat">Bookings This Week: ' . ($stats['bookings_week'] ?? 0) . '</div>';
    $html .= '<div class="stat">Bookings This Month: ' . ($stats['bookings_month'] ?? 0) . '</div>';
    
    $html .= '<h2>Revenue by Month</h2>';
    $html .= '<table><tr><th>Month</th><th>Revenue (₱)</th></tr>';
    foreach ($revenue_data as $month => $revenue) {
        $html .= '<tr><td>' . $month . '</td><td>₱' . number_format($revenue, 2) . '</td></tr>';
    }
    $html .= '</table>';
    
    if (!empty($vehicles)) {
        $html .= '<h2>Top Performing Vehicles</h2>';
        $html .= '<table><tr><th>Vehicle</th><th>Trips</th><th>Revenue (₱)</th></tr>';
        foreach ($vehicles as $vehicle) {
            $html .= '<tr><td>' . htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) . '</td>';
            $html .= '<td>' . $vehicle['trips'] . '</td>';
            $html .= '<td>₱' . number_format($vehicle['revenue'], 2) . '</td></tr>';
        }
        $html .= '</table>';
    }
    
    $html .= '</body></html>';
    
    // Output as PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="cariton-report-' . date('Y-m-d') . '.pdf"');
    
    // Simple HTML to PDF conversion (in a real implementation, you'd use a library like TCPDF or Dompdf)
    echo $html;
}
?>

<div class="flex h-screen overflow-hidden">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="sticky top-0 z-30 flex items-center bg-white/80 dark:bg-background-dark/80 backdrop-blur-md p-4 justify-between border-b border-slate-200 dark:border-slate-800 lg:px-8">
            <h2 class="text-slate-900 dark:text-white text-lg font-bold">Analytics & Reports</h2>
            <form method="POST" class="inline">
                <button type="submit" name="export_pdf" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined">download</span>
                    Export PDF
                </button>
            </form>
        </header>

        <main class="flex-1 overflow-y-auto p-4 lg:p-8 pb-24 admin-content">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 lg:gap-8 mb-8">
                <div class="bg-white dark:bg-surface-dark rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-6 shadow-sm chart-card">
                    <h3 class="text-base sm:text-lg font-bold mb-4 sm:mb-6">Revenue Growth</h3>
                    <div class="chart-wrap">
                        <canvas id="growthChart"></canvas>
                    </div>
                </div>
                <div class="bg-white dark:bg-surface-dark rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-6 shadow-sm">
                    <h3 class="text-base sm:text-lg font-bold mb-4 sm:mb-6">Top Performing Vehicles</h3>
                    <?php if (empty($top_vehicles)): ?>
                        <div class="text-center py-8 text-slate-500">
                            <span class="material-symbols-outlined text-4xl mb-2">info</span>
                            <p>No vehicle data available yet.</p>
                            <p class="text-xs">Add some bookings to see performance metrics.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4 sm:space-y-6">
                            <?php foreach($top_vehicles as $v): ?>
                                <div>
                                    <div class="flex justify-between text-sm mb-2">
                                        <span class="font-bold"><?= htmlspecialchars($v['make'] . ' ' . $v['model']) ?></span>
                                        <span class="text-slate-500"><?= $v['trips'] ?> trips</span>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                                        <div class="bg-primary h-full" style="width: <?= ($v['trips'] / 50) * 100 ?>%"></div>
                                    </div>
                                    <p class="text-[10px] text-right mt-1 text-primary font-bold">₱<?= number_format($v['revenue']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Live Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-surface-dark rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-2">Today's Bookings</h3>
                    <p class="text-3xl font-bold text-emerald-500" data-stat="today"><?= $activity_stats['bookings_today'] ?? 0 ?></p>
                    <p class="text-xs text-slate-500 mt-1">Real-time count</p>
                </div>
                <div class="bg-white dark:bg-surface-dark rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-2">This Week</h3>
                    <p class="text-3xl font-bold text-primary" data-stat="week"><?= $activity_stats['bookings_week'] ?? 0 ?></p>
                    <p class="text-xs text-slate-500 mt-1">Real-time count</p>
                </div>
                <div class="bg-white dark:bg-surface-dark rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-2">This Month</h3>
                    <p class="text-3xl font-bold text-purple-500" data-stat="month"><?= $activity_stats['bookings_month'] ?? 0 ?></p>
                    <p class="text-xs text-slate-500 mt-1">Real-time count</p>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('growthChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($last_6_months)) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode(array_values($last_6_months)) ?>,
                    backgroundColor: '#137fec',
                    borderRadius: 8
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
        
        // Auto-refresh stats every 30 seconds
        setInterval(function() {
            fetch('ajax_refresh_stats.php')
                .then(response => response.json())
                .then(data => {
                    // Update real-time stats
                    document.querySelector('[data-stat="today"]').textContent = data.bookings_today;
                    document.querySelector('[data-stat="week"]').textContent = data.bookings_week;
                    document.querySelector('[data-stat="month"]').textContent = data.bookings_month;
                })
                .catch(error => console.log('Stats refresh failed:', error));
        }, 30000);
    });
</script>

<?php include 'includes/footer.php'; ?>
