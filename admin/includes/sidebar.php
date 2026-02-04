<?php
// admin/includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="fixed top-0 left-0 h-full w-72 bg-white dark:bg-surface-dark z-50 transform -translate-x-full transition-transform duration-300 ease-in-out border-r border-slate-200 dark:border-slate-800 lg:translate-x-0 lg:static lg:block" id="sidebar">
    <div class="flex items-center justify-between p-6">
        <div class="flex items-center gap-3">
            <div class="bg-primary size-10 rounded-lg flex items-center justify-center">
                <span class="material-symbols-outlined text-white">directions_car</span>
            </div>
            <span class="font-bold text-xl tracking-tight">DriveAdmin</span>
        </div>
        <button class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full lg:hidden" onclick="toggleSidebar()">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    <nav class="mt-4 px-4 space-y-2">
        <a class="flex items-center gap-4 p-3 <?= $current_page == 'index.php' ? 'bg-primary/10 text-primary font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?> rounded-lg transition-colors" href="index.php">
            <span class="material-symbols-outlined <?= $current_page == 'index.php' ? 'fill-1' : '' ?>">dashboard</span>
            Overview
        </a>
        <a class="flex items-center gap-4 p-3 <?= $current_page == 'fleet.php' ? 'bg-primary/10 text-primary font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?> rounded-lg transition-colors" href="fleet.php">
            <span class="material-symbols-outlined <?= $current_page == 'fleet.php' ? 'fill-1' : '' ?>">garage</span>
            Fleet Management
        </a>
        <a class="flex items-center gap-4 p-3 <?= $current_page == 'drivers.php' ? 'bg-primary/10 text-primary font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?> rounded-lg transition-colors" href="drivers.php">
            <span class="material-symbols-outlined <?= $current_page == 'drivers.php' ? 'fill-1' : '' ?>">group</span>
            Drivers
        </a>
        <a class="flex items-center gap-4 p-3 <?= $current_page == 'bookings.php' ? 'bg-primary/10 text-primary font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?> rounded-lg transition-colors" href="bookings.php">
            <span class="material-symbols-outlined <?= $current_page == 'bookings.php' ? 'fill-1' : '' ?>">book_online</span>
            Bookings
        </a>
        <a class="flex items-center gap-4 p-3 <?= $current_page == 'payments.php' ? 'bg-primary/10 text-primary font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?> rounded-lg transition-colors" href="payments.php">
            <span class="material-symbols-outlined <?= $current_page == 'payments.php' ? 'fill-1' : '' ?>">payments</span>
            Payments
        </a>
        <a class="flex items-center gap-4 p-3 <?= $current_page == 'reviews.php' ? 'bg-primary/10 text-primary font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?> rounded-lg transition-colors" href="reviews.php">
            <span class="material-symbols-outlined <?= $current_page == 'reviews.php' ? 'fill-1' : '' ?>">reviews</span>
            Reviews
        </a>
        <a class="flex items-center gap-4 p-3 <?= $current_page == 'users.php' ? 'bg-primary/10 text-primary font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?> rounded-lg transition-colors" href="users.php">
            <span class="material-symbols-outlined <?= $current_page == 'users.php' ? 'fill-1' : '' ?>">people</span>
            Users
        </a>
        <a class="flex items-center gap-4 p-3 <?= $current_page == 'reports.php' ? 'bg-primary/10 text-primary font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?> rounded-lg transition-colors" href="reports.php">
            <span class="material-symbols-outlined <?= $current_page == 'reports.php' ? 'fill-1' : '' ?>">analytics</span>
            Reports
        </a>
        <div class="pt-6 mt-6 border-t border-slate-200 dark:border-slate-800">
            <a class="flex items-center gap-4 p-3 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors" href="settings.php">
                <span class="material-symbols-outlined">settings</span>
                Settings
            </a>
            <a class="flex items-center gap-4 p-3 text-red-500 hover:bg-red-500/10 rounded-lg transition-colors" href="logout.php">
                <span class="material-symbols-outlined">logout</span>
                Logout
            </a>
        </div>
    </nav>
</div>
