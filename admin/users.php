<?php
/**
 * User Management - Admin Page
 */

$page_title = 'Users';
require_once 'includes/header.php';

$message = '';
$error = '';

try {
    $pdo = getDBConnection();
    
    // Handle actions
    if (isset($_POST['toggle_status'])) {
        $stmt = $pdo->prepare("UPDATE users SET is_active = !is_active WHERE id = ?");
        $stmt->execute([$_POST['user_id']]);
        $message = "User status updated.";
    } elseif (isset($_POST['add_user'])) {
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['first_name'], $_POST['last_name'], $_POST['email'], 
            $_POST['phone'], password_hash($_POST['password'], PASSWORD_DEFAULT), 
            $_POST['is_active'] ?? 1
        ]);
        $message = "User added successfully!";
    } elseif (isset($_POST['update_user'])) {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, is_active = ? WHERE id = ?");
        $stmt->execute([
            $_POST['first_name'], $_POST['last_name'], $_POST['email'], 
            $_POST['phone'], $_POST['is_active'], $_POST['user_id']
        ]);
        $message = "User updated successfully!";
    } elseif (isset($_POST['delete_user'])) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_POST['user_id']]);
        $message = "User deleted successfully!";
    }

    // Get users
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();

} catch (PDOException $e) {
    $users = [];
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="flex h-screen overflow-hidden">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="sticky top-0 z-30 flex items-center bg-white/80 dark:bg-background-dark/80 backdrop-blur-md p-4 justify-between border-b border-slate-200 dark:border-slate-800 lg:px-8">
            <h2 class="text-slate-900 dark:text-white text-lg font-bold">User Management</h2>
            <button onclick="document.getElementById('addUserModal').classList.remove('hidden')" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2">
                <span class="material-symbols-outlined">person_add</span>
                Add User
            </button>
        </header>

        <main class="flex-1 overflow-y-auto p-4 lg:p-8 pb-24 admin-content">
            <div class="bg-white dark:bg-surface-dark rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="table-scroll overflow-x-auto">
                    <table class="w-full text-left min-w-[780px]">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50">
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">User</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Contact</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Joined</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <?php if (empty($users)): ?>
                                <!-- Mock users -->
                                <?php 
                                $mock_users = [
                                    ['id' => 1, 'first_name' => 'Marcus', 'last_name' => 'K.', 'email' => 'marcus@example.com', 'phone' => '0917 123 4567', 'created_at' => '2023-01-15', 'is_active' => 1],
                                    ['id' => 2, 'first_name' => 'Sarah', 'last_name' => 'J.', 'email' => 'sarah@example.com', 'phone' => '0918 222 3333', 'created_at' => '2023-02-20', 'is_active' => 1],
                                    ['id' => 3, 'first_name' => 'Bad', 'last_name' => 'Actor', 'email' => 'bad@example.com', 'phone' => '0919 000 0000', 'created_at' => '2023-03-05', 'is_active' => 0],
                                ];
                                foreach($mock_users as $u): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="size-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-bold text-xs">
                                                    <?= substr($u['first_name'], 0, 1) ?>
                                                </div>
                                                <span class="font-bold text-sm"><?= $u['first_name'] . ' ' . $u['last_name'] ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-500">
                                            <?= $u['email'] ?><br><?= $u['phone'] ?>
                                        </td>
                                        <td class="px-6 py-4 text-xs">
                                            <?= date('M d, Y', strtotime($u['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase <?= $u['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                                <?= $u['is_active'] ? 'Active' : 'Banned' ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button class="text-primary text-xs font-bold">Edit</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="size-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-bold text-xs">
                                                    <?= substr($u['first_name'], 0, 1) ?>
                                                </div>
                                                <span class="font-bold text-sm"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-500">
                                            <?= htmlspecialchars($u['email']) ?><br><?= htmlspecialchars($u['phone']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-xs">
                                            <?= date('M d, Y', strtotime($u['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase <?= $u['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                                <?= $u['is_active'] ? 'Active' : 'Banned' ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center gap-2 justify-end">
                                                <button onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)" class="text-primary text-xs font-bold">Edit</button>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <button type="submit" name="toggle_status" class="text-xs font-bold <?= $u['is_active'] ? 'text-rose-500' : 'text-green-500' ?>">
                                                        <?= $u['is_active'] ? 'Ban' : 'Unban' ?>
                                                    </button>
                                                </form>
                                                <form method="POST" onsubmit="return confirm('Delete this user?')" class="inline">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <button type="submit" name="delete_user" class="text-rose-500 text-xs font-bold">Delete</button>
                                                </form>
                                            </div>
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

<?php include 'includes/footer.php'; ?>

<script>
// Edit user modal
function openEditModal(user) {
    const modal = document.getElementById('editUserModal');
    const form = document.getElementById('editUserForm');
    
    // Fill form with user data
    form.user_id.value = user.id;
    form.first_name.value = user.first_name;
    form.last_name.value = user.last_name;
    form.email.value = user.email;
    form.phone.value = user.phone;
    form.is_active.value = user.is_active;
    
    modal.classList.remove('hidden');
}
</script>

<!-- Add User Modal -->
<div id="addUserModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-surface-dark rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
            <h3 class="text-lg font-bold">Add New User</h3>
            <button onclick="document.getElementById('addUserModal').classList.add('hidden')" class="p-2 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-full transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto no-scrollbar">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">First Name</label>
                    <input type="text" name="first_name" required placeholder="John" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Last Name</label>
                    <input type="text" name="last_name" required placeholder="Doe" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Email</label>
                    <input type="email" name="email" required placeholder="john.doe@example.com" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Phone</label>
                    <input type="tel" name="phone" required placeholder="+63 912 345 6789" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Password</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                <select name="is_active" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="flex gap-4 pt-4">
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="flex-1 px-4 py-3 border border-slate-200 dark:border-slate-800 rounded-xl font-bold hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors">
                    Cancel
                </button>
                <button type="submit" name="add_user" class="flex-1 px-4 py-3 bg-primary text-white rounded-xl font-bold shadow-lg shadow-primary/30 hover:bg-primary/90 transition-colors">
                    Add User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-surface-dark rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
            <h3 class="text-lg font-bold">Edit User</h3>
            <button onclick="document.getElementById('editUserModal').classList.add('hidden')" class="p-2 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-full transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" id="editUserForm" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto no-scrollbar">
            <input type="hidden" name="user_id">
            <input type="hidden" name="update_user" value="1">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">First Name</label>
                    <input type="text" name="first_name" required class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Last Name</label>
                    <input type="text" name="last_name" required class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Email</label>
                    <input type="email" name="email" required class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Phone</label>
                    <input type="tel" name="phone" required class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                <select name="is_active" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-primary">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="flex gap-4 pt-4">
                <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')" class="flex-1 px-4 py-3 border border-slate-200 dark:border-slate-800 rounded-xl font-bold hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-3 bg-primary text-white rounded-xl font-bold shadow-lg shadow-primary/30 hover:bg-primary/90 transition-colors">
                    Update User
                </button>
            </div>
        </form>
    </div>
</div>
