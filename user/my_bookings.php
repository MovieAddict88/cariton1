<?php
require_once '../admin/includes/config.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: user_authentication.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$pdo = getDBConnection();

// Fetch bookings
$stmt = $pdo->prepare("
    SELECT b.*, v.make, v.model, v.images, v.plate_number,
           p.status as payment_status, p.amount as payment_amount
    FROM bookings b
    LEFT JOIN vehicles v ON b.vehicle_id = v.id
    LEFT JOIN payments p ON b.id = p.booking_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();

foreach ($bookings as &$booking) {
    $images = json_decode($booking['images'] ?? '[]', true);
    $booking['vehicle_image'] = !empty($images) ? $images[0] : 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&q=80&w=400';
    $booking['vehicle_name'] = $booking['make'] . ' ' . $booking['model'];
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>My Bookings - Cariton</title>
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
                    },
                    fontFamily: { "display": ["Inter", "sans-serif"] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
        .fill-1 { font-variation-settings: 'FILL' 1; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-gray-900 dark:text-white min-h-screen">
    <header class="sticky top-0 z-50 bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-800">
        <div class="flex items-center justify-between px-4 py-4 max-w-7xl mx-auto">
            <a href="browse_cars.php" class="flex size-10 items-center justify-center rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <h1 class="text-lg font-bold">My Bookings</h1>
            <div class="w-10"></div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto p-4 pb-24">
        <?php if (empty($bookings)): ?>
            <div class="text-center py-20">
                <span class="material-symbols-outlined text-6xl text-gray-400 mb-4">event_busy</span>
                <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">No bookings found</p>
                <a href="browse_cars.php" class="inline-block mt-6 px-6 py-3 bg-primary text-white rounded-lg font-semibold">Browse Cars</a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($bookings as $b):
                    $status_colors = [
                        'pending' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
                        'confirmed' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                        'active' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                        'completed' => 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-300',
                        'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                    ];
                    $s_color = $status_colors[$b['booking_status']] ?? $status_colors['pending'];
                ?>
                    <div class="bg-card-light dark:bg-card-dark rounded-xl overflow-hidden border border-gray-200 dark:border-gray-800 shadow-sm">
                        <div class="p-4">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <p class="text-[10px] text-gray-500 uppercase font-bold">Reference</p>
                                    <p class="font-bold text-primary"><?= htmlspecialchars($b['reference_number']) ?></p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase <?= $s_color ?>">
                                    <?= htmlspecialchars($b['booking_status']) ?>
                                </span>
                            </div>

                            <div class="flex gap-4 mb-4">
                                <div class="w-20 h-20 bg-gray-200 rounded-lg bg-cover bg-center shrink-0" style="background-image: url('<?= htmlspecialchars($b['vehicle_image']) ?>')"></div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-bold truncate"><?= htmlspecialchars($b['vehicle_name']) ?></h3>
                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($b['plate_number']) ?></p>
                                    <p class="text-[10px] text-gray-400 mt-1">
                                        <?= date('M d', strtotime($b['pickup_date'])) ?> - <?= date('M d, Y', strtotime($b['dropoff_date'])) ?>
                                    </p>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <a href="booking_details.php?booking_id=<?= $b['id'] ?>" class="flex-1 text-center py-2 bg-gray-100 dark:bg-gray-800 rounded-lg text-xs font-bold transition-colors">Details</a>
                                <?php if ($b['booking_status'] === 'pending' && !$b['payment_status']): ?>
                                    <a href="payment_proof_upload.php?booking_id=<?= $b['id'] ?>" class="flex-1 text-center py-2 bg-primary text-white rounded-lg text-xs font-bold">Pay Now</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <nav class="fixed bottom-0 left-0 right-0 z-50 bg-background-light/95 dark:bg-background-dark/95 backdrop-blur-xl border-t border-gray-200 dark:border-gray-800 pb-safe">
        <div class="flex justify-around items-center h-16 max-w-7xl mx-auto">
            <a href="index.html" class="flex flex-col items-center text-gray-400">
                <span class="material-symbols-outlined">home</span>
                <span class="text-[10px]">Home</span>
            </a>
            <a href="browse_cars.php" class="flex flex-col items-center text-gray-400">
                <span class="material-symbols-outlined">explore</span>
                <span class="text-[10px]">Browse</span>
            </a>
            <a href="my_bookings.php" class="flex flex-col items-center text-primary">
                <span class="material-symbols-outlined fill-1">calendar_today</span>
                <span class="text-[10px] font-bold">Bookings</span>
            </a>
            <a href="profile.php" class="flex flex-col items-center text-gray-400">
                <span class="material-symbols-outlined">person</span>
                <span class="text-[10px]">Profile</span>
            </a>
        </div>
    </nav>
</body>
</html>
