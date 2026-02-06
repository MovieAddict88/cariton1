<?php
require_once '../admin/includes/config.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: user_authentication.html');
    exit;
}

$booking_id = $_GET['booking_id'] ?? $_POST['booking_id'] ?? null;
if (!$booking_id) {
    header('Location: my_bookings.php');
    exit;
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: my_bookings.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';
    $ref_no = $_POST['reference_number'] ?? '';

    if (isset($_FILES['proof_of_payment']) && $_FILES['proof_of_payment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/payments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $ext = pathinfo($_FILES['proof_of_payment']['name'], PATHINFO_EXTENSION);
        $filename = 'pay_' . $booking_id . '_' . time() . '.' . $ext;
        $dest = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['proof_of_payment']['tmp_name'], $dest)) {
            try {
                $pdo->beginTransaction();

                // Insert into payments
                $stmt = $pdo->prepare("
                    INSERT INTO payments (booking_id, reference_number, amount, payment_method, payment_type, transaction_reference, proof_of_payment, status)
                    VALUES (?, ?, ?, ?, 'downpayment', ?, ?, 'pending')
                    ON DUPLICATE KEY UPDATE transaction_reference = VALUES(transaction_reference), proof_of_payment = VALUES(proof_of_payment)
                ");

                $ref = 'PAY-' . strtoupper(bin2hex(random_bytes(4)));
                $stmt->execute([
                    $booking_id, $ref, $booking['downpayment_amount'],
                    $payment_method, $ref_no, $filename
                ]);

                $pdo->commit();
                $success = 'Payment proof submitted successfully! Please wait for admin verification.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Failed to move uploaded file.';
        }
    } else {
        $error = 'Please select a file to upload.';
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Submit Payment - Cariton</title>
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
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-background-light dark:bg-background-dark text-gray-900 dark:text-white min-h-screen">
    <header class="sticky top-0 z-50 bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-800">
        <div class="flex items-center justify-between px-4 py-4 max-w-7xl mx-auto">
            <a href="booking_details.php?booking_id=<?= $booking_id ?>" class="flex size-10 items-center justify-center rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <h1 class="text-lg font-bold">Submit Payment</h1>
            <div class="w-10"></div>
        </div>
    </header>

    <main class="max-w-2xl mx-auto p-4">
        <?php if ($success): ?>
            <div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-10 rounded-2xl text-center">
                <span class="material-symbols-outlined text-5xl mb-4">check_circle</span>
                <h2 class="text-xl font-bold mb-2">Submitted!</h2>
                <p class="mb-6"><?= $success ?></p>
                <a href="my_bookings.php" class="bg-emerald-600 text-white px-6 py-2 rounded-lg font-bold">Back to Bookings</a>
            </div>
        <?php else: ?>
            <div class="bg-blue-600 text-white rounded-2xl p-6 mb-6">
                <p class="text-xs uppercase font-bold opacity-80 mb-1">Total Downpayment</p>
                <h2 class="text-3xl font-bold">₱<?= number_format($booking['downpayment_amount'], 2) ?></h2>
                <p class="text-xs mt-4 opacity-80">Reference: <?= $booking['reference_number'] ?></p>
            </div>

            <?php if ($error): ?>
                <div class="bg-rose-100 border border-rose-400 text-rose-700 px-4 py-3 rounded-xl mb-6 text-sm">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="booking_id" value="<?= $booking_id ?>">

                <section>
                    <h3 class="font-bold mb-4">Select Payment Method</h3>
                    <div class="grid grid-cols-1 gap-3">
                        <label class="flex items-center gap-4 border border-gray-200 dark:border-gray-800 p-4 rounded-xl cursor-pointer has-[:checked]:bg-primary/5 has-[:checked]:border-primary transition-all">
                            <input type="radio" name="payment_method" value="gcash" checked class="text-primary focus:ring-primary">
                            <span class="font-medium">GCash</span>
                        </label>
                        <label class="flex items-center gap-4 border border-gray-200 dark:border-gray-800 p-4 rounded-xl cursor-pointer has-[:checked]:bg-primary/5 has-[:checked]:border-primary transition-all">
                            <input type="radio" name="payment_method" value="paymaya" class="text-primary focus:ring-primary">
                            <span class="font-medium">PayMaya</span>
                        </label>
                        <label class="flex items-center gap-4 border border-gray-200 dark:border-gray-800 p-4 rounded-xl cursor-pointer has-[:checked]:bg-primary/5 has-[:checked]:border-primary transition-all">
                            <input type="radio" name="payment_method" value="bank_transfer" class="text-primary focus:ring-primary">
                            <span class="font-medium">Bank Transfer</span>
                        </label>
                    </div>
                </section>

                <section>
                    <label class="block font-bold mb-2">Transaction Reference #</label>
                    <input type="text" name="reference_number" required placeholder="Enter reference number" class="w-full border-gray-200 dark:border-gray-800 dark:bg-gray-900 rounded-xl p-3 focus:ring-primary">
                </section>

                <section>
                    <label class="block font-bold mb-2">Upload Proof of Payment</label>
                    <div class="border-2 border-dashed border-gray-200 dark:border-gray-800 rounded-2xl p-8 text-center cursor-pointer hover:border-primary transition-colors" onclick="document.getElementById('proofFile').click()">
                        <input type="file" id="proofFile" name="proof_of_payment" accept="image/*" class="hidden" required onchange="updateFileName(this)">
                        <span class="material-symbols-outlined text-4xl text-gray-400 mb-2">cloud_upload</span>
                        <p class="text-sm text-gray-500" id="fileText">Tap to select image</p>
                    </div>
                </section>

                <button type="submit" class="w-full bg-primary text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/30 active:scale-95 transition-transform">
                    Confirm & Submit Payment
                </button>
            </form>
        <?php endif; ?>
    </main>

    <script>
        function updateFileName(input) {
            const text = document.getElementById('fileText');
            if (input.files && input.files[0]) {
                text.textContent = "Selected: " + input.files[0].name;
                text.classList.add('text-primary', 'font-bold');
            }
        }
    </script>
</body>
</html>
