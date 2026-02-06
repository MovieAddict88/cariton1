<?php
/**
 * Reviews Management - Admin Page
 */

$page_title = 'Reviews';
require_once 'includes/header.php';

$message = '';
$error = '';

try {
    $pdo = getDBConnection();
    
    // Handle status updates and deletions
    if (isset($_POST['update_status'])) {
        $stmt = $pdo->prepare("UPDATE reviews SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['review_id']]);
        $message = "Review marked as " . $_POST['status'];
    } elseif (isset($_POST['delete_review'])) {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$_POST['review_id']]);
        $message = "Review deleted successfully!";
    } elseif (isset($_POST['bulk_action'])) {
        if (!empty($_POST['selected_reviews'])) {
            $selected_ids = implode(',', array_map('intval', $_POST['selected_reviews']));
            if ($_POST['bulk_action'] === 'approve') {
                $stmt = $pdo->prepare("UPDATE reviews SET status = 'published' WHERE id IN ($selected_ids)");
                $stmt->execute();
                $message = count($_POST['selected_reviews']) . " reviews approved!";
            } elseif ($_POST['bulk_action'] === 'flag') {
                $stmt = $pdo->prepare("UPDATE reviews SET status = 'flagged' WHERE id IN ($selected_ids)");
                $stmt->execute();
                $message = count($_POST['selected_reviews']) . " reviews flagged!";
            } elseif ($_POST['bulk_action'] === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM reviews WHERE id IN ($selected_ids)");
                $stmt->execute();
                $message = count($_POST['selected_reviews']) . " reviews deleted!";
            }
        }
    }

    // Get reviews with stats
    $stmt = $pdo->query("SELECT r.*, u.first_name, u.last_name, v.make, v.model 
                         FROM reviews r 
                         LEFT JOIN users u ON r.user_id = u.id 
                         LEFT JOIN vehicles v ON r.vehicle_id = v.id 
                         ORDER BY r.created_at DESC");
    $reviews = $stmt->fetchAll();
    
    // Get review stats
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM reviews GROUP BY status");
    $review_stats = [];
    while ($row = $stmt->fetch()) {
        $review_stats[$row['status']] = $row['count'];
    }

} catch (PDOException $e) {
    $reviews = [];
    $review_stats = ['pending' => 0, 'published' => 0, 'flagged' => 0, 'hidden' => 0];
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="flex h-screen overflow-hidden">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="sticky top-0 z-30 flex items-center bg-white/80 dark:bg-background-dark/80 backdrop-blur-md p-4 justify-between border-b border-slate-200 dark:border-slate-800 lg:px-8">
            <h2 class="text-slate-900 dark:text-white text-lg font-bold">Review Moderation</h2>
        </header>

        <main class="flex-1 overflow-y-auto p-4 lg:p-8 pb-24 admin-content">
            <!-- Review Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-surface-dark rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Pending</p>
                    <p class="text-2xl font-bold text-orange-500"><?= $review_stats['pending'] ?? 0 ?></p>
                </div>
                <div class="bg-white dark:bg-surface-dark rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Published</p>
                    <p class="text-2xl font-bold text-emerald-500"><?= $review_stats['published'] ?? 0 ?></p>
                </div>
                <div class="bg-white dark:bg-surface-dark rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Flagged</p>
                    <p class="text-2xl font-bold text-red-500"><?= $review_stats['flagged'] ?? 0 ?></p>
                </div>
                <div class="bg-white dark:bg-surface-dark rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Total Reviews</p>
                    <p class="text-2xl font-bold text-primary"><?= array_sum($review_stats) ?></p>
                </div>
            </div>

            <!-- Bulk Actions -->
            <?php if (!empty($reviews)): ?>
                <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 p-4 mb-6 shadow-sm">
                    <form id="bulkActionsForm" method="POST" class="flex items-center gap-4">
                        <input type="hidden" name="bulk_action" value="1">
                        <select name="bulk_action_type" class="px-4 py-2 border border-slate-200 dark:border-slate-800 rounded-lg text-sm font-bold bg-transparent">
                            <option value="">Bulk Actions</option>
                            <option value="approve">Approve Selected</option>
                            <option value="flag">Flag Selected</option>
                            <option value="delete">Delete Selected</option>
                        </select>
                        <button type="submit" onclick="return confirm('Are you sure?')" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-bold cursor-pointer">
                            Apply
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="space-y-6">
                <?php if (empty($reviews)): ?>
                    <!-- Mock reviews when database is empty -->
                    <?php 
                    $mock_reviews = [
                        ['id' => 1, 'first_name' => 'Marcus', 'last_name' => 'Kearney', 'make' => 'Tesla', 'model' => 'Model 3', 'overall_rating' => 5.0, 'vehicle_comment' => 'Amazing car, very clean and powerful. The autopilot was a great experience.', 'status' => 'published', 'created_at' => date('Y-m-d')],
                        ['id' => 2, 'first_name' => 'Sarah', 'last_name' => 'Johnson', 'make' => 'BMW', 'model' => 'X5', 'overall_rating' => 4.0, 'vehicle_comment' => 'Great SUV for the family trip. A bit thirsty on fuel but comfortable.', 'status' => 'pending', 'created_at' => date('Y-m-d')],
                    ];
                    foreach($mock_reviews as $r): ?>
                        <div class="bg-white dark:bg-surface-dark rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" name="selected_reviews[]" value="<?= $r['id'] ?>" class="rounded">
                                    <div class="size-10 rounded-full bg-primary/10 flex items-center justify-center font-bold text-primary">
                                        <?= substr($r['first_name'], 0, 1) ?>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-sm"><?= $r['first_name'] . ' ' . $r['last_name'] ?></h4>
                                        <p class="text-xs text-slate-500"><?= $r['make'] . ' ' . $r['model'] ?> • <?= date('M d, Y', strtotime($r['created_at'])) ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 text-amber-500 font-bold">
                                    <span class="material-symbols-outlined text-sm fill-1">star</span>
                                    <?= number_format($r['overall_rating'], 1) ?>
                                </div>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-6 italic">"<?= htmlspecialchars($r['vehicle_comment']) ?>"</p>
                            <div class="flex gap-2">
                                <?php if ($r['status'] !== 'published'): ?>
                                    <button class="px-4 py-2 bg-emerald-500 text-white text-xs font-bold rounded-lg">Approve</button>
                                <?php endif; ?>
                                <button class="px-4 py-2 bg-orange-500 text-white text-xs font-bold rounded-lg">Flag</button>
                                <button class="px-4 py-2 text-rose-500 text-xs font-bold rounded-lg hover:bg-rose-500/10">Delete</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($reviews as $r): ?>
                        <div class="bg-white dark:bg-surface-dark rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm mb-4">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" name="selected_reviews[]" value="<?= $r['id'] ?>" form="bulkActionsForm" class="rounded cursor-pointer">
                                    <div class="size-10 rounded-full bg-primary/10 flex items-center justify-center font-bold text-primary">
                                        <?= substr($r['first_name'] ?? 'U', 0, 1) ?>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-sm"><?= htmlspecialchars(($r['first_name'] ?? 'User') . ' ' . ($r['last_name'] ?? '')) ?></h4>
                                        <p class="text-xs text-slate-500"><?= htmlspecialchars($r['make'] . ' ' . $r['model']) ?> • <?= date('M d, Y', strtotime($r['created_at'])) ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 text-amber-500 font-bold">
                                    <span class="material-symbols-outlined text-sm fill-1">star</span>
                                    <?= number_format($r['overall_rating'], 1) ?>
                                </div>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-6 italic">"<?= htmlspecialchars($r['vehicle_comment']) ?>"</p>

                            <div class="flex gap-2">
                                <?php if ($r['status'] !== 'published'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                        <button type="submit" name="update_status" value="published" class="px-4 py-2 bg-emerald-500 text-white text-xs font-bold rounded-lg cursor-pointer">Publish</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($r['status'] !== 'flagged'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                        <button type="submit" name="update_status" value="flagged" class="px-4 py-2 bg-orange-500 text-white text-xs font-bold rounded-lg cursor-pointer">Flag</button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" class="inline">
                                    <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                    <button type="submit" name="delete_review" onclick="return confirm('Delete this review?')" class="px-4 py-2 text-rose-500 text-xs font-bold rounded-lg hover:bg-rose-500/10 cursor-pointer">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
