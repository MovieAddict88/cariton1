<?php
/**
 * Bookings Management - Admin Page
 */

$page_title = 'Bookings';
require_once 'includes/header.php';

$message = '';
$error = '';

try {
    $pdo = getDBConnection();
    
    // Handle status updates
    if (isset($_POST['update_status'])) {
        $stmt = $pdo->prepare("UPDATE bookings SET booking_status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['booking_id']]);
        $message = "Booking status updated to " . $_POST['status'];
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

        <main class="flex-1 overflow-y-auto p-4 lg:p-8 pb-24 admin-content">
            <?php if ($message): ?>
                <div class="bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-400 text-emerald-700 dark:text-emerald-300 px-4 py-3 rounded-xl mb-6">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="bg-white dark:bg-surface-dark rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
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
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-500">No bookings found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $b): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors">
                                        <td class="px-6 py-4 font-bold text-primary"><?= $b['reference_number'] ?></td>
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
                                                <button onclick="showPickupMap(<?= $b['pickup_latitude'] ?>, <?= $b['pickup_longitude'] ?>, '<?= addslashes(htmlspecialchars($b['pickup_location'])) ?>', '<?= addslashes(htmlspecialchars($b['pickup_description'])) ?>')"
                                                        class="flex size-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors" title="View Pickup Location">
                                                    <span class="material-symbols-outlined text-lg">location_on</span>
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
        </main>
    </div>
</div>

<!-- Pickup Location Modal -->
<div id="pickupModal" class="fixed inset-0 z-[100] hidden overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closePickupModal()"></div>
        <div class="relative w-full max-w-2xl rounded-2xl bg-white dark:bg-surface-dark shadow-2xl border border-slate-200 dark:border-slate-800">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 p-4">
                <h3 class="text-lg font-bold">Pickup Location</h3>
                <button onclick="closePickupModal()" class="flex size-8 items-center justify-center rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="p-6">
                <div class="flex justify-end gap-2 mb-4" id="modalNavButtons">
                    <!-- Dynamic buttons will be added here -->
                </div>
                <div id="adminPickupMap" class="w-full h-80 rounded-xl border border-slate-200 dark:border-slate-800 mb-4 z-0"></div>
                <div class="space-y-4">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase">Address</p>
                        <p id="modalAddress" class="text-sm font-semibold"></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase">Area Description</p>
                        <p id="modalDescription" class="text-sm text-slate-600 dark:text-slate-400 italic"></p>
                    </div>
                </div>
            </div>
            <div class="flex justify-end p-4 border-t border-slate-100 dark:border-slate-800">
                <button onclick="closePickupModal()" class="px-6 py-2 bg-slate-100 dark:bg-slate-800 rounded-lg font-bold text-sm">Close</button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
let adminMap, adminMarker;

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

function showPickupMap(lat, lng, address, description) {
    document.getElementById('pickupModal').classList.remove('hidden');
    document.getElementById('modalAddress').textContent = address;
    document.getElementById('modalDescription').textContent = description || 'No description provided.';

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
            adminMap = L.map('adminPickupMap', { zoomControl: false }).setView([lat, lng], 16);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '© OpenStreetMap © CARTO'
            }).addTo(adminMap);
            L.control.zoom({ position: 'bottomright' }).addTo(adminMap);
        } else {
            // Remove all existing markers
            adminMap.eachLayer((layer) => {
                if (layer instanceof L.Marker) adminMap.removeLayer(layer);
            });
            adminMap.setView([lat, lng], 16);
        }

        adminMarker = L.marker([lat, lng], { icon: carIcon }).addTo(adminMap);
        adminMap.invalidateSize();
    }, 100);
}

function closePickupModal() {
    document.getElementById('pickupModal').classList.add('hidden');
}

function showAllPickups() {
    document.getElementById('pickupModal').classList.remove('hidden');
    document.getElementById('modalAddress').textContent = "Multiple Locations";
    document.getElementById('modalDescription').textContent = "Displaying all active and pending pickups.";

    // Prepare data for markers
    const pickupData = [
        <?php foreach ($bookings as $b): ?>
            <?php if ($b['pickup_latitude'] && $b['pickup_longitude'] && in_array($b['booking_status'], ['pending', 'confirmed', 'active'])): ?>
                {
                    lat: <?= $b['pickup_latitude'] ?>,
                    lng: <?= $b['pickup_longitude'] ?>,
                    ref: '<?= $b['reference_number'] ?>',
                    customer: '<?= addslashes(htmlspecialchars($b['first_name'] . " " . $b['last_name'])) ?>',
                    vehicle: '<?= addslashes(htmlspecialchars($b['make'] . " " . $b['model'])) ?>',
                    location: '<?= addslashes(htmlspecialchars($b['pickup_location'])) ?>'
                },
            <?php endif; ?>
        <?php endforeach; ?>
    ];

    setTimeout(() => {
        if (!adminMap) {
            adminMap = L.map('adminPickupMap').setView([14.5995, 120.9842], 11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(adminMap);
        } else {
            // Remove existing markers if any
            adminMap.eachLayer((layer) => {
                if (layer instanceof L.Marker) adminMap.removeLayer(layer);
            });
        }

        const bounds = [];
        pickupData.forEach(p => {
            const marker = L.marker([p.lat, p.lng]).addTo(adminMap);
            marker.bindPopup(`
                <div class="p-1 min-w-[150px]">
                    <p class="font-bold text-primary mb-1">${p.ref}</p>
                    <p class="text-[11px] leading-relaxed"><b>Customer:</b> ${p.customer}</p>
                    <p class="text-[11px] leading-relaxed"><b>Vehicle:</b> ${p.vehicle}</p>
                    <p class="text-[10px] text-slate-500 mt-2 pt-2 border-t border-slate-100">${p.location}</p>
                </div>
            `);
            bounds.push([p.lat, p.lng]);
        });

        if (bounds.length > 0) {
            adminMap.fitBounds(bounds, { padding: [20, 20] });
        }
        adminMap.invalidateSize();
    }, 100);
}
</script>

<?php include 'includes/footer.php'; ?>
