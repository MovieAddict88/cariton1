<?php
/**
 * Drivers Management - Admin Page
 */

$page_title = 'Drivers';
require_once 'includes/header.php';

$message = '';
$error = '';

try {
    $pdo = getDBConnection();
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_driver'])) {
            $stmt = $pdo->prepare("INSERT INTO drivers (first_name, last_name, email, phone, license_number, license_expiry, employee_id, status, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
            $stmt->execute([
                $_POST['first_name'], $_POST['last_name'], $_POST['email'], 
                $_POST['phone'], $_POST['license_number'], $_POST['license_expiry'],
                $_POST['employee_id'], $_POST['status'] ?? 'active'
            ]);
            $message = 'Driver added successfully!';
        } elseif (isset($_POST['update_status'])) {
            $stmt = $pdo->prepare("UPDATE drivers SET status = ? WHERE id = ?");
            $stmt->execute([$_POST['status'], $_POST['driver_id']]);
            $message = 'Driver status updated!';
        } elseif (isset($_POST['archive_driver'])) {
            $stmt = $pdo->prepare("UPDATE drivers SET status = 'archived' WHERE id = ?");
            $stmt->execute([$_POST['driver_id']]);
            $message = 'Driver archived!';
        } elseif (isset($_POST['unarchive_driver'])) {
            $stmt = $pdo->prepare("UPDATE drivers SET status = 'active' WHERE id = ?");
            $stmt->execute([$_POST['driver_id']]);
            $message = 'Driver restored!';
        } elseif (isset($_POST['delete_driver'])) {
            $stmt = $pdo->prepare("DELETE FROM drivers WHERE id = ?");
            $stmt->execute([$_POST['driver_id']]);
            $message = 'Driver deleted permanently!';
        }
    }

    $view = $_GET['view'] ?? 'active';
    $status_filter = $view === 'archived' ? "status = 'archived'" : "status != 'archived'";

    // Get drivers with both permanent and current booking assignments
    $stmt = $pdo->query("SELECT d.*, v.make, v.model,
                         (SELECT v2.make FROM bookings b2 JOIN vehicles v2 ON b2.vehicle_id = v2.id WHERE b2.driver_id = d.id AND b2.booking_status IN ('confirmed', 'active') LIMIT 1) as b_make,
                         (SELECT v2.model FROM bookings b2 JOIN vehicles v2 ON b2.vehicle_id = v2.id WHERE b2.driver_id = d.id AND b2.booking_status IN ('confirmed', 'active') LIMIT 1) as b_model
                         FROM drivers d
                         LEFT JOIN vehicles v ON d.assigned_vehicle_id = v.id
                         WHERE d.$status_filter
                         ORDER BY d.created_at DESC");
    $drivers = $stmt->fetchAll();

} catch (PDOException $e) {
    $drivers = [];
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
                <h2 class="text-slate-900 dark:text-white text-lg font-bold"><?= $view === 'archived' ? 'Archived Drivers' : 'Driver Roster' ?></h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="?view=<?= $view === 'archived' ? 'active' : 'archived' ?>" class="text-slate-600 dark:text-slate-400 px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 hover:bg-slate-100 dark:hover:bg-slate-800">
                    <span class="material-symbols-outlined"><?= $view === 'archived' ? 'group' : 'archive' ?></span>
                    <span class="hidden sm:inline"><?= $view === 'archived' ? 'View Active' : 'View Archived' ?></span>
                </a>
                <button onclick="document.getElementById('addDriverModal').classList.remove('hidden')" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined">person_add</span>
                    <span class="hidden sm:inline">Add Driver</span>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 lg:p-8 pb-24 admin-content">
            <?php if ($message): ?>
                <div class="bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-400 text-emerald-700 dark:text-emerald-300 px-4 py-3 rounded-xl mb-6">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php if (empty($drivers)): ?>
                    <div class="col-span-full py-12 text-center">
                        <div class="bg-slate-100 dark:bg-slate-800 size-20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="material-symbols-outlined text-4xl text-slate-400">group_off</span>
                        </div>
                        <h3 class="text-lg font-bold">No Drivers Found</h3>
                        <p class="text-slate-500">There are no <?= $view === 'archived' ? 'archived' : '' ?> drivers to display.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($drivers as $d): ?>
                        <div class="bg-white dark:bg-surface-dark rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                            <div class="flex items-center gap-4 mb-6">
                                <div class="size-16 rounded-full bg-slate-100 dark:bg-slate-800 border-2 border-primary/20 flex items-center justify-center overflow-hidden">
                                    <img src="https://ui-avatars.com/api/?name=<?= $d['first_name'] ?>+<?= $d['last_name'] ?>&background=random" class="w-full h-full object-cover">
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg"><?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?></h3>
                                    <p class="text-xs text-slate-500 font-bold uppercase tracking-wider"><?= $d['employee_id'] ?></p>
                                </div>
                            </div>
                            <div class="space-y-3 mb-6">
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Status</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $d['status'] == 'active' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' ?>">
                                        <?= $d['status'] ?>
                                    </span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Assigned</span>
                                    <span class="font-medium text-xs">
                                        <?php
                                        if ($d['make']) {
                                            echo htmlspecialchars($d['make'] . ' ' . $d['model']);
                                        } elseif ($d['b_make']) {
                                            echo htmlspecialchars($d['b_make'] . ' ' . $d['b_model']) . ' <span class="text-[10px] text-primary opacity-70">(Booked)</span>';
                                        } else {
                                            echo 'Unassigned';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" class="flex-1">
                                    <input type="hidden" name="driver_id" value="<?= $d['id'] ?>">
                                    <input type="hidden" name="update_status" value="1">
                                    <select name="status" onchange="this.form.submit()" class="w-full text-xs font-bold border-slate-200 dark:border-slate-800 rounded-lg bg-slate-50 dark:bg-slate-900">
                                        <option value="active" <?= $d['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $d['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                        <option value="on_leave" <?= $d['status'] === 'on_leave' ? 'selected' : '' ?>>On Leave</option>
                                        <?php if ($d['status'] === 'archived'): ?>
                                            <option value="archived" selected>Archived</option>
                                        <?php endif; ?>
                                    </select>
                                </form>
                                <a href="driver_details.php?id=<?= $d['id'] ?>" class="p-2 text-slate-400 hover:text-primary transition-colors flex items-center justify-center" title="Edit Profile">
                                    <span class="material-symbols-outlined">edit</span>
                                </a>
                                <?php if ($d['status'] !== 'archived'): ?>
                                    <form method="POST" onsubmit="return confirm('Archive this driver?');" class="inline">
                                        <input type="hidden" name="driver_id" value="<?= $d['id'] ?>">
                                        <button type="submit" name="archive_driver" class="p-2 text-slate-400 hover:text-amber-500 transition-colors flex items-center justify-center" title="Archive Driver">
                                            <span class="material-symbols-outlined">archive</span>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" onsubmit="return confirm('Restore this driver?');" class="inline">
                                        <input type="hidden" name="driver_id" value="<?= $d['id'] ?>">
                                        <button type="submit" name="unarchive_driver" class="p-2 text-slate-400 hover:text-emerald-500 transition-colors flex items-center justify-center" title="Restore Driver">
                                            <span class="material-symbols-outlined">unarchive</span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" onsubmit="return confirm('Permanently delete this driver? This cannot be undone.');" class="inline">
                                    <input type="hidden" name="driver_id" value="<?= $d['id'] ?>">
                                    <button type="submit" name="delete_driver" class="p-2 text-slate-400 hover:text-rose-500 transition-colors flex items-center justify-center" title="Delete Permanently">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Add Driver Modal -->
<div id="addDriverModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-surface-dark rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
            <h3 class="text-lg font-bold">Add New Driver</h3>
            <button onclick="document.getElementById('addDriverModal').classList.add('hidden')" class="p-2 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-full transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto no-scrollbar">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">First Name</label>
                    <input type="text" name="first_name" required placeholder="John" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Last Name</label>
                    <input type="text" name="last_name" required placeholder="Doe" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Email</label>
                    <input type="email" name="email" required placeholder="john.doe@example.com" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Phone</label>
                    <input type="tel" name="phone" required placeholder="+63 912 345 6789" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">License Number</label>
                    <input type="text" name="license_number" required placeholder="D01-123456789" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">License Expiry</label>
                    <input type="date" name="license_expiry" required class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Employee ID</label>
                <input type="text" name="employee_id" required placeholder="DRV-001" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                <select name="status" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="on_leave">On Leave</option>
                </select>
            </div>
            <div class="flex gap-4 pt-4">
                <button type="button" onclick="document.getElementById('addDriverModal').classList.add('hidden')" class="flex-1 px-4 py-3 border border-slate-200 dark:border-slate-800 rounded-xl font-bold hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors">
                    Cancel
                </button>
                <button type="submit" name="add_driver" class="flex-1 px-4 py-3 bg-primary text-white rounded-xl font-bold shadow-lg shadow-primary/30 hover:bg-primary/90 transition-colors">
                    Add Driver
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
