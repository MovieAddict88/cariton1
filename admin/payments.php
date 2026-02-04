<?php
/**
 * Payments Management - Admin Page
 */

$page_title = 'Payments';
require_once 'includes/header.php';

$message = '';
$error = '';

try {
    $pdo = getDBConnection();
    
    // Handle verification
    if (isset($_POST['verify_payment'])) {
        $stmt = $pdo->prepare("UPDATE payments SET status = 'verified', verified_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$_POST['payment_id']]);
        
        // Also update booking status if it was pending downpayment
        $stmt = $pdo->prepare("UPDATE bookings SET downpayment_paid = 1, booking_status = 'confirmed' 
                             WHERE id = (SELECT booking_id FROM payments WHERE id = ?)");
        $stmt->execute([$_POST['payment_id']]);
        
        $message = 'Payment verified and booking confirmed!';
    }

    // Get payments
    $stmt = $pdo->query("SELECT p.*, b.reference_number as booking_ref, u.first_name, u.last_name 
                         FROM payments p 
                         LEFT JOIN bookings b ON p.booking_id = b.id 
                         LEFT JOIN users u ON b.user_id = u.id 
                         ORDER BY p.created_at DESC");
    $payments = $stmt->fetchAll();

} catch (PDOException $e) {
    $payments = [];
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
                <h2 class="text-slate-900 dark:text-white text-lg font-bold">Payment Approvals</h2>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 lg:p-8 pb-24 admin-content">
            <?php if ($message): ?>
                <div class="bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-400 text-emerald-700 dark:text-emerald-300 px-4 py-3 rounded-xl mb-6">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php if (empty($payments)): ?>
                    <div class="col-span-full p-12 text-center text-slate-500 bg-white dark:bg-surface-dark rounded-2xl border border-slate-200 dark:border-slate-800">
                        No payments found.
                    </div>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                        <div class="bg-white dark:bg-surface-dark rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm flex flex-col">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Reference</p>
                                    <h3 class="font-bold text-primary"><?= $p['reference_number'] ?></h3>
                                </div>
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase <?= 
                                    $p['status'] === 'verified' ? 'bg-green-100 text-green-700' : 
                                    ($p['status'] === 'pending' ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700')
                                ?>">
                                    <?= $p['status'] ?>
                                </span>
                            </div>
                            
                            <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 mb-4 space-y-2">
                                <div class="flex justify-between text-xs">
                                    <span class="text-slate-500">Amount</span>
                                    <span class="font-bold text-slate-900 dark:text-white"><?= formatCurrency(convertCurrency($p['amount'], 'PHP', $selected_currency), $selected_currency) ?></span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-slate-500">Method</span>
                                    <span class="font-medium"><?= strtoupper($p['payment_method']) ?></span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-slate-500">Booking</span>
                                    <span class="font-medium"><?= $p['booking_ref'] ?></span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-slate-500">Customer</span>
                                    <span class="font-medium"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></span>
                                </div>
                            </div>

                            <?php if ($p['proof_of_payment']): ?>
                                <button onclick="window.open('<?= $p['proof_of_payment'] ?>')" class="mb-4 text-xs font-bold text-primary flex items-center gap-1 hover:underline">
                                    <span class="material-symbols-outlined text-sm">image</span>
                                    View Proof of Payment
                                </button>
                            <?php endif; ?>

                            <div class="mt-auto pt-4 flex gap-2">
                                <?php if ($p['status'] === 'pending'): ?>
                                    <form method="POST" class="flex-1">
                                        <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                                        <button type="submit" name="verify_payment" class="w-full bg-primary text-white py-2 rounded-lg text-xs font-bold hover:bg-primary/90 transition-colors">Verify Payment</button>
                                    </form>
                                    <button class="flex-1 border border-slate-200 dark:border-slate-800 py-2 rounded-lg text-xs font-bold hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors text-rose-500">Reject</button>
                                <?php else: ?>
                                    <button disabled class="w-full bg-slate-100 dark:bg-slate-800 text-slate-400 py-2 rounded-lg text-xs font-bold cursor-not-allowed">Verified on <?= date('M d, Y', strtotime($p['verified_at'])) ?></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
