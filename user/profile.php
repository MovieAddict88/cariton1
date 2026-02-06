<?php
require_once '../admin/includes/config.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: user_authentication.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$pdo = getDBConnection();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    try {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$first_name, $last_name, $phone, $address, $user_id]);

        $_SESSION['user_name'] = $first_name . ' ' . $last_name;
        $message = 'Profile updated successfully!';
    } catch (PDOException $e) {
        $error = 'Update failed: ' . $e->getMessage();
    }
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>My Profile - Cariton</title>
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
                        "card-dark": "#1a252f",
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-background-light dark:bg-background-dark text-gray-900 dark:text-white min-h-screen">
    <header class="sticky top-0 z-50 bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-800">
        <div class="flex items-center justify-between px-4 py-4 max-w-7xl mx-auto">
            <a href="browse_cars.php" class="flex size-10 items-center justify-center rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <h1 class="text-lg font-bold">My Profile</h1>
            <a href="logout.php" class="text-rose-500 text-sm font-bold">Logout</a>
        </div>
    </header>

    <main class="max-w-xl mx-auto p-4 py-8">
        <div class="flex flex-col items-center mb-8">
            <div class="size-24 rounded-full bg-primary/20 flex items-center justify-center border-4 border-white dark:border-gray-800 shadow-xl overflow-hidden mb-4">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['first_name'] . ' ' . $user['last_name']) ?>&background=137fec&color=fff&size=128" class="w-full h-full object-cover">
            </div>
            <h2 class="text-xl font-bold"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h2>
            <p class="text-gray-500 text-sm"><?= htmlspecialchars($user['email']) ?></p>
        </div>

        <?php if ($message): ?>
            <div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded-xl mb-6 text-sm">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-rose-100 border border-rose-400 text-rose-700 px-4 py-3 rounded-xl mb-6 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">First Name</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required class="w-full border-gray-200 dark:border-gray-800 dark:bg-gray-900 rounded-xl p-3 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Last Name</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required class="w-full border-gray-200 dark:border-gray-800 dark:bg-gray-900 rounded-xl p-3 focus:ring-primary">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Phone Number</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" class="w-full border-gray-200 dark:border-gray-800 dark:bg-gray-900 rounded-xl p-3 focus:ring-primary">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Home Address</label>
                <textarea name="address" rows="3" class="w-full border-gray-200 dark:border-gray-800 dark:bg-gray-900 rounded-xl p-3 focus:ring-primary"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="w-full bg-primary text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/30 active:scale-95 transition-transform mt-4">
                Update Profile
            </button>
        </form>
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
            <a href="my_bookings.php" class="flex flex-col items-center text-gray-400">
                <span class="material-symbols-outlined">calendar_today</span>
                <span class="text-[10px]">Bookings</span>
            </a>
            <a href="profile.php" class="flex flex-col items-center text-primary">
                <span class="material-symbols-outlined fill-1">person</span>
                <span class="text-[10px] font-bold">Profile</span>
            </a>
        </div>
    </nav>
</body>
</html>
