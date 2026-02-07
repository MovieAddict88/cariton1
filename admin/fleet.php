<?php
/**
 * Fleet Management - Admin Page
 */

$page_title = 'Fleet Management';
require_once 'includes/header.php';

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        
        if (isset($_POST['add_vehicle'])) {
            // Process images - handle both URLs and uploaded files
            $images = [];
            
            // Handle image URLs if provided
            if (!empty($_POST['image_urls'])) {
                $urls = array_map('trim', explode(',', $_POST['image_urls']));
                $images = array_merge($images, array_filter($urls));
            }
            
            // Handle uploaded files
            if (!empty($_FILES['image_files']['name'][0])) {
                $uploadDir = '../uploads/vehicles/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                foreach ($_FILES['image_files']['tmp_name'] as $key => $tmpName) {
                    if ($_FILES['image_files']['error'][$key] === UPLOAD_ERR_OK) {
                        $extension = pathinfo($_FILES['image_files']['name'][$key], PATHINFO_EXTENSION);
                        $filename = 'vehicle_' . uniqid() . '_' . time() . '.' . $extension;
                        $filepath = $uploadDir . $filename;
                        
                        if (move_uploaded_file($tmpName, $filepath)) {
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                            $host = $_SERVER['HTTP_HOST'];
                            $baseUrl = $protocol . '://' . $host . dirname(dirname($_SERVER['SCRIPT_NAME']));
                            $images[] = $baseUrl . '/uploads/vehicles/' . $filename;
                        }
                    }
                }
            }
            
            $imagesJson = json_encode(array_values(array_unique(array_filter($images))));
            $stmt = $pdo->prepare("INSERT INTO vehicles (make, model, year, color, plate_number, vehicle_type, transmission, fuel_type, seats, daily_rate, status, images, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['make'], $_POST['model'], $_POST['year'], $_POST['color'],
                $_POST['plate_number'], $_POST['vehicle_type'], $_POST['transmission'],
                $_POST['fuel_type'], $_POST['seats'], $_POST['daily_rate'], $_POST['status'],
                $imagesJson, $_POST['description'] ?? ''
            ]);
            $message = 'Vehicle added successfully! It will now appear on the user website.';
        } elseif (isset($_POST['update_vehicle'])) {
            // Process images - handle both URLs and uploaded files
            $images = [];
            
            // Keep existing images if checkbox is checked
            if (isset($_POST['keep_existing_images']) && $_POST['keep_existing_images'] === '1') {
                $stmt = $pdo->prepare("SELECT images FROM vehicles WHERE id = ?");
                $stmt->execute([$_POST['vehicle_id']]);
                $existing = $stmt->fetch();
                $existingImages = json_decode($existing['images'] ?? '[]', true);
                if (is_array($existingImages)) {
                    $images = $existingImages;
                }
            }
            
            // Handle image URLs if provided
            if (!empty($_POST['image_urls'])) {
                $urls = array_map('trim', explode(',', $_POST['image_urls']));
                $images = array_merge($images, array_filter($urls));
            }
            
            // Handle uploaded files
            if (!empty($_FILES['image_files']['name'][0])) {
                $uploadDir = '../uploads/vehicles/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                foreach ($_FILES['image_files']['tmp_name'] as $key => $tmpName) {
                    if ($_FILES['image_files']['error'][$key] === UPLOAD_ERR_OK) {
                        $extension = pathinfo($_FILES['image_files']['name'][$key], PATHINFO_EXTENSION);
                        $filename = 'vehicle_' . uniqid() . '_' . time() . '.' . $extension;
                        $filepath = $uploadDir . $filename;
                        
                        if (move_uploaded_file($tmpName, $filepath)) {
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                            $host = $_SERVER['HTTP_HOST'];
                            $baseUrl = $protocol . '://' . $host . dirname(dirname($_SERVER['SCRIPT_NAME']));
                            $images[] = $baseUrl . '/uploads/vehicles/' . $filename;
                        }
                    }
                }
            }
            
            $imagesJson = json_encode(array_values(array_unique(array_filter($images))));
            $stmt = $pdo->prepare("UPDATE vehicles SET make=?, model=?, year=?, color=?, plate_number=?, vehicle_type=?, transmission=?, fuel_type=?, seats=?, daily_rate=?, status=?, images=?, description=? WHERE id=?");
            $stmt->execute([
                $_POST['make'], $_POST['model'], $_POST['year'], $_POST['color'],
                $_POST['plate_number'], $_POST['vehicle_type'], $_POST['transmission'],
                $_POST['fuel_type'], $_POST['seats'], $_POST['daily_rate'], $_POST['status'],
                $imagesJson, $_POST['description'] ?? '', $_POST['vehicle_id']
            ]);
            $message = 'Vehicle updated successfully! Changes are live on the user website.';
        } elseif (isset($_POST['update_status'])) {
            $stmt = $pdo->prepare("UPDATE vehicles SET status = ? WHERE id = ?");
            $stmt->execute([$_POST['status'], $_POST['vehicle_id']]);
            $message = 'Vehicle status updated! Real-time update reflects on user website.';
        } elseif (isset($_POST['delete_vehicle'])) {
            $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
            $stmt->execute([$_POST['vehicle_id']]);
            $message = 'Vehicle removed! It is no longer available on the user website.';
        } elseif (isset($_POST['toggle_featured'])) {
            $stmt = $pdo->prepare("UPDATE vehicles SET is_featured = !is_featured WHERE id = ?");
            $stmt->execute([$_POST['vehicle_id']]);
            $message = 'Featured status updated!';
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all vehicles
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM vehicles ORDER BY created_at DESC");
    $vehicles = $stmt->fetchAll();
    
    // Stats
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM vehicles GROUP BY status");
    $status_counts = $stmt->fetchAll();
    $status_counts = array_column($status_counts, 'count', 'status');
} catch (PDOException $e) {
    $vehicles = [];
    $status_counts = ['available' => 0, 'rented' => 0, 'maintenance' => 0];
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
                <h2 class="text-slate-900 dark:text-white text-lg font-bold leading-tight tracking-[-0.015em]">Fleet Management</h2>
            </div>
            
            <div class="flex items-center gap-4">
                <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 flex items-center gap-2 text-sm font-bold">
                    <span class="material-symbols-outlined">add</span>
                    <span class="hidden sm:inline">Add Vehicle</span>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 lg:p-8 pb-24 admin-content">
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-surface-dark rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Total Fleet</p>
                    <p class="text-2xl font-bold"><?= count($vehicles) ?></p>
                </div>
                <div class="bg-white dark:bg-surface-dark rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Available</p>
                    <p class="text-2xl font-bold text-emerald-500"><?= $status_counts['available'] ?? 0 ?></p>
                </div>
                <div class="bg-white dark:bg-surface-dark rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Rented</p>
                    <p class="text-2xl font-bold text-primary"><?= $status_counts['rented'] ?? 0 ?></p>
                </div>
                <div class="bg-white dark:bg-surface-dark rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Maintenance</p>
                    <p class="text-2xl font-bold text-orange-500"><?= $status_counts['maintenance'] ?? 0 ?></p>
                </div>
            </div>

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

            <!-- Vehicles Grid (Responsive) -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php foreach ($vehicles as $v): ?>
                <div class="bg-white dark:bg-surface-dark rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm group">
                    <div class="relative aspect-video bg-slate-100 dark:bg-slate-800 overflow-hidden">
                        <?php 
                        $images = json_decode($v['images'] ?? '[]', true);
                        $image_url = !empty($images) ? $images[0] : 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&q=80&w=400';
                        ?>
                        <img src="<?= $image_url ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="<?= $v['make'] ?>">
                        <div class="absolute top-4 right-4">
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?= 
                                $v['status'] === 'available' ? 'bg-emerald-500 text-white' : 
                                ($v['status'] === 'rented' ? 'bg-primary text-white' : 'bg-orange-500 text-white')
                            ?>">
                                <?= $v['status'] ?>
                            </span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="font-bold text-lg"><?= htmlspecialchars($v['make'] . ' ' . $v['model']) ?></h3>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars($v['year'] . ' • ' . $v['fuel_type'] . ' • ' . $v['transmission']) ?></p>
                            </div>
                            <p class="text-primary font-bold text-lg"><?= formatCurrency(convertCurrency($v['daily_rate'], 'PHP', $selected_currency), $selected_currency) ?><span class="text-[10px] text-slate-400 font-normal">/day</span></p>
                        </div>
                        
                        <div class="flex items-center gap-4 text-xs text-slate-500 mb-6">
                            <div class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">event_seat</span>
                                <?= $v['seats'] ?> Seats
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">pin</span>
                                <?= htmlspecialchars($v['plate_number']) ?>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <form method="POST" class="flex-1">
                                <input type="hidden" name="vehicle_id" value="<?= $v['id'] ?>">
                                <input type="hidden" name="update_status" value="1">
                                <select name="status" onchange="this.form.submit()" class="w-full text-xs font-bold border-slate-200 dark:border-slate-800 rounded-lg bg-slate-50 dark:bg-slate-900 focus:ring-primary">
                                    <option value="available" <?= $v['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                                    <option value="rented" <?= $v['status'] === 'rented' ? 'selected' : '' ?>>Rented</option>
                                    <option value="maintenance" <?= $v['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                </select>
                            </form>
                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($v)) ?>)" class="p-2 text-slate-400 hover:text-primary transition-colors">
                                <span class="material-symbols-outlined">edit</span>
                            </button>
                            <form method="POST" onsubmit="return confirm('Delete this vehicle?');" class="inline">
                                <input type="hidden" name="vehicle_id" value="<?= $v['id'] ?>">
                                <button type="submit" name="delete_vehicle" class="p-2 text-slate-400 hover:text-rose-500 transition-colors">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-surface-dark rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
            <h3 class="text-lg font-bold">Add New Vehicle</h3>
            <button onclick="document.getElementById('addModal').classList.add('hidden')" class="p-2 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-full transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto no-scrollbar">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Make</label>
                    <input type="text" name="make" required placeholder="e.g. Tesla" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Model</label>
                    <input type="text" name="model" required placeholder="e.g. Model 3" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Year</label>
                    <input type="number" name="year" required value="2024" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Color</label>
                    <input type="text" name="color" required placeholder="White" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Plate</label>
                    <input type="text" name="plate_number" required placeholder="ABC-123" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Type</label>
                    <select name="vehicle_type" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                        <option value="sedan">Sedan</option>
                        <option value="suv">SUV</option>
                        <option value="luxury">Luxury</option>
                        <option value="electric">Electric</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Transmission</label>
                    <select name="transmission" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                        <option value="automatic">Automatic</option>
                        <option value="manual">Manual</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fuel Type</label>
                    <select name="fuel_type" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                        <option value="gasoline">Gasoline</option>
                        <option value="diesel">Diesel</option>
                        <option value="electric">Electric</option>
                        <option value="hybrid">Hybrid</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Seats</label>
                    <input type="number" name="seats" required value="5" min="2" max="12" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Daily Rate (₱)</label>
                <input type="number" name="daily_rate" required step="0.01" placeholder="0.00" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary font-bold text-primary">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                <select name="status" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                    <option value="available">Available</option>
                    <option value="rented">Rented</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
            
            <!-- Image Upload Options -->
            <div class="border-t border-slate-200 dark:border-slate-800 pt-4 mt-4">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Vehicle Images</label>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Option 1: Image URLs (comma separated)</label>
                        <input type="text" name="image_urls" placeholder="https://example.com/image1.jpg,https://example.com/image2.jpg" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary text-sm">
                        <p class="text-[10px] text-slate-400 mt-1">Paste image URLs separated by commas</p>
                    </div>
                    <div class="relative">
                        <label class="block text-xs text-slate-400 mb-1">Option 2: Upload Images</label>
                        <input type="file" name="image_files[]" multiple accept="image/*" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary text-sm file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/90">
                        <p class="text-[10px] text-slate-400 mt-1">Upload one or multiple images (JPG, PNG, WebP, max 5MB each)</p>
                    </div>
                </div>
            </div>
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Description</label>
                <textarea name="description" rows="3" placeholder="Vehicle description..." class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary"></textarea>
            </div>
            <div class="flex gap-4 pt-4">
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="flex-1 px-4 py-3 border border-slate-200 dark:border-slate-800 rounded-xl font-bold hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors">
                    Cancel
                </button>
                <button type="submit" name="add_vehicle" class="flex-1 px-4 py-3 bg-primary text-white rounded-xl font-bold shadow-lg shadow-primary/30 hover:bg-primary/90 transition-colors">
                    Add Vehicle
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Edit vehicle modal
function openEditModal(vehicle) {
    const modal = document.getElementById('editModal');
    const form = document.getElementById('editForm');
    
    // Fill form with vehicle data
    form.vehicle_id.value = vehicle.id;
    form.make.value = vehicle.make;
    form.model.value = vehicle.model;
    form.year.value = vehicle.year;
    form.color.value = vehicle.color;
    form.plate_number.value = vehicle.plate_number;
    form.vehicle_type.value = vehicle.vehicle_type;
    form.transmission.value = vehicle.transmission;
    form.fuel_type.value = vehicle.fuel_type;
    form.seats.value = vehicle.seats;
    form.daily_rate.value = vehicle.daily_rate;
    form.status.value = vehicle.status;
    
    // Handle images
    let images = [];
    try {
        images = typeof vehicle.images === 'string' ? JSON.parse(vehicle.images || '[]') : (vehicle.images || []);
    } catch(e) {
        images = [];
    }
    form.image_urls.value = images.join(',');
    
    form.description.value = vehicle.description || '';
    
    modal.classList.remove('hidden');
}
</script>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-surface-dark rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
            <h3 class="text-lg font-bold">Edit Vehicle</h3>
            <button onclick="document.getElementById('editModal').classList.add('hidden')" class="p-2 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-full transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" enctype="multipart/form-data" id="editForm" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto no-scrollbar">
            <input type="hidden" name="vehicle_id">
            <input type="hidden" name="update_vehicle" value="1">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Make</label>
                    <input type="text" name="make" required class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Model</label>
                    <input type="text" name="model" required class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Year</label>
                    <input type="number" name="year" required class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Color</label>
                    <input type="text" name="color" required class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Plate</label>
                    <input type="text" name="plate_number" required class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Type</label>
                    <select name="vehicle_type" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                        <option value="sedan">Sedan</option>
                        <option value="suv">SUV</option>
                        <option value="luxury">Luxury</option>
                        <option value="electric">Electric</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Transmission</label>
                    <select name="transmission" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                        <option value="automatic">Automatic</option>
                        <option value="manual">Manual</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fuel Type</label>
                    <select name="fuel_type" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                        <option value="gasoline">Gasoline</option>
                        <option value="diesel">Diesel</option>
                        <option value="electric">Electric</option>
                        <option value="hybrid">Hybrid</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Seats</label>
                    <input type="number" name="seats" required min="2" max="12" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Daily Rate (₱)</label>
                <input type="number" name="daily_rate" required step="0.01" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary font-bold text-primary">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                <select name="status" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                    <option value="available">Available</option>
                    <option value="rented">Rented</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
            
            <!-- Image Upload Options -->
            <div class="border-t border-slate-200 dark:border-slate-800 pt-4 mt-4">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Vehicle Images</label>
                <div class="space-y-3">
                    <div class="flex items-center gap-2 mb-2">
                        <input type="checkbox" name="keep_existing_images" value="1" id="keepImages" class="rounded border-slate-300 dark:border-slate-700 text-primary focus:ring-primary">
                        <label for="keepImages" class="text-xs text-slate-500">Keep existing images</label>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Option 1: Image URLs (comma separated)</label>
                        <input type="text" name="image_urls" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary text-sm">
                        <p class="text-[10px] text-slate-400 mt-1">Add new image URLs separated by commas</p>
                    </div>
                    <div class="relative">
                        <label class="block text-xs text-slate-400 mb-1">Option 2: Upload Images</label>
                        <input type="file" name="image_files[]" multiple accept="image/*" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary text-sm file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/90">
                        <p class="text-[10px] text-slate-400 mt-1">Upload new images (JPG, PNG, WebP, max 5MB each)</p>
                    </div>
                </div>
            </div>
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary"></textarea>
            </div>
            <div class="flex gap-4 pt-4">
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="flex-1 px-4 py-3 border border-slate-200 dark:border-slate-800 rounded-xl font-bold hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-3 bg-primary text-white rounded-xl font-bold shadow-lg shadow-primary/30 hover:bg-primary/90 transition-colors">
                    Update Vehicle
                </button>
            </div>
        </form>
    </div>
</div>
