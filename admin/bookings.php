<?php
/**
 * Bookings Management - Admin Page
 */

$page_title = 'Bookings';
require_once 'includes/header.php';

$message = '';
$error = '';

try {
    $pdo = getDBConnection();
    
    // Handle status updates
    if (isset($_POST['update_status'])) {
        $stmt = $pdo->prepare("UPDATE bookings SET booking_status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['booking_id']]);
        $message = "Booking status updated to " . $_POST['status'];
    }

    // Get bookings
    $stmt = $pdo->query("SELECT b.*, u.first_name, u.last_name, v.make, v.model, v.plate_number 
                         FROM bookings b 
                         LEFT JOIN users u ON b.user_id = u.id 
                         LEFT JOIN vehicles v ON b.vehicle_id = v.id 
                         ORDER BY b.created_at DESC");
    $bookings = $stmt->fetchAll();

} catch (PDOException $e) {
    $bookings = [];
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="flex h-screen overflow-hidden">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="sticky top-0 z-30 flex items-center bg-white/80 dark:bg-background-dark/80 backdrop-blur-md p-4 justify-between border-b border-slate-200 dark:border-slate-800 lg:px-8">
            <div class="flex items-center gap-4">
                <button class="text-slate-900 dark:text-white flex size-10 items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full lg:hidden" onclick="toggleSidebar()">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <h2 class="text-slate-900 dark:text-white text-lg font-bold">Bookings Management</h2>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 lg:p-8 pb-24 admin-content">
            <?php if ($message): ?>
                <div class="bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-400 text-emerald-700 dark:text-emerald-300 px-4 py-3 rounded-xl mb-6">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="bg-white dark:bg-surface-dark rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="table-scroll overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[900px]">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50">
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Reference</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Customer</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Vehicle</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Dates</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Total</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-500">No bookings found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $b): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors">
                                        <td class="px-6 py-4 font-bold text-primary"><?= $b['reference_number'] ?></td>
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-sm"><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></p>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?= htmlspecialchars($b['make'] . ' ' . $b['model']) ?>
                                            <p class="text-xs text-slate-500"><?= $b['plate_number'] ?></p>
                                        </td>
                                        <td class="px-6 py-4 text-xs">
                                            <p><span class="text-slate-400">Pick:</span> <?= date('M d, Y', strtotime($b['pickup_date'])) ?></p>
                                            <p><span class="text-slate-400">Drop:</span> <?= date('M d, Y', strtotime($b['dropoff_date'])) ?></p>
                                        </td>
                                        <td class="px-6 py-4 font-bold">
                                            <?= formatCurrency(convertCurrency($b['total_amount'], 'PHP', $selected_currency), $selected_currency) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase <?= 
                                                $b['booking_status'] === 'completed' ? 'bg-green-100 text-green-700' : 
                                                ($b['booking_status'] === 'active' ? 'bg-blue-100 text-blue-700' : 
                                                ($b['booking_status'] === 'pending' ? 'bg-orange-100 text-orange-700' : 'bg-slate-100 text-slate-700'))
                                            ?>">
                                                <?= $b['booking_status'] ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                <input type="hidden" name="update_status" value="1">
                                                <select name="status" onchange="this.form.submit()" class="text-[10px] font-bold border-slate-200 dark:border-slate-800 rounded-lg bg-slate-50 dark:bg-slate-900 focus:ring-primary">
                                                    <option value="pending" <?= $b['booking_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="confirmed" <?= $b['booking_status'] === 'confirmed' ? 'selected' : '' ?>>Confirm</option>
                                                    <option value="active" <?= $b['booking_status'] === 'active' ? 'selected' : '' ?>>Set Active</option>
                                                    <option value="completed" <?= $b['booking_status'] === 'completed' ? 'selected' : '' ?>>Complete</option>
                                                    <option value="cancelled" <?= $b['booking_status'] === 'cancelled' ? 'selected' : '' ?>>Cancel</option>
                                                </select>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
