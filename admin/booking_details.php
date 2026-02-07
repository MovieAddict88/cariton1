<?php
/**
 * Booking Details - Admin Page
 * Provides a comprehensive view of a single booking including customer, vehicle, driver, and map.
 */

$page_title = 'Booking Details';
require_once 'includes/header.php';

?>
<style>
    :root {
        --fluid-text-xs: clamp(0.6rem, 0.5rem + 0.5vw, 0.75rem);
        --fluid-text-sm: clamp(0.75rem, 0.7rem + 0.25vw, 0.875rem);
        --fluid-text-base: clamp(0.875rem, 0.8rem + 0.35vw, 1rem);
        --fluid-text-lg: clamp(1rem, 0.9rem + 0.5vw, 1.25rem);
        --fluid-text-xl: clamp(1.25rem, 1.1rem + 0.75vw, 1.75rem);
        --fluid-padding: clamp(1rem, 0.8rem + 1vw, 2rem);
    }
    .fluid-p { padding: var(--fluid-padding); }
    .text-fluid-xs { font-size: var(--fluid-text-xs); }
    .text-fluid-sm { font-size: var(--fluid-text-sm); }
    .text-fluid-base { font-size: var(--fluid-text-base); }
    .text-fluid-lg { font-size: var(--fluid-text-lg); }
    .text-fluid-xl { font-size: var(--fluid-text-xl); }
</style>
<?php

$booking_id = $_GET['id'] ?? null;
if (!$booking_id) {
    echo "<script>window.location.href='bookings.php';</script>";
    exit;
}

try {
    $pdo = getDBConnection();

    // Fetch detailed booking information
    $stmt = $pdo->prepare("
        SELECT b.*,
               u.first_name as user_first_name, u.last_name as user_last_name, u.email as user_email, u.phone as user_phone, u.address as user_address,
               v.make, v.model, v.plate_number, v.images, v.transmission, v.fuel_type, v.daily_rate as vehicle_daily_rate,
               d.first_name as driver_first_name, d.last_name as driver_last_name, d.phone as driver_phone, d.employee_id as driver_employee_id, d.status as driver_status
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        LEFT JOIN drivers d ON b.driver_id = d.id
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        echo "<div class='p-8 text-center'><p class='text-rose-500 font-bold'>Booking not found.</p><a href='bookings.php' class='text-primary underline mt-4 inline-block'>Back to Bookings</a></div>";
        include 'includes/footer.php';
        exit;
    }

    $message = '';
    $error = '';

    // Handle status updates
    if (isset($_POST['update_status'])) {
        $new_status = $_POST['status'];
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE bookings SET booking_status = ? WHERE id = ?");
            $stmt->execute([$new_status, $booking_id]);

            // If completed or cancelled, make vehicle available again
            if (in_array($new_status, ['completed', 'cancelled', 'no_show'])) {
                $stmt = $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE id = (SELECT vehicle_id FROM bookings WHERE id = ?)");
                $stmt->execute([$booking_id]);
            }
            // If active, mark vehicle as rented
            if ($new_status === 'active') {
                $stmt = $pdo->prepare("UPDATE vehicles SET status = 'rented' WHERE id = (SELECT vehicle_id FROM bookings WHERE id = ?)");
                $stmt->execute([$booking_id]);
            }
            // If confirmed, mark vehicle as reserved
            if ($new_status === 'confirmed') {
                $stmt = $pdo->prepare("UPDATE vehicles SET status = 'reserved' WHERE id = (SELECT vehicle_id FROM bookings WHERE id = ?)");
                $stmt->execute([$booking_id]);
            }

            $pdo->commit();
            $message = "Booking status updated to " . ucfirst($new_status);

            // Refresh booking data
            $stmt = $pdo->prepare("
                SELECT b.*,
                       u.first_name as user_first_name, u.last_name as user_last_name, u.email as user_email, u.phone as user_phone, u.address as user_address,
                       v.make, v.model, v.plate_number, v.images, v.transmission, v.fuel_type, v.daily_rate as vehicle_daily_rate,
                       d.first_name as driver_first_name, d.last_name as driver_last_name, d.phone as driver_phone, d.employee_id as driver_employee_id, d.status as driver_status
                FROM bookings b
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN vehicles v ON b.vehicle_id = v.id
                LEFT JOIN drivers d ON b.driver_id = d.id
                WHERE b.id = ?
            ");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch();

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error updating status: " . $e->getMessage();
        }
    }

    // Fetch payment history for this booking
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE booking_id = ? ORDER BY created_at DESC");
    $stmt->execute([$booking_id]);
    $payments = $stmt->fetchAll();

    $images = json_decode($booking['images'] ?? '[]', true);
    $vehicle_image = !empty($images) ? $images[0] : 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&q=80&w=400';

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<div class="flex h-screen overflow-hidden">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-h-0 overflow-hidden">
        <header class="flex-none z-30 flex items-center bg-white/80 dark:bg-background-dark/80 backdrop-blur-md p-4 justify-between border-b border-slate-200 dark:border-slate-800 lg:px-8">
            <div class="flex items-center gap-4">
                <a href="bookings.php" class="text-slate-900 dark:text-white flex size-10 items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full">
                    <span class="material-symbols-outlined">arrow_back</span>
                </a>
                <h2 class="text-slate-900 dark:text-white text-fluid-lg font-bold">Booking Details: <?= $booking['reference_number'] ?></h2>
            </div>
            <div class="flex gap-2">
                <button onclick="window.print()" class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white px-4 py-2 rounded-xl font-bold text-sm hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                    <span class="material-symbols-outlined text-sm">print</span>
                    Print
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto fluid-p admin-content" style="padding-bottom: 16rem !important;">
            <?php if ($message): ?>
                <div class="bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-400 text-emerald-700 dark:text-emerald-300 px-4 py-3 rounded-xl mb-6">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded-xl mb-6">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Left Column: Summary & Customer -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Main Status Card -->
                    <div class="bg-white dark:bg-surface-dark rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex flex-wrap justify-between items-start gap-4 mb-6">
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Booking Reference</p>
                                <h1 class="text-fluid-xl font-bold text-primary"><?= $booking['reference_number'] ?></h1>
                                <p class="text-xs text-slate-500 mt-1">Booked on <?= date('F j, Y \a\t g:i A', strtotime($booking['created_at'])) ?></p>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <span class="px-4 py-1.5 rounded-full text-xs font-bold uppercase <?=
                                    $booking['booking_status'] === 'completed' ? 'bg-emerald-100 text-emerald-700' :
                                    ($booking['booking_status'] === 'active' ? 'bg-blue-100 text-blue-700' :
                                    ($booking['booking_status'] === 'pending' ? 'bg-orange-100 text-orange-700' : 'bg-slate-100 text-slate-700'))
                                ?>">
                                    <?= $booking['booking_status'] ?>
                                </span>
                                <span class="px-4 py-1.5 rounded-full text-xs font-bold uppercase <?=
                                    $booking['payment_status'] === 'paid' ? 'bg-emerald-100 text-emerald-700' :
                                    ($booking['payment_status'] === 'partial' ? 'bg-orange-100 text-orange-700' : 'bg-rose-100 text-rose-700')
                                ?>">
                                    Payment: <?= $booking['payment_status'] ?>
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 py-6 border-y border-slate-100 dark:border-slate-800">
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Pickup Date</p>
                                <p class="font-bold text-sm"><?= date('M d, Y', strtotime($booking['pickup_date'])) ?></p>
                                <p class="text-xs text-slate-500"><?= date('g:i A', strtotime($booking['pickup_date'])) ?></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Dropoff Date</p>
                                <p class="font-bold text-sm"><?= date('M d, Y', strtotime($booking['dropoff_date'])) ?></p>
                                <p class="text-xs text-slate-500"><?= date('g:i A', strtotime($booking['dropoff_date'])) ?></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Duration</p>
                                <p class="font-bold text-sm"><?= $booking['rental_days'] ?> Days</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Total Amount</p>
                                <p class="font-bold text-lg text-slate-900 dark:text-white"><?= formatCurrency(convertCurrency($booking['total_amount'], 'PHP', $selected_currency), $selected_currency) ?></p>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="font-bold text-sm mb-3 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-lg">location_on</span>
                                    Pickup Details
                                </h3>
                                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-100 dark:border-slate-800/50">
                                    <p class="text-xs font-bold text-slate-400 uppercase mb-1">Pickup Address</p>
                                    <p class="text-sm font-semibold mb-1"><?= htmlspecialchars($booking['pickup_location']) ?></p>
                                    <?php if ($booking['pickup_description']): ?>
                                        <p class="text-[11px] text-slate-500 italic mt-2">"<?= htmlspecialchars($booking['pickup_description']) ?>"</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-bold text-sm mb-3 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-rose-500 text-lg">keyboard_return</span>
                                    Return Details
                                </h3>
                                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-100 dark:border-slate-800/50">
                                    <p class="text-xs font-bold text-slate-400 uppercase mb-1">Return Address</p>
                                    <p class="text-sm font-semibold mb-1"><?= htmlspecialchars($booking['dropoff_location'] ?: 'Same as pickup') ?></p>
                                    <?php if ($booking['dropoff_description']): ?>
                                        <p class="text-[11px] text-slate-500 italic mt-2">"<?= htmlspecialchars($booking['dropoff_description']) ?>"</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Maps Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <?php if ($booking['pickup_latitude'] && $booking['pickup_longitude']): ?>
                        <div class="bg-white dark:bg-surface-dark rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                            <div class="flex flex-wrap justify-between items-center mb-4 gap-4">
                                <h3 class="font-bold text-base flex items-center gap-2">
                                    Pickup Map
                                    <span class="text-[9px] text-emerald-500 flex items-center gap-1 px-2 py-0.5 bg-emerald-500/10 rounded-full">
                                        <span class="size-1 rounded-full bg-emerald-500 animate-pulse"></span>
                                        LIVE
                                    </span>
                                </h3>
                                <div class="flex gap-1">
                                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $booking['pickup_latitude'] ?>,<?= $booking['pickup_longitude'] ?>" target="_blank" class="size-8 flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-200 transition-colors">
                                        <span class="material-symbols-outlined text-base">map</span>
                                    </a>
                                    <a href="https://waze.com/ul?ll=<?= $booking['pickup_latitude'] ?>,<?= $booking['pickup_longitude'] ?>&navigate=yes" target="_blank" class="size-8 flex items-center justify-center bg-[#33ccff]/10 text-[#33ccff] rounded-lg hover:bg-[#33ccff]/20 transition-colors">
                                        <img src="https://www.vectorlogo.zone/logos/waze/waze-icon.svg" class="size-3.5" alt="Waze">
                                    </a>
                                </div>
                            </div>
                            <div id="detailsPickupMap" class="w-full h-64 rounded-xl border border-slate-100 dark:border-slate-800 z-0"></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($booking['dropoff_latitude'] && $booking['dropoff_longitude']): ?>
                        <div class="bg-white dark:bg-surface-dark rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                            <div class="flex flex-wrap justify-between items-center mb-4 gap-4">
                                <h3 class="font-bold text-base flex items-center gap-2">
                                    Return Map
                                </h3>
                                <div class="flex gap-1">
                                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $booking['dropoff_latitude'] ?>,<?= $booking['dropoff_longitude'] ?>" target="_blank" class="size-8 flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-200 transition-colors">
                                        <span class="material-symbols-outlined text-base">map</span>
                                    </a>
                                    <a href="https://waze.com/ul?ll=<?= $booking['dropoff_latitude'] ?>,<?= $booking['dropoff_longitude'] ?>&navigate=yes" target="_blank" class="size-8 flex items-center justify-center bg-[#33ccff]/10 text-[#33ccff] rounded-lg hover:bg-[#33ccff]/20 transition-colors">
                                        <img src="https://www.vectorlogo.zone/logos/waze/waze-icon.svg" class="size-3.5" alt="Waze">
                                    </a>
                                </div>
                            </div>
                            <div id="detailsDropoffMap" class="w-full h-64 rounded-xl border border-slate-100 dark:border-slate-800 z-0"></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Payment History -->
                    <div class="bg-white dark:bg-surface-dark rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                            <h3 class="font-bold text-lg">Payment History</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 dark:bg-slate-900/50">
                                    <tr>
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase">Ref</th>
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase">Method</th>
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase">Amount</th>
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase">Status</th>
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <?php if (empty($payments)): ?>
                                        <tr>
                                            <td colspan="5" class="px-6 py-8 text-center text-slate-500 text-sm">No payments recorded yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($payments as $p): ?>
                                            <tr class="text-sm">
                                                <td class="px-6 py-4 font-medium text-xs"><?= $p['reference_number'] ?></td>
                                                <td class="px-6 py-4 uppercase text-[10px] font-bold"><?= $p['payment_method'] ?></td>
                                                <td class="px-6 py-4 font-bold"><?= formatCurrency(convertCurrency($p['amount'], 'PHP', $selected_currency), $selected_currency) ?></td>
                                                <td class="px-6 py-4">
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?=
                                                        $p['status'] === 'verified' ? 'bg-emerald-100 text-emerald-700' :
                                                        ($p['status'] === 'pending' ? 'bg-orange-100 text-orange-700' : 'bg-rose-100 text-rose-700')
                                                    ?>">
                                                        <?= $p['status'] ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-xs text-slate-500"><?= date('M d, Y', strtotime($p['created_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Sidebar Details -->
                <div class="space-y-6">

                    <!-- Customer Card -->
                    <div class="bg-white dark:bg-surface-dark rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                        <h3 class="font-bold text-sm uppercase text-slate-400 mb-4">Customer Information</h3>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="size-12 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                                <span class="material-symbols-outlined text-2xl">person</span>
                            </div>
                            <div>
                                <h4 class="font-bold"><?= htmlspecialchars($booking['user_first_name'] . ' ' . $booking['user_last_name']) ?></h4>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars($booking['user_email']) ?></p>
                            </div>
                        </div>
                        <div class="space-y-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-3 text-sm">
                                <span class="material-symbols-outlined text-slate-400 text-base">call</span>
                                <span><?= htmlspecialchars($booking['user_phone'] ?: 'N/A') ?></span>
                            </div>
                            <div class="flex items-start gap-3 text-sm">
                                <span class="material-symbols-outlined text-slate-400 text-base">location_on</span>
                                <span class="text-xs"><?= htmlspecialchars($booking['user_address'] ?: 'No address provided') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicle Card -->
                    <div class="bg-white dark:bg-surface-dark rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="h-40 bg-slate-200 bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($vehicle_image) ?>')"></div>
                        <div class="p-6">
                            <h3 class="font-bold text-sm uppercase text-slate-400 mb-2">Vehicle Details</h3>
                            <h4 class="font-bold text-lg mb-1"><?= htmlspecialchars($booking['make'] . ' ' . $booking['model']) ?></h4>
                            <p class="text-xs text-slate-500 mb-4"><?= htmlspecialchars($booking['plate_number']) ?></p>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-slate-50 dark:bg-slate-900/50 p-2 rounded-lg text-center">
                                    <p class="text-[8px] font-bold text-slate-400 uppercase">Transmission</p>
                                    <p class="text-[10px] font-bold"><?= ucfirst($booking['transmission']) ?></p>
                                </div>
                                <div class="bg-slate-50 dark:bg-slate-900/50 p-2 rounded-lg text-center">
                                    <p class="text-[8px] font-bold text-slate-400 uppercase">Fuel Type</p>
                                    <p class="text-[10px] font-bold"><?= ucfirst($booking['fuel_type']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Driver Card -->
                    <div class="bg-white dark:bg-surface-dark rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                        <h3 class="font-bold text-sm uppercase text-slate-400 mb-4">Assigned Driver</h3>
                        <?php if ($booking['driver_id']): ?>
                            <div class="flex items-center gap-4 mb-4">
                                <div class="size-12 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                                    <span class="material-symbols-outlined text-2xl">license</span>
                                </div>
                                <div>
                                    <h4 class="font-bold"><?= htmlspecialchars($booking['driver_first_name'] . ' ' . $booking['driver_last_name']) ?></h4>
                                    <p class="text-[10px] text-slate-500 font-bold uppercase"><?= $booking['driver_employee_id'] ?></p>
                                </div>
                            </div>
                            <div class="space-y-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                                <div class="flex items-center gap-3 text-sm">
                                    <span class="material-symbols-outlined text-slate-400 text-base">call</span>
                                    <span><?= htmlspecialchars($booking['driver_phone']) ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold px-2 py-0.5 rounded <?= $booking['driver_status'] === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' ?>">
                                        <?= strtoupper($booking['driver_status']) ?>
                                    </span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <span class="material-symbols-outlined text-slate-300 text-4xl mb-2">person_off</span>
                                <p class="text-xs text-slate-500">No driver assigned to this booking.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Action Center -->
                    <div class="bg-white dark:bg-surface-dark rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                        <h3 class="font-bold text-sm uppercase text-slate-400 mb-4">Action Center</h3>
                        <form method="POST" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Update Booking Status</label>
                                <select name="status" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary text-sm font-bold">
                                    <option value="pending" <?= $booking['booking_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="confirmed" <?= $booking['booking_status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="active" <?= $booking['booking_status'] === 'active' ? 'selected' : '' ?>>Active (On-going)</option>
                                    <option value="completed" <?= $booking['booking_status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="cancelled" <?= $booking['booking_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="w-full bg-primary text-white py-3 rounded-xl font-bold text-sm shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all active:scale-[0.98]">
                                Update Status
                            </button>
                        </form>
                    </div>

                    <!-- Billing Summary -->
                    <div class="bg-primary text-white rounded-2xl p-6 shadow-lg shadow-primary/20">
                        <h3 class="font-bold text-sm uppercase text-white/60 mb-4">Financial Summary</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-white/70">Subtotal (<?= $booking['rental_days'] ?> Days)</span>
                                <span class="font-medium"><?= formatCurrency(convertCurrency($booking['daily_rate'] * $booking['rental_days'], 'PHP', $selected_currency), $selected_currency) ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-white/70">Insurance Fee</span>
                                <span class="font-medium"><?= formatCurrency(convertCurrency($booking['insurance_amount'], 'PHP', $selected_currency), $selected_currency) ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-white/70">Service Fee</span>
                                <span class="font-medium"><?= formatCurrency(convertCurrency($booking['service_fee'], 'PHP', $selected_currency), $selected_currency) ?></span>
                            </div>
                            <div class="pt-3 border-t border-white/20 flex justify-between items-center">
                                <span class="font-bold">Total Amount</span>
                                <span class="text-xl font-black"><?= formatCurrency(convertCurrency($booking['total_amount'], 'PHP', $selected_currency), $selected_currency) ?></span>
                            </div>
                            <div class="pt-2 flex justify-between text-sm font-bold text-emerald-300">
                                <span>Downpayment</span>
                                <span><?= formatCurrency(convertCurrency($booking['downpayment_amount'], 'PHP', $selected_currency), $selected_currency) ?></span>
                            </div>
                            <div class="pt-1 flex justify-between text-sm font-bold text-orange-200">
                                <span>Balance</span>
                                <span><?= formatCurrency(convertCurrency($booking['balance_amount'], 'PHP', $selected_currency), $selected_currency) ?></span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <!-- Extra spacer for mobile scrolling -->
            <div class="h-32 lg:hidden"></div>
        </main>
    </div>
</div>

<?php if (($booking['pickup_latitude'] && $booking['pickup_longitude']) || ($booking['dropoff_latitude'] && $booking['dropoff_longitude'])): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const carIcon = L.divIcon({
            html: `
                <div class="relative flex items-center justify-center">
                    <div class="absolute size-10 bg-primary/30 rounded-full animate-ping"></div>
                    <div class="size-8 bg-primary text-white rounded-full flex items-center justify-center shadow-lg border-2 border-white">
                        <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1">directions_car</span>
                    </div>
                </div>
            `,
            className: '',
            iconSize: [32, 32],
            iconAnchor: [16, 16]
        });

        // Pickup Map
        <?php if ($booking['pickup_latitude'] && $booking['pickup_longitude']): ?>
        const pLat = <?= $booking['pickup_latitude'] ?>;
        const pLng = <?= $booking['pickup_longitude'] ?>;
        const pAddress = "<?= addslashes(htmlspecialchars($booking['pickup_location'])) ?>";

        const pMap = L.map('detailsPickupMap', { zoomControl: false }).setView([pLat, pLng], 16);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/voyager/{z}/{x}/{y}{r}.png', { attribution: '© OpenStreetMap © CARTO' }).addTo(pMap);
        L.control.zoom({ position: 'bottomright' }).addTo(pMap);

        const pMarker = L.marker([pLat, pLng], { icon: carIcon }).addTo(pMap);
        pMarker.bindPopup(`
            <div class="p-1">
                <p class="font-bold text-sm mb-1">Pickup Location</p>
                <p class="text-[11px] text-slate-500 leading-tight">${pAddress}</p>
            </div>
        `).openPopup();
        setTimeout(() => pMap.invalidateSize(), 500);
        <?php endif; ?>

        // Dropoff Map
        <?php if ($booking['dropoff_latitude'] && $booking['dropoff_longitude']): ?>
        const dLat = <?= $booking['dropoff_latitude'] ?>;
        const dLng = <?= $booking['dropoff_longitude'] ?>;
        const dAddress = "<?= addslashes(htmlspecialchars($booking['dropoff_location'])) ?>";

        const dMap = L.map('detailsDropoffMap', { zoomControl: false }).setView([dLat, dLng], 16);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/voyager/{z}/{x}/{y}{r}.png', { attribution: '© OpenStreetMap © CARTO' }).addTo(dMap);
        L.control.zoom({ position: 'bottomright' }).addTo(dMap);

        const dMarker = L.marker([dLat, dLng], { icon: carIcon }).addTo(dMap);
        dMarker.bindPopup(`
            <div class="p-1">
                <p class="font-bold text-sm mb-1">Return Location</p>
                <p class="text-[11px] text-slate-500 leading-tight">${dAddress}</p>
            </div>
        `).openPopup();
        setTimeout(() => dMap.invalidateSize(), 500);
        <?php endif; ?>
    });
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
