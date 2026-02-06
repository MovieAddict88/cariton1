<?php
require_once '../admin/includes/config.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: user_authentication.html');
    exit;
}

$booking_id = $_GET['booking_id'] ?? null;
if (!$booking_id) {
    header('Location: my_bookings.php');
    exit;
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("
    SELECT b.*, v.make, v.model, v.images, v.plate_number, v.transmission, v.fuel_type, v.daily_rate,
           p.status as payment_status, p.payment_method, p.transaction_reference, p.amount as payment_amount
    FROM bookings b
    LEFT JOIN vehicles v ON b.vehicle_id = v.id
    LEFT JOIN payments p ON b.id = p.booking_id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: my_bookings.php');
    exit;
}

$images = json_decode($booking['images'] ?? '[]', true);
$vehicle_image = !empty($images) ? $images[0] : 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&q=80&w=400';
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Booking Details - Cariton</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#137fec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101922",
                        "card-light": "#ffffff",
                        "card-dark": "#192633",
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-gray-900 dark:text-white min-h-screen pb-24">
    <header class="sticky top-0 z-50 bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-800">
        <div class="flex items-center justify-between px-4 py-4 max-w-7xl mx-auto">
            <a href="my_bookings.php" class="flex size-10 items-center justify-center rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <h1 class="text-lg font-bold">Booking Details</h1>
            <div class="w-10"></div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto p-4 space-y-6">
        <!-- Status Card -->
        <div class="bg-card-light dark:bg-card-dark rounded-2xl p-6 border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <span class="text-xs font-bold uppercase text-gray-500">Status</span>
                <?php
                    $status_colors = [
                        'pending' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
                        'confirmed' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                        'active' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                        'completed' => 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-300',
                        'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                    ];
                ?>
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase <?= $status_colors[$booking['booking_status']] ?? 'bg-gray-100' ?>">
                    <?= $booking['booking_status'] ?>
                </span>
            </div>
            <h2 class="text-2xl font-bold text-primary mb-1"><?= htmlspecialchars($booking['reference_number']) ?></h2>
            <p class="text-xs text-gray-500">Booked on <?= date('M d, Y h:i A', strtotime($booking['created_at'])) ?></p>
        </div>

        <!-- Vehicle Details -->
        <div class="bg-card-light dark:bg-card-dark rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="h-48 bg-gray-200 bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($vehicle_image) ?>')"></div>
            <div class="p-6">
                <h3 class="text-xl font-bold mb-1"><?= htmlspecialchars($booking['make'] . ' ' . $booking['model']) ?></h3>
                <p class="text-sm text-gray-500 mb-4"><?= htmlspecialchars($booking['plate_number']) ?> • <?= htmlspecialchars($booking['transmission']) ?> • <?= htmlspecialchars($booking['fuel_type']) ?></p>

                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <div>
                        <p class="text-[10px] text-gray-500 uppercase font-bold">Pickup</p>
                        <p class="text-sm font-semibold"><?= date('M d, Y', strtotime($booking['pickup_date'])) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($booking['pickup_location']) ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-500 uppercase font-bold">Dropoff</p>
                        <p class="text-sm font-semibold"><?= date('M d, Y', strtotime($booking['dropoff_date'])) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($booking['dropoff_location']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Summary -->
        <div class="bg-card-light dark:bg-card-dark rounded-2xl p-6 border border-gray-200 dark:border-gray-800 shadow-sm">
            <h3 class="text-lg font-bold mb-4">Payment Summary</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Daily Rate</span>
                    <span>₱<?= number_format($booking['daily_rate'], 2) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Rental Days</span>
                    <span><?= $booking['rental_days'] ?> days</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Insurance Fee</span>
                    <span>₱<?= number_format($booking['insurance_amount'], 2) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Service Fee</span>
                    <span>₱<?= number_format($booking['service_fee'], 2) ?></span>
                </div>
                <div class="flex justify-between font-bold text-base pt-3 border-t border-gray-100 dark:border-gray-800">
                    <span>Total Amount</span>
                    <span>₱<?= number_format($booking['total_amount'], 2) ?></span>
                </div>
                <div class="flex justify-between font-bold text-primary">
                    <span>Downpayment (20%)</span>
                    <span>₱<?= number_format($booking['downpayment_amount'], 2) ?></span>
                </div>
            </div>

            <div class="mt-6 p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50">
                <div class="flex justify-between items-center mb-1">
                    <span class="text-xs font-bold uppercase text-gray-500">Payment Status</span>
                    <span class="text-sm font-bold capitalize <?= $booking['payment_status'] === 'verified' ? 'text-green-600' : 'text-orange-600' ?>">
                        <?= htmlspecialchars($booking['payment_status'] ?? 'Pending') ?>
                    </span>
                </div>
                <?php if ($booking['payment_method']): ?>
                    <p class="text-xs text-gray-500">Method: <?= htmlspecialchars(strtoupper($booking['payment_method'])) ?></p>
                    <?php if ($booking['transaction_reference']): ?>
                        <p class="text-xs text-gray-500">Ref: <?= htmlspecialchars($booking['transaction_reference']) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="mt-6 flex gap-3">
                <?php if ($booking['booking_status'] === 'pending' && !$booking['payment_status']): ?>
                    <a href="payment_proof_upload.php?booking_id=<?= $booking['id'] ?>" class="flex-1 bg-primary text-white text-center py-3 rounded-xl font-bold">Submit Payment</a>
                <?php endif; ?>
                <button onclick="window.print()" class="px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-xl font-bold">
                    <span class="material-symbols-outlined text-base">print</span>
                </button>
            </div>
        </div>

        <?php if ($booking['booking_status'] === 'pending'): ?>
            <form action="../api/bookings.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                <button type="submit" class="w-full text-red-500 font-bold text-sm py-2">Cancel Booking</button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
