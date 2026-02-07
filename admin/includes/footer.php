<?php
// admin/includes/footer.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
    <!-- Bottom Navigation Bar (iOS Style) - Only visible on mobile -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white/90 dark:bg-background-dark/90 backdrop-blur-lg border-t border-slate-200 dark:border-slate-800 px-6 pt-3 pb-8 flex justify-between items-center z-40 lg:hidden">
        <a class="flex flex-col items-center gap-1 <?= $current_page == 'index.php' ? 'text-primary' : 'text-slate-400' ?>" href="index.php">
            <span class="material-symbols-outlined <?= $current_page == 'index.php' ? 'fill-1' : '' ?>">home</span>
            <span class="text-[10px] font-bold uppercase tracking-widest">Home</span>
        </a>
        <a class="flex flex-col items-center gap-1 <?= $current_page == 'fleet.php' ? 'text-primary' : 'text-slate-400' ?>" href="fleet.php">
            <span class="material-symbols-outlined <?= $current_page == 'fleet.php' ? 'fill-1' : '' ?>">garage</span>
            <span class="text-[10px] font-bold uppercase tracking-widest">Fleet</span>
        </a>
        <?php if ($current_page === 'drivers.php'): ?>
            <button onclick="document.getElementById('addDriverModal').classList.remove('hidden')" class="-mt-12 bg-primary size-14 rounded-full flex items-center justify-center shadow-lg shadow-primary/40 border-4 border-background-light dark:border-background-dark active:scale-90 transition-transform focus:outline-none">
                <span class="material-symbols-outlined text-white text-3xl">add</span>
            </button>
        <?php elseif ($current_page === 'fleet.php'): ?>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="-mt-12 bg-primary size-14 rounded-full flex items-center justify-center shadow-lg shadow-primary/40 border-4 border-background-light dark:border-background-dark active:scale-90 transition-transform focus:outline-none">
                <span class="material-symbols-outlined text-white text-3xl">add</span>
            </button>
        <?php else: ?>
            <a href="fleet.php" class="-mt-12 bg-primary size-14 rounded-full flex items-center justify-center shadow-lg shadow-primary/40 border-4 border-background-light dark:border-background-dark active:scale-90 transition-transform">
                <span class="material-symbols-outlined text-white text-3xl">add</span>
            </a>
        <?php endif; ?>
        <a class="flex flex-col items-center gap-1 <?= $current_page == 'payments.php' ? 'text-primary' : 'text-slate-400' ?>" href="payments.php">
            <span class="material-symbols-outlined <?= $current_page == 'payments.php' ? 'fill-1' : '' ?>">payments</span>
            <span class="text-[10px] font-bold uppercase tracking-widest">Pay</span>
        </a>
        <a class="flex flex-col items-center gap-1 <?= $current_page == 'drivers.php' ? 'text-primary' : 'text-slate-400' ?>" href="drivers.php">
            <span class="material-symbols-outlined <?= $current_page == 'drivers.php' ? 'fill-1' : '' ?>">group</span>
            <span class="text-[10px] font-bold uppercase tracking-widest">Drivers</span>
        </a>
    </nav>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('drawer-overlay');
            const isHidden = sidebar.classList.contains('-translate-x-full');

            if (isHidden) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }

        document.getElementById('drawer-overlay')?.addEventListener('click', toggleSidebar);

        function toggleDarkMode() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
        }
    </script>
</body>
</html>
