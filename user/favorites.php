<?php
require_once '../admin/includes/config.php';
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: user_authentication.html');
    exit;
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>My Favorites - VeloDrive</title>
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
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-white min-h-screen pb-24">
    <header class="sticky top-0 z-50 bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between max-w-7xl mx-auto p-4">
            <a href="index.html" class="flex items-center justify-center size-10 rounded-full hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <h2 class="font-bold text-lg flex-1 text-center">My Favorites</h2>
            <div class="size-10"></div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-4">
        <div id="favoritesGrid" class="grid grid-cols-1 xs:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4">
            <!-- Favorites will be loaded here -->
            <div class="col-span-full py-20 text-center animate-pulse">
                <p class="text-slate-500">Loading your favorites...</p>
            </div>
        </div>

        <div id="emptyState" class="hidden py-20 text-center">
            <span class="material-symbols-outlined text-6xl text-slate-300 mb-4">favorite</span>
            <h3 class="text-xl font-bold mb-2">No favorites yet</h3>
            <p class="text-slate-500 mb-6">Explore our cars and heart the ones you love!</p>
            <a href="browse_cars.php" class="inline-block px-8 py-3 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/20 transition-all active:scale-95">Browse Cars</a>
        </div>
    </main>

    <nav class="fixed bottom-0 left-0 right-0 z-50 bg-background-light/95 dark:bg-background-dark/95 backdrop-blur-xl border-t border-slate-200 dark:border-slate-800 pb-safe">
        <div class="flex justify-around items-center h-20 max-w-7xl mx-auto px-6">
            <a href="index.html" class="flex flex-col items-center justify-center text-slate-400">
                <span class="material-symbols-outlined">home</span>
                <span class="text-[10px] mt-1 font-medium">Home</span>
            </a>
            <a href="browse_cars.php" class="flex flex-col items-center justify-center text-slate-400">
                <span class="material-symbols-outlined">explore</span>
                <span class="text-[10px] mt-1 font-medium">Browse</span>
            </a>
            <a href="my_bookings.php" class="flex flex-col items-center justify-center text-slate-400">
                <span class="material-symbols-outlined">calendar_today</span>
                <span class="text-[10px] mt-1 font-medium">Bookings</span>
            </a>
            <a href="profile.php" class="flex flex-col items-center justify-center text-slate-400">
                <span class="material-symbols-outlined">person</span>
                <span class="text-[10px] mt-1 font-medium">Profile</span>
            </a>
        </div>
    </nav>

    <script>
    async function loadFavorites() {
        const grid = document.getElementById('favoritesGrid');
        const emptyState = document.getElementById('emptyState');

        try {
            const response = await fetch('../api/favorites.php');
            const data = await response.json();

            if (data.success && data.data) {
                if (data.data.length === 0) {
                    grid.classList.add('hidden');
                    emptyState.classList.remove('hidden');
                } else {
                    grid.classList.remove('hidden');
                    emptyState.classList.add('hidden');

                    grid.innerHTML = data.data.map(vehicle => `
                        <div class="flex flex-col bg-white dark:bg-card-dark rounded-xl overflow-hidden shadow-sm border border-slate-100 dark:border-slate-800/50 hover:shadow-md transition-shadow">
                            <a href="vehicle_details.php?id=${vehicle.id}" class="relative w-full aspect-[4/3] bg-slate-200 dark:bg-slate-800 bg-center bg-no-repeat bg-cover block" style='background-image: url("${vehicle.display_image}");'>
                                <button type="button" class="absolute top-2 right-2 size-8 bg-black/20 backdrop-blur-md rounded-full flex items-center justify-center text-primary hover:bg-black/30 transition-colors" onclick="event.preventDefault(); toggleFavorite(${vehicle.id}, this)">
                                    <span class="material-symbols-outlined text-lg fill-1">favorite</span>
                                </button>
                            </a>
                            <div class="p-3">
                                <a href="vehicle_details.php?id=${vehicle.id}" class="text-slate-900 dark:text-white text-base font-bold leading-tight mb-1 hover:text-primary transition-colors block">${vehicle.make} ${vehicle.model}</a>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">${vehicle.year} • ${vehicle.fuel_type}</p>
                                <p class="text-primary text-sm font-bold mb-2">₱${parseFloat(vehicle.daily_rate).toFixed(2)}<span class="text-slate-500 dark:text-slate-400 font-normal">/day</span></p>
                                <a href="checkout_summary.html?vehicle=${vehicle.id}" class="block w-full text-center py-2 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-primary/90 transition-colors">Book Now</a>
                            </div>
                        </div>
                    `).join('');
                }
            }
        } catch (error) {
            console.error('Error loading favorites:', error);
            grid.innerHTML = '<p class="col-span-full text-center text-red-500">Failed to load favorites.</p>';
        }
    }

    async function toggleFavorite(vehicleId, button) {
        try {
            const response = await fetch('../api/favorites.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ vehicle_id: vehicleId })
            });
            const data = await response.json();
            if (data.success) {
                // Since we are on the favorites page, if we remove it, we should probably just reload or remove the card
                loadFavorites();
            }
        } catch (error) {
            console.error('Error toggling favorite:', error);
        }
    }

    document.addEventListener('DOMContentLoaded', loadFavorites);
    </script>
</body>
</html>
