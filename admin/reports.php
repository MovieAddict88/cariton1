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
    // Professional HTML report generation
    $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Cariton Analytics Report</title>';
    $html .= '<style>
                body { font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #1e293b; background: #f8fafc; margin: 0; padding: 40px; }
                .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0; }
                .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #137fec; padding-bottom: 20px; margin-bottom: 30px; }
                h1 { color: #137fec; margin: 0; font-size: 24px; }
                .date { color: #64748b; font-size: 14px; }
                .grid { display: grid; grid-template-cols: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
                .stat-card { background: #f1f5f9; padding: 15px; rounded-lg; text-align: center; border-radius: 8px; }
                .stat-value { display: block; font-size: 20px; font-weight: bold; color: #0f172a; }
                .stat-label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
                h2 { font-size: 18px; color: #334155; margin-top: 30px; margin-bottom: 15px; border-left: 4px solid #137fec; padding-left: 10px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th { background-color: #f8fafc; color: #475569; font-weight: 600; text-align: left; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; padding: 12px 8px; }
                td { padding: 12px 8px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
                tr:last-child td { border-bottom: none; }
                .currency { font-family: monospace; font-weight: 600; }
                .btn-print { background: #137fec; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; margin-bottom: 20px; }
                @media print { .no-print { display: none; } body { padding: 0; background: white; } .container { border: none; max-width: 100%; padding: 0; } }
              </style></head><body>';
    
    $html .= '<div class="container">';
    $html .= '<button class="btn-print no-print" onclick="window.print()">Print Report</button>';
    $html .= '<div class="header"><div><h1>Cariton Rental Services</h1><p style="margin:5px 0 0 0; color:#64748b;">Fleet Analytics & Revenue Report</p></div>';
    $html .= '<div class="date">Generated: ' . date('M d, Y H:i') . '</div></div>';
    
    $html .= '<h2>Activity Summary</h2>';
    $html .= '<div class="grid">';
    $html .= '<div class="stat-card"><span class="stat-value">' . ($stats['bookings_today'] ?? 0) . '</span><span class="stat-label">Bookings Today</span></div>';
    $html .= '<div class="stat-card"><span class="stat-value">' . ($stats['bookings_week'] ?? 0) . '</span><span class="stat-label">This Week</span></div>';
    $html .= '<div class="stat-card"><span class="stat-value">' . ($stats['bookings_month'] ?? 0) . '</span><span class="stat-label">This Month</span></div>';
    $html .= '</div>';
    
    $html .= '<h2>Revenue by Month</h2>';
    $html .= '<table><thead><tr><th>Month</th><th>Revenue</th></tr></thead><tbody>';
    foreach ($revenue_data as $month => $revenue) {
        $html .= '<tr><td>' . $month . '</td><td class="currency">₱' . number_format($revenue, 2) . '</td></tr>';
    }
    $html .= '</tbody></table>';
    
    if (!empty($vehicles)) {
        $html .= '<h2>Top Performing Vehicles</h2>';
        $html .= '<table><thead><tr><th>Vehicle Model</th><th style="text-align:center;">Total Trips</th><th>Total Revenue</th></tr></thead><tbody>';
        foreach ($vehicles as $vehicle) {
            $html .= '<tr><td>' . htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) . '</td>';
            $html .= '<td style="text-align:center;">' . $vehicle['trips'] . '</td>';
            $html .= '<td class="currency">₱' . number_format($vehicle['revenue'], 2) . '</td></tr>';
        }
        $html .= '</tbody></table>';
    }
    
    $html .= '<div style="margin-top:50px; padding-top:20px; border-top:1px solid #e2e8f0; font-size:11px; color:#94a3b8; text-align:center;">';
    $html .= 'This is an automatically generated system report. &copy; ' . date('Y') . ' Cariton Rental Management System.';
    $html .= '</div></div>';
    
    $html .= '<script>window.onload = () => { setTimeout(() => { window.print(); }, 500); }</script>';
    $html .= '</body></html>';
    
    // Set proper headers
    header('Content-Type: text/html; charset=utf-8');
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
