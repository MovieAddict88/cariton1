<?php
/**
 * Bookings Management - Admin Page
 */

$page_title = 'Bookings';
require_once 'includes/header.php';

?>
<style>
    :root {
        --fluid-text-xs: clamp(0.6rem, 0.5rem + 0.5vw, 0.75rem);
        --fluid-text-sm: clamp(0.75rem, 0.7rem + 0.25vw, 0.875rem);
        --fluid-text-base: clamp(0.875rem, 0.8rem + 0.35vw, 1rem);
        --fluid-text-lg: clamp(1rem, 0.9rem + 0.5vw, 1.25rem);
        --fluid-text-xl: clamp(1.25rem, 1.1rem + 0.75vw, 1.75rem);
        --fluid-padding: clamp(1rem, 0.8rem + 1vw, 2rem);
    }

    .responsive-card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(clamp(300px, 100%, 450px), 1fr));
        gap: var(--fluid-padding);
    }

    .fluid-p { padding: var(--fluid-padding); }
    .fluid-px { padding-left: var(--fluid-padding); padding-right: var(--fluid-padding); }
    .fluid-py { padding-top: var(--fluid-padding); padding-bottom: var(--fluid-padding); }

    .text-fluid-xs { font-size: var(--fluid-text-xs); }
    .text-fluid-sm { font-size: var(--fluid-text-sm); }
    .text-fluid-base { font-size: var(--fluid-text-base); }
    .text-fluid-lg { font-size: var(--fluid-text-lg); }
    .text-fluid-xl { font-size: var(--fluid-text-xl); }

    @media (max-width: 1023px) {
        .admin-table-container { display: none; }
        .admin-cards-container { display: block; }
    }
    @media (min-width: 1024px) {
        .admin-table-container { display: block; }
        .admin-cards-container { display: none; }
    }
</style>
<?php

$message = '';
$error = '';

try {
    $pdo = getDBConnection();
    
    // Handle status updates
    if (isset($_POST['update_status'])) {
        $status = $_POST['status'];
        $booking_id = $_POST['booking_id'];

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE bookings SET booking_status = ? WHERE id = ?");
            $stmt->execute([$status, $booking_id]);

            // If completed, make vehicle available again
            if ($status === 'completed') {
                $stmt = $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE id = (SELECT vehicle_id FROM bookings WHERE id = ?)");
                $stmt->execute([$booking_id]);
            }
            // If active, mark vehicle as rented
            if ($status === 'active') {
                $stmt = $pdo->prepare("UPDATE vehicles SET status = 'rented' WHERE id = (SELECT vehicle_id FROM bookings WHERE id = ?)");
                $stmt->execute([$booking_id]);
            }

            $pdo->commit();
            $message = "Booking status updated to " . $status;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error updating status: " . $e->getMessage();
        }
    }

    if (isset($_POST['update_driver'])) {
        $driver_id = !empty($_POST['driver_id']) ? $_POST['driver_id'] : null;
        $stmt = $pdo->prepare("UPDATE bookings SET driver_id = ? WHERE id = ?");
        $stmt->execute([$driver_id, $_POST['booking_id']]);
        $message = "Driver assignment updated!";
    }

    // Get all drivers for the dropdown (including inactive ones if they are currently assigned)
    $stmt = $pdo->query("SELECT id, first_name, last_name, employee_id, status FROM drivers ORDER BY first_name ASC");
    $all_drivers = $stmt->fetchAll();

    // Get bookings
    $stmt = $pdo->query("SELECT b.*, u.first_name, u.last_name, v.make, v.model, v.plate_number,
                         d.first_name as driver_first_name, d.last_name as driver_last_name
                         FROM bookings b 
                         LEFT JOIN users u ON b.user_id = u.id 
                         LEFT JOIN vehicles v ON b.vehicle_id = v.id 
                         LEFT JOIN drivers d ON b.driver_id = d.id
                         ORDER BY b.created_at DESC");
    $bookings = $stmt->fetchAll();

} catch (PDOException $e) {
    $bookings = [];
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
                <h2 class="text-slate-900 dark:text-white text-lg font-bold">Bookings Management</h2>
            </div>
            <button onclick="showAllPickups()" class="hidden lg:flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-xl font-bold text-sm shadow-lg shadow-primary/20">
                <span class="material-symbols-outlined text-sm">map</span>
                View All Pickups
            </button>
        </header>

        <main class="flex-1 overflow-y-auto fluid-p pb-24 admin-content">
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

            <!-- Table for Desktop -->
            <div class="admin-table-container bg-white dark:bg-surface-dark rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm mb-6">
                <div class="table-scroll overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[900px]">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50">
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Reference</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Customer</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Vehicle</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Dates</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Total</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Downpayment</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Balance</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Driver</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="10" class="px-6 py-12 text-center text-slate-500 text-fluid-base">No bookings found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $b): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors">
                                        <td class="px-6 py-4 font-bold text-primary text-fluid-sm"><?= $b['reference_number'] ?></td>
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-sm"><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></p>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?= htmlspecialchars($b['make'] . ' ' . $b['model']) ?>
                                            <p class="text-xs text-slate-500"><?= $b['plate_number'] ?></p>
                                        </td>
                                        <td class="px-6 py-4 text-xs">
                                            <p><span class="text-slate-400">Pick:</span> <?= date('M d, Y', strtotime($b['pickup_date'])) ?></p>
                                            <p><span class="text-slate-400">Drop:</span> <?= date('M d, Y', strtotime($b['dropoff_date'])) ?></p>
                                        </td>
                                        <td class="px-6 py-4 font-bold">
                                            <?= formatCurrency(convertCurrency($b['total_amount'], 'PHP', $selected_currency), $selected_currency) ?>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-bold text-emerald-600">
                                            <?= formatCurrency(convertCurrency($b['downpayment_amount'], 'PHP', $selected_currency), $selected_currency) ?>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-bold text-orange-600">
                                            <?= formatCurrency(convertCurrency($b['balance_amount'], 'PHP', $selected_currency), $selected_currency) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase <?= 
                                                $b['booking_status'] === 'completed' ? 'bg-green-100 text-green-700' : 
                                                ($b['booking_status'] === 'active' ? 'bg-blue-100 text-blue-700' : 
                                                ($b['booking_status'] === 'pending' ? 'bg-orange-100 text-orange-700' : 'bg-slate-100 text-slate-700'))
                                            ?>">
                                                <?= $b['booking_status'] ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col gap-1">
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                    <input type="hidden" name="update_driver" value="1">
                                                    <select name="driver_id" onchange="this.form.submit()" class="text-[10px] font-bold border-slate-200 dark:border-slate-800 rounded-lg bg-slate-50 dark:bg-slate-900 focus:ring-primary w-full cursor-pointer">
                                                        <option value="">Assign Driver</option>
                                                        <?php foreach ($all_drivers as $ad): ?>
                                                            <option value="<?= $ad['id'] ?>" <?= $b['driver_id'] == $ad['id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($ad['first_name'] . ' ' . $ad['last_name']) ?> (<?= $ad['employee_id'] ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </form>
                                                <?php if ($b['driver_id']): ?>
                                                    <div class="flex items-center gap-1 text-[9px] font-bold text-slate-500 bg-slate-100 dark:bg-slate-800/50 px-2 py-0.5 rounded-full w-fit">
                                                        <span class="material-symbols-outlined text-[10px]" style="font-variation-settings: 'FILL' 1">license</span>
                                                        <?= htmlspecialchars($b['driver_first_name'] . ' ' . $b['driver_last_name']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right flex items-center justify-end gap-2">
                                            <a href="booking_details.php?id=<?= $b['id'] ?>"
                                               class="flex size-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors" title="View Details">
                                                <span class="material-symbols-outlined text-lg">visibility</span>
                                            </a>
                                            <?php if ($b['pickup_latitude'] && $b['pickup_longitude']): ?>
                                                <button onclick="showLocationMap(<?= $b['id'] ?>, 'pickup')"
                                                        class="flex size-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors" title="View Pickup Location">
                                                    <span class="material-symbols-outlined text-lg">location_on</span>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($b['dropoff_latitude'] && $b['dropoff_longitude']): ?>
                                                <button onclick="showLocationMap(<?= $b['id'] ?>, 'dropoff')"
                                                        class="flex size-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors" title="View Return Location">
                                                    <span class="material-symbols-outlined text-lg">keyboard_return</span>
                                                </button>
                                            <?php endif; ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                <input type="hidden" name="update_status" value="1">
                                                <select name="status" onchange="this.form.submit()" class="text-[10px] font-bold border-slate-200 dark:border-slate-800 rounded-lg bg-slate-50 dark:bg-slate-900 focus:ring-primary">
                                                    <option value="pending" <?= $b['booking_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="confirmed" <?= $b['booking_status'] === 'confirmed' ? 'selected' : '' ?>>Confirm</option>
                                                    <option value="active" <?= $b['booking_status'] === 'active' ? 'selected' : '' ?>>Set Active</option>
                                                    <option value="completed" <?= $b['booking_status'] === 'completed' ? 'selected' : '' ?>>Complete</option>
                                                    <option value="cancelled" <?= $b['booking_status'] === 'cancelled' ? 'selected' : '' ?>>Cancel</option>
                                                </select>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Cards for Mobile -->
            <div class="admin-cards-container">
                <div class="responsive-card-grid">
                    <?php if (empty($bookings)): ?>
                        <div class="col-span-full bg-white dark:bg-surface-dark p-12 text-center rounded-2xl border border-slate-200 dark:border-slate-800">
                            <p class="text-slate-500 text-fluid-base">No bookings found.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($bookings as $b): ?>
                            <div class="bg-white dark:bg-surface-dark rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <p class="text-primary font-bold text-fluid-lg"><?= $b['reference_number'] ?></p>
                                        <p class="text-xs text-slate-500"><?= date('M d, Y', strtotime($b['created_at'])) ?></p>
                                    </div>
                                    <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase <?=
                                        $b['booking_status'] === 'completed' ? 'bg-green-100 text-green-700' :
                                        ($b['booking_status'] === 'active' ? 'bg-blue-100 text-blue-700' :
                                        ($b['booking_status'] === 'pending' ? 'bg-orange-100 text-orange-700' : 'bg-slate-100 text-slate-700'))
                                    ?>">
                                        <?= $b['booking_status'] ?>
                                    </span>
                                </div>

                                <div class="space-y-3 mb-6">
                                    <div class="flex items-center gap-3">
                                        <div class="size-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500">
                                            <span class="material-symbols-outlined text-fluid-base">person</span>
                                        </div>
                                        <div>
                                            <p class="text-fluid-sm font-bold"><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></p>
                                            <p class="text-xs text-slate-500">Customer</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="size-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500">
                                            <span class="material-symbols-outlined text-fluid-base">directions_car</span>
                                        </div>
                                        <div>
                                            <p class="text-fluid-sm font-bold"><?= htmlspecialchars($b['make'] . ' ' . $b['model']) ?></p>
                                            <p class="text-xs text-slate-500"><?= $b['plate_number'] ?></p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase">Total</p>
                                            <p class="font-bold text-fluid-sm"><?= formatCurrency(convertCurrency($b['total_amount'], 'PHP', $selected_currency), $selected_currency) ?></p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase">Balance</p>
                                            <p class="font-bold text-fluid-sm text-orange-600"><?= formatCurrency(convertCurrency($b['balance_amount'], 'PHP', $selected_currency), $selected_currency) ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <a href="booking_details.php?id=<?= $b['id'] ?>" class="flex-1 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white py-2 rounded-lg font-bold text-xs text-center">View Details</a>
                                    <div class="flex gap-1">
                                        <?php if ($b['pickup_latitude']): ?>
                                            <button onclick="showLocationMap(<?= $b['id'] ?>, 'pickup')" class="size-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center"><span class="material-symbols-outlined text-lg">location_on</span></button>
                                        <?php endif; ?>
                                        <?php if ($b['dropoff_latitude']): ?>
                                            <button onclick="showLocationMap(<?= $b['id'] ?>, 'dropoff')" class="size-9 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center"><span class="material-symbols-outlined text-lg">keyboard_return</span></button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Location Modal -->
<div id="locationModal" class="fixed inset-0 z-[100] hidden overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeLocationModal()"></div>
        <div class="relative w-full max-w-2xl rounded-2xl bg-white dark:bg-surface-dark shadow-2xl border border-slate-200 dark:border-slate-800">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 p-4">
                <h3 id="modalTitle" class="text-lg font-bold">Location Details</h3>
                <button onclick="closeLocationModal()" class="flex size-8 items-center justify-center rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <div id="modalBadge" class="px-3 py-1 rounded-full text-[10px] font-bold uppercase"></div>
                    <div class="flex justify-end gap-2" id="modalNavButtons">
                        <!-- Dynamic buttons will be added here -->
                    </div>
                </div>
                <div id="adminLocationMap" class="w-full h-80 rounded-xl border border-slate-200 dark:border-slate-800 mb-4 z-0"></div>
                <div class="space-y-4">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase">Address</p>
                        <p id="modalAddress" class="text-fluid-sm font-semibold"></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase">Area Description</p>
                        <p id="modalDescription" class="text-fluid-xs text-slate-600 dark:text-slate-400 italic"></p>
                    </div>
                </div>
            </div>
            <div class="flex justify-end p-4 border-t border-slate-100 dark:border-slate-800">
                <button onclick="closeLocationModal()" class="px-6 py-2 bg-slate-100 dark:bg-slate-800 rounded-lg font-bold text-sm">Close</button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
let adminMap, adminMarker;
const bookingsData = <?= json_encode($bookings) ?>;

const carIcon = L.divIcon({
    html: `
        <div class="relative flex items-center justify-center">
            <div class="absolute size-10 bg-primary/30 rounded-full animate-ping"></div>
            <div class="size-8 bg-primary text-white rounded-full flex items-center justify-center shadow-lg border-2 border-white">
                <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1">directions_car</span>
            </div>
        </div>
    `,
    className: '',
    iconSize: [32, 32],
    iconAnchor: [16, 16]
});

function showLocationMap(bookingId, type) {
    const booking = bookingsData.find(b => b.id == bookingId);
    if (!booking) return;

    const lat = type === 'pickup' ? booking.pickup_latitude : booking.dropoff_latitude;
    const lng = type === 'pickup' ? booking.pickup_longitude : booking.dropoff_longitude;
    const address = type === 'pickup' ? booking.pickup_location : booking.dropoff_location;
    const description = type === 'pickup' ? booking.pickup_description : booking.dropoff_description;

    document.getElementById('locationModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = type === 'pickup' ? 'Pickup Location' : 'Return Location';
    document.getElementById('modalAddress').textContent = address;
    document.getElementById('modalDescription').textContent = description || 'No description provided.';

    const badge = document.getElementById('modalBadge');
    badge.textContent = type === 'pickup' ? 'Pickup Spot' : 'Return Spot';
    badge.className = `px-3 py-1 rounded-full text-[10px] font-bold uppercase ${type === 'pickup' ? 'bg-blue-100 text-blue-700' : 'bg-rose-100 text-rose-700'}`;

    // Add Nav Buttons
    const navButtons = document.getElementById('modalNavButtons');
    navButtons.innerHTML = `
        <a href="https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}" target="_blank" class="flex items-center gap-1 px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-bold">
            <span class="material-symbols-outlined text-xs">map</span> Google Maps
        </a>
        <a href="https://waze.com/ul?ll=${lat},${lng}&navigate=yes" target="_blank" class="flex items-center gap-1 px-2 py-1 bg-[#33ccff]/10 text-[#33ccff] rounded text-[10px] font-bold">
            <img src="https://www.vectorlogo.zone/logos/waze/waze-icon.svg" class="size-3" alt="Waze"> Waze
        </a>
    `;

    setTimeout(() => {
        if (!adminMap) {
            adminMap = L.map('adminLocationMap', { zoomControl: false }).setView([lat, lng], 16);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '© OpenStreetMap © CARTO'
            }).addTo(adminMap);
            L.control.zoom({ position: 'bottomright' }).addTo(adminMap);
        } else {
            adminMap.eachLayer((layer) => {
                if (layer instanceof L.Marker) adminMap.removeLayer(layer);
            });
            adminMap.setView([lat, lng], 16);
        }

        adminMarker = L.marker([lat, lng], { icon: carIcon }).addTo(adminMap);
        adminMap.invalidateSize();
    }, 100);
}

function closeLocationModal() {
    document.getElementById('locationModal').classList.add('hidden');
}

function showAllPickups() {
    document.getElementById('locationModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = "All Active Locations";
    document.getElementById('modalAddress').textContent = "Multiple Locations";
    document.getElementById('modalDescription').textContent = "Displaying all active and pending pickups/returns.";
    document.getElementById('modalBadge').className = 'hidden';

    setTimeout(() => {
        if (!adminMap) {
            adminMap = L.map('adminLocationMap').setView([14.5995, 120.9842], 11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(adminMap);
        } else {
            adminMap.eachLayer((layer) => {
                if (layer instanceof L.Marker) adminMap.removeLayer(layer);
            });
        }

        const bounds = [];
        bookingsData.forEach(b => {
            if (b.pickup_latitude && b.pickup_longitude && ['pending', 'confirmed', 'active'].includes(b.booking_status)) {
                const pMarker = L.marker([b.pickup_latitude, b.pickup_longitude]).addTo(adminMap);
                pMarker.bindPopup(`
                    <div class="p-1 min-w-[150px]">
                        <p class="font-bold text-primary mb-1">${b.reference_number}</p>
                        <p class="text-[11px]"><b>Type:</b> Pickup</p>
                        <p class="text-[11px]"><b>Customer:</b> ${b.first_name} ${b.last_name}</p>
                        <p class="text-[10px] text-slate-500 mt-2 pt-2 border-t border-slate-100">${b.pickup_location}</p>
                    </div>
                `);
                bounds.push([b.pickup_latitude, b.pickup_longitude]);
            }
            if (b.dropoff_latitude && b.dropoff_longitude && ['active'].includes(b.booking_status)) {
                const dMarker = L.marker([b.dropoff_latitude, b.dropoff_longitude], {icon: carIcon}).addTo(adminMap);
                dMarker.bindPopup(`
                    <div class="p-1 min-w-[150px]">
                        <p class="font-bold text-rose-500 mb-1">${b.reference_number}</p>
                        <p class="text-[11px]"><b>Type:</b> Return</p>
                        <p class="text-[11px]"><b>Customer:</b> ${b.first_name} ${b.last_name}</p>
                        <p class="text-[10px] text-slate-500 mt-2 pt-2 border-t border-slate-100">${b.dropoff_location}</p>
                    </div>
                `);
                bounds.push([b.dropoff_latitude, b.dropoff_longitude]);
            }
        });

        if (bounds.length > 0) {
            adminMap.fitBounds(bounds, { padding: [20, 20] });
        }
        adminMap.invalidateSize();
    }, 100);
}
</script>

<?php include 'includes/footer.php'; ?>
