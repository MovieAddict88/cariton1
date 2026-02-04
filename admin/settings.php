<?php
/**
 * Settings - Admin Page
 */

$page_title = 'Settings';
require_once 'includes/header.php';

$message = '';
$error = '';

try {
    $pdo = getDBConnection();
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_system_settings'])) {
            // Update system settings
            foreach ($_POST['settings'] as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->execute([$key, $value]);
            }
            $message = 'System settings updated successfully!';
        } elseif (isset($_POST['update_company_info'])) {
            // Update company information
            $stmt = $pdo->prepare("UPDATE company_info SET company_name = ?, address = ?, phone = ?, email = ?, business_hours = ? WHERE id = 1");
            $stmt->execute([
                $_POST['company_name'], $_POST['address'], $_POST['phone'], 
                $_POST['email'], $_POST['business_hours']
            ]);
            $message = 'Company information updated successfully!';
        } elseif (isset($_POST['update_pricing'])) {
            // Update pricing settings
            $stmt = $pdo->prepare("INSERT INTO pricing_settings (setting_type, base_price, price_per_hour, late_fee, cleaning_fee) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE base_price = VALUES(base_price), price_per_hour = VALUES(price_per_hour), late_fee = VALUES(late_fee), cleaning_fee = VALUES(cleaning_fee)");
            $stmt->execute([
                $_POST['setting_type'], $_POST['base_price'], $_POST['price_per_hour'], 
                $_POST['late_fee'], $_POST['cleaning_fee']
            ]);
            $message = 'Pricing settings updated successfully!';
        }
    }

    // Get current settings
    $stmt = $pdo->query("SELECT * FROM system_settings");
    $system_settings = [];
    while ($row = $stmt->fetch()) {
        $system_settings[$row['setting_key']] = $row['setting_value'];
    }

    $stmt = $pdo->query("SELECT * FROM company_info WHERE id = 1");
    $company_info = $stmt->fetch();

    $stmt = $pdo->query("SELECT * FROM pricing_settings");
    $pricing_settings = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="flex h-screen overflow-hidden">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="sticky top-0 z-30 flex items-center bg-white/80 dark:bg-background-dark/80 backdrop-blur-md p-4 justify-between border-b border-slate-200 dark:border-slate-800 lg:px-8">
            <h2 class="text-slate-900 dark:text-white text-lg font-bold">System Settings</h2>
        </header>

        <main class="flex-1 overflow-y-auto p-4 lg:p-8 pb-24 admin-content">
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

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- System Settings -->
                <div class="bg-white dark:bg-surface-dark rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                    <h3 class="text-lg font-bold mb-6">System Settings</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="update_system_settings" value="1">
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Site Name</label>
                            <input type="text" name="settings[site_name]" value="<?= htmlspecialchars($system_settings['site_name'] ?? 'Cariton') ?>" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Maintenance Mode</label>
                            <select name="settings[maintenance_mode]" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                                <option value="0" <?= ($system_settings['maintenance_mode'] ?? '0') === '0' ? 'selected' : '' ?>>Disabled</option>
                                <option value="1" <?= ($system_settings['maintenance_mode'] ?? '0') === '1' ? 'selected' : '' ?>>Enabled</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Max Upload Size (MB)</label>
                            <input type="number" name="settings[max_upload_size]" value="<?= htmlspecialchars($system_settings['max_upload_size'] ?? '5') ?>" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Session Timeout (minutes)</label>
                            <input type="number" name="settings[session_timeout]" value="<?= htmlspecialchars($system_settings['session_timeout'] ?? '120') ?>" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                        </div>
                        
                        <button type="submit" class="w-full px-4 py-3 bg-primary text-white rounded-xl font-bold shadow-lg shadow-primary/30 hover:bg-primary/90 transition-colors">
                            Update System Settings
                        </button>
                    </form>
                </div>

                <!-- Company Information -->
                <div class="bg-white dark:bg-surface-dark rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                    <h3 class="text-lg font-bold mb-6">Company Information</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="update_company_info" value="1">
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Company Name</label>
                            <input type="text" name="company_name" value="<?= htmlspecialchars($company_info['company_name'] ?? 'Cariton Rental Services') ?>" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Address</label>
                            <textarea name="address" rows="3" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary"><?= htmlspecialchars($company_info['address'] ?? '') ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Phone</label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($company_info['phone'] ?? '+63 2 8123 4567') ?>" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($company_info['email'] ?? 'info@cariton.com') ?>" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Business Hours</label>
                            <input type="text" name="business_hours" value="<?= htmlspecialchars($company_info['business_hours'] ?? 'Mon-Sun: 6:00 AM - 10:00 PM') ?>" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                        </div>
                        
                        <button type="submit" class="w-full px-4 py-3 bg-primary text-white rounded-xl font-bold shadow-lg shadow-primary/30 hover:bg-primary/90 transition-colors">
                            Update Company Info
                        </button>
                    </form>
                </div>
            </div>

            <!-- Pricing Settings -->
            <div class="mt-6 bg-white dark:bg-surface-dark rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                <h3 class="text-lg font-bold mb-6">Pricing Settings</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="update_pricing" value="1">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Vehicle Type</label>
                            <select name="setting_type" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                                <option value="sedan">Sedan</option>
                                <option value="suv">SUV</option>
                                <option value="luxury">Luxury</option>
                                <option value="electric">Electric</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Base Price (₱)</label>
                            <input type="number" name="base_price" step="0.01" value="1500" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Price Per Hour (₱)</label>
                            <input type="number" name="price_per_hour" step="0.01" value="200" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Late Fee (₱)</label>
                            <input type="number" name="late_fee" step="0.01" value="300" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Cleaning Fee (₱)</label>
                        <input type="number" name="cleaning_fee" step="0.01" value="150" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                    </div>
                    
                    <button type="submit" class="px-6 py-3 bg-primary text-white rounded-xl font-bold shadow-lg shadow-primary/30 hover:bg-primary/90 transition-colors">
                        Update Pricing
                    </button>
                </form>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>