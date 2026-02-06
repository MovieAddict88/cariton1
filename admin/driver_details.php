<?php
/**
 * Driver Details - Admin Page
 */

$page_title = 'Driver Details';
require_once 'includes/header.php';

$message = '';
$error = '';

$driver_id = $_GET['id'] ?? 1;

try {
    $pdo = getDBConnection();
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_profile'])) {
            $stmt = $pdo->prepare("UPDATE drivers SET phone = ?, license_number = ?, license_expiry = ?, experience_years = ? WHERE id = ?");
            $stmt->execute([
                $_POST['phone'], $_POST['license_number'], $_POST['license_expiry'],
                $_POST['experience_years'], $driver_id
            ]);
            $message = 'Profile updated successfully!';
        } elseif (isset($_POST['archive_driver'])) {
            $stmt = $pdo->prepare("UPDATE drivers SET status = 'archived' WHERE id = ?");
            $stmt->execute([$driver_id]);
            header("Location: drivers.php?message=Driver archived");
            exit;
        } elseif (isset($_POST['restore_driver'])) {
            $stmt = $pdo->prepare("UPDATE drivers SET status = 'active' WHERE id = ?");
            $stmt->execute([$driver_id]);
            $message = 'Driver restored successfully!';
        }
    }

    // Get driver info
    $stmt = $pdo->prepare("SELECT d.* FROM drivers d WHERE d.id = ?");
    $stmt->execute([$driver_id]);
    $driver = $stmt->fetch();

    if (!$driver) {
        header("Location: drivers.php");
        exit;
    }

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="flex h-screen overflow-hidden">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="sticky top-0 z-30 flex items-center bg-white/80 dark:bg-background-dark/80 backdrop-blur-md p-4 border-b border-slate-200 dark:border-slate-800 lg:px-8">
            <div class="flex items-center gap-4">
                <a href="drivers.php" class="text-primary flex size-10 items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors">
                    <span class="material-symbols-outlined">arrow_back_ios_new</span>
                </a>
                <h2 class="text-slate-900 dark:text-white text-lg font-bold">Driver Profile</h2>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 lg:p-8 pb-32 admin-content">
            <div class="max-w-2xl mx-auto">
            <form method="POST">
                <input type="hidden" name="update_profile" value="1">
                <div class="flex flex-col items-center mb-12">
                    <div class="size-32 rounded-full border-4 border-white dark:border-slate-800 shadow-xl overflow-hidden mb-4 relative group">
                        <img src="https://ui-avatars.com/api/?name=<?= $driver['first_name'] ?>+<?= $driver['last_name'] ?>&size=128" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                            <span class="material-symbols-outlined text-white">photo_camera</span>
                        </div>
                    </div>
                    <h1 class="text-2xl font-bold"><?= htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']) ?></h1>
                    <p class="text-slate-500 font-bold tracking-widest text-xs uppercase"><?= $driver['employee_id'] ?></p>
                    <div class="mt-4 px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-[10px] font-bold rounded-full uppercase">
                        <?= $driver['status'] ?> Status
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-400 text-emerald-700 dark:text-emerald-300 px-4 py-3 rounded-xl mb-6 text-sm">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
                    <div class="col-span-full">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Personal Information</h3>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 ml-1">Phone Number</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($driver['phone']) ?>" class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 ml-1">Email Address</label>
                        <input type="email" value="<?= htmlspecialchars($driver['email'] ?? 'N/A') ?>" disabled class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-400 cursor-not-allowed">
                    </div>

                    <div class="col-span-full mt-6">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Professional Information</h3>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 ml-1">License Number</label>
                        <input type="text" name="license_number" value="<?= htmlspecialchars($driver['license_number']) ?>" class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary uppercase">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 ml-1">Expiry Date</label>
                        <input type="date" name="license_expiry" value="<?= $driver['license_expiry'] ?>" class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 ml-1">Experience (Years)</label>
                        <input type="number" name="experience_years" value="<?= $driver['experience_years'] ?>" class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                    </div>
                </div>

                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-primary text-white py-4 rounded-2xl font-bold shadow-lg shadow-primary/30 hover:bg-primary/90 transition-all active:scale-95">Save Changes</button>
                </div>
            </form>

            <div class="mt-4 flex gap-4">
                <?php if ($driver['status'] !== 'archived'): ?>
                    <form method="POST" onsubmit="return confirm('Archive this driver?');" class="flex-1">
                        <input type="hidden" name="archive_driver" value="1">
                        <button type="submit" class="w-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 py-4 rounded-2xl font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">Archive Driver</button>
                    </form>
                <?php else: ?>
                    <form method="POST" onsubmit="return confirm('Restore this driver?');" class="flex-1">
                        <input type="hidden" name="restore_driver" value="1">
                        <button type="submit" class="w-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 py-4 rounded-2xl font-bold hover:bg-emerald-200 dark:hover:bg-emerald-800 transition-colors">Restore Driver</button>
                    </form>
                <?php endif; ?>

                <form method="POST" action="drivers.php" onsubmit="return confirm('Permanently delete this driver? This cannot be undone.');" class="flex-1">
                    <input type="hidden" name="driver_id" value="<?= $driver['id'] ?>">
                    <input type="hidden" name="delete_driver" value="1">
                    <button type="submit" class="w-full bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 py-4 rounded-2xl font-bold hover:bg-rose-200 dark:hover:bg-rose-800 transition-colors">Delete Permanently</button>
                </form>
            </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
