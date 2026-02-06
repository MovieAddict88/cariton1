<!DOCTYPE html>
<html class="dark" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Browse Cars - Rental System</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
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
                        "rating-gold": "#FFB800",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    screens: {
                        'xs': '280px',
                        'sm': '640px',
                        'md': '768px',
                        'lg': '1024px',
                        'xl': '1280px',
                        '2xl': '1536px',
                        '3xl': '1920px',
                        '4xl': '2560px',
                    },
                },
            },
        }
    </script>
<style>
        body {
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        body {
            min-height: max(884px, 100dvh);
        }
        
        /* Enhanced responsive design for all devices */
        @media (min-width: 280px) {
            :root {
                font-size: clamp(12px, 3vw, 16px);
            }
        }
        @media (min-width: 640px) {
            :root {
                font-size: clamp(14px, 2vw, 16px);
            }
        }
        @media (min-width: 1024px) {
            :root {
                font-size: clamp(15px, 1.2vw, 18px);
            }
        }
        @media (min-width: 1920px) {
            :root {
                font-size: clamp(16px, 1vw, 20px);
            }
        }
        @media (min-width: 2560px) {
            :root {
                font-size: clamp(18px, 0.8vw, 24px);
            }
        }
        
        .container-responsive {
            max-width: clamp(280px, 100%, 1920px);
            margin: 0 auto;
            padding: clamp(0.5rem, 2vw, 2rem);
        }
        
        .text-responsive {
            font-size: clamp(0.875rem, 2vw, 1rem);
        }
        
        .heading-responsive {
            font-size: clamp(1.25rem, 3vw, 2rem);
        }

        .skeleton {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .5;
            }
        }
    </style>
  </head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-white min-h-screen">
<header class="sticky top-0 z-50 bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800">
<div class="flex items-center justify-between max-w-7xl mx-auto" style="padding: clamp(0.75rem, 2vw, 1.5rem);">
<a href="index.html" class="flex items-center justify-center shrink-0" style="width: clamp(2rem, 5vw, 2.5rem); height: clamp(2rem, 5vw, 2.5rem);">
<span class="material-symbols-outlined" style="font-size: clamp(1.25rem, 3vw, 1.75rem);">arrow_back</span>
</a>
<h2 class="font-bold leading-tight tracking-tight flex-1 text-center" style="font-size: clamp(1rem, 2.5vw, 1.5rem);">Browse Cars</h2>
<div class="flex items-center gap-2 justify-end" style="width: clamp(4rem, 10vw, 6rem);">
<div class="flex items-center justify-center" onclick="openDrawer()">
<span class="material-symbols-outlined cursor-pointer" style="font-size: clamp(1.25rem, 3vw, 1.75rem);">menu</span>
</div>
<a href="profile.php" class="flex items-center justify-center">
<span class="material-symbols-outlined cursor-pointer" style="font-size: clamp(1.25rem, 3vw, 1.75rem);">account_circle</span>
</a>
</div>
</div>
</header>
<main class="max-w-7xl mx-auto pb-24 lg:pb-32 xl:pb-36">
<div class="px-4 py-4">
<div class="flex gap-2">
<label class="flex flex-col flex-1 h-12">
<div class="flex w-full flex-1 items-stretch rounded-xl h-full shadow-sm">
<div class="text-slate-400 flex border-none bg-white dark:bg-card-dark items-center justify-center pl-4 rounded-l-xl">
<span class="material-symbols-outlined">search</span>
</div>
<input id="searchInput" class="form-input flex w-full min-w-0 flex-1 border-none bg-white dark:bg-card-dark focus:ring-0 h-full placeholder:text-slate-400 px-3 rounded-r-xl text-base font-normal" placeholder="Search by brand or model..." value=""/>
</div>
</label>
<button type="button" onclick="openFilters()" class="bg-primary text-white flex items-center justify-center size-12 rounded-xl shrink-0 shadow-lg shadow-primary/20">
<span class="material-symbols-outlined">tune</span>
</button>
</div>
</div>

<!-- Category Filter Tabs -->
<div class="flex gap-3 px-4 pb-4 overflow-x-auto hide-scrollbar" id="categoryTabs">
<div class="flex h-10 shrink-0 items-center justify-center gap-x-2 rounded-full bg-primary px-5 text-white shadow-md shadow-primary/20 cursor-pointer" data-category="all">
<p class="text-sm font-semibold">All</p>
</div>
<div class="flex h-10 shrink-0 items-center justify-center gap-x-2 rounded-full bg-white dark:bg-card-dark border border-slate-200 dark:border-slate-800 px-5 text-slate-600 dark:text-slate-300 cursor-pointer" data-category="suv">
<p class="text-sm font-medium">SUV</p>
</div>
<div class="flex h-10 shrink-0 items-center justify-center gap-x-2 rounded-full bg-white dark:bg-card-dark border border-slate-200 dark:border-slate-800 px-5 text-slate-600 dark:text-slate-300 cursor-pointer" data-category="sedan">
<p class="text-sm font-medium">Sedan</p>
</div>
<div class="flex h-10 shrink-0 items-center justify-center gap-x-2 rounded-full bg-white dark:bg-card-dark border border-slate-200 dark:border-slate-800 px-5 text-slate-600 dark:text-slate-300 cursor-pointer" data-category="luxury">
<p class="text-sm font-medium">Luxury</p>
</div>
<div class="flex h-10 shrink-0 items-center justify-center gap-x-2 rounded-full bg-white dark:bg-card-dark border border-slate-200 dark:border-slate-800 px-5 text-slate-600 dark:text-slate-300 cursor-pointer" data-category="electric">
<p class="text-sm font-medium">Electric</p>
</div>
</div>

<div class="flex items-center justify-between px-4 pt-2">
<h3 class="font-bold tracking-tight" style="font-size: clamp(1.125rem, 2.5vw, 1.5rem);">Available Cars</h3>
<span id="carCount" class="text-primary font-semibold" style="font-size: clamp(0.75rem, 1.5vw, 0.875rem);">Loading...</span>
</div>

<!-- Loading State -->
<div id="loadingState" class="grid grid-cols-1 xs:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4 p-4">
<div class="flex flex-col bg-white dark:bg-card-dark rounded-xl overflow-hidden shadow-sm border border-slate-100 dark:border-slate-800/50">
<div class="w-full aspect-[4/3] bg-slate-200 dark:bg-slate-800 skeleton"></div>
<div class="p-3 space-y-2">
<div class="h-5 bg-slate-200 dark:bg-slate-800 rounded skeleton"></div>
<div class="h-4 bg-slate-200 dark:bg-slate-800 rounded w-3/4 skeleton"></div>
<div class="h-8 bg-slate-200 dark:bg-slate-800 rounded skeleton"></div>
</div>
</div>
<div class="flex flex-col bg-white dark:bg-card-dark rounded-xl overflow-hidden shadow-sm border border-slate-100 dark:border-slate-800/50">
<div class="w-full aspect-[4/3] bg-slate-200 dark:bg-slate-800 skeleton"></div>
<div class="p-3 space-y-2">
<div class="h-5 bg-slate-200 dark:bg-slate-800 rounded skeleton"></div>
<div class="h-4 bg-slate-200 dark:bg-slate-800 rounded w-3/4 skeleton"></div>
<div class="h-8 bg-slate-200 dark:bg-slate-800 rounded skeleton"></div>
</div>
</div>
<div class="flex flex-col bg-white dark:bg-card-dark rounded-xl overflow-hidden shadow-sm border border-slate-100 dark:border-slate-800/50">
<div class="w-full aspect-[4/3] bg-slate-200 dark:bg-slate-800 skeleton"></div>
<div class="p-3 space-y-2">
<div class="h-5 bg-slate-200 dark:bg-slate-800 rounded skeleton"></div>
<div class="h-4 bg-slate-200 dark:bg-slate-800 rounded w-3/4 skeleton"></div>
<div class="h-8 bg-slate-200 dark:bg-slate-800 rounded skeleton"></div>
</div>
</div>
</div>

<!-- Cars Grid -->
<div id="carsGrid" class="grid grid-cols-1 xs:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4 p-4" style="display: none;">
<!-- Cars will be dynamically loaded here -->
</div>

<!-- Error State -->
<div id="errorState" class="p-8 text-center" style="display: none;">
<span class="material-symbols-outlined text-6xl text-slate-400 mb-4">error_outline</span>
<p class="text-slate-500 dark:text-slate-400 mb-4">Unable to load vehicles. Please try again later.</p>
<button onclick="loadVehicles()" class="px-6 py-2 bg-primary text-white rounded-lg font-semibold">Retry</button>
</div>

<!-- Empty State -->
<div id="emptyState" class="p-8 text-center" style="display: none;">
<span class="material-symbols-outlined text-6xl text-slate-400 mb-4">directions_car</span>
<p class="text-slate-500 dark:text-slate-400">No vehicles found matching your criteria.</p>
</div>
</main>

<nav class="fixed bottom-0 left-0 right-0 z-50 bg-background-light/95 dark:bg-background-dark/95 backdrop-blur-xl border-t border-slate-200 dark:border-slate-800 pb-safe">
<div class="flex justify-around items-center h-20 max-w-7xl mx-auto px-6">
<a href="index.html" class="flex flex-col items-center justify-center text-slate-400">
<span class="material-symbols-outlined">home</span>
<span class="text-[10px] mt-1 font-medium">Home</span>
</a>
<a href="browse_cars.php" class="flex flex-col items-center justify-center text-primary">
<span class="material-symbols-outlined fill-1">explore</span>
<span class="text-[10px] mt-1 font-bold">Browse</span>
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

<!-- Filters Bottom Sheet -->
<div id="filters-overlay" class="fixed inset-0 bg-black/60 z-[80] hidden transition-opacity duration-300 opacity-0"></div>
<div id="filters-sheet" class="fixed bottom-0 left-0 right-0 z-[90] bg-background-light dark:bg-[#111a22] rounded-t-[2.5rem] shadow-[0_-10px_40px_rgba(0,0,0,0.5)] transform translate-y-full transition-transform duration-300 ease-in-out max-h-[90%] flex flex-col">
    <div class="flex flex-col items-center pt-3 pb-1 shrink-0">
        <div class="h-1.5 w-10 rounded-full bg-slate-300 dark:bg-slate-700"></div>
    </div>
    <div class="flex items-center p-4 pt-2 justify-between border-b border-slate-100 dark:border-slate-800 shrink-0">
        <h2 class="text-xl font-bold">Filters</h2>
        <button onclick="resetFilters()" class="text-primary font-semibold">Reset</button>
    </div>
    <div class="flex-1 overflow-y-auto p-4 space-y-6 pb-32">
        <!-- Price Range -->
        <div>
            <h3 class="text-lg font-bold mb-4">Price Range</h3>
            <div class="space-y-4">
                <div class="flex justify-between">
                    <span class="text-sm text-slate-500">Min Price (₱)</span>
                    <span id="minPriceDisplay" class="text-sm font-bold text-primary">0</span>
                </div>
                <input type="range" id="minPriceInput" min="0" max="50000" step="500" value="0" class="w-full h-2 bg-slate-200 dark:bg-slate-800 rounded-lg appearance-none cursor-pointer accent-primary">

                <div class="flex justify-between">
                    <span class="text-sm text-slate-500">Max Price (₱)</span>
                    <span id="maxPriceDisplay" class="text-sm font-bold text-primary">50000+</span>
                </div>
                <input type="range" id="maxPriceInput" min="0" max="50000" step="500" value="50000" class="w-full h-2 bg-slate-200 dark:bg-slate-800 rounded-lg appearance-none cursor-pointer accent-primary">
            </div>
        </div>
        <hr class="border-slate-100 dark:border-slate-800">
        <!-- Transmission -->
        <div>
            <h3 class="text-lg font-bold mb-4">Transmission</h3>
            <div class="flex gap-4">
                <label class="flex-1">
                    <input type="radio" name="transmission" value="all" checked class="hidden peer">
                    <div class="text-center py-3 rounded-xl border border-slate-200 dark:border-slate-800 peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary transition-all cursor-pointer font-medium">All</div>
                </label>
                <label class="flex-1">
                    <input type="radio" name="transmission" value="automatic" class="hidden peer">
                    <div class="text-center py-3 rounded-xl border border-slate-200 dark:border-slate-800 peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary transition-all cursor-pointer font-medium">Auto</div>
                </label>
                <label class="flex-1">
                    <input type="radio" name="transmission" value="manual" class="hidden peer">
                    <div class="text-center py-3 rounded-xl border border-slate-200 dark:border-slate-800 peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary transition-all cursor-pointer font-medium">Manual</div>
                </label>
            </div>
        </div>
        <hr class="border-slate-100 dark:border-slate-800">
        <!-- Additional Filters -->
        <div>
            <h3 class="text-lg font-bold mb-4">Additional Filters</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span>Top Rated Only</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="featuredFilter" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                </div>
            </div>
        </div>
    </div>
    <div class="p-6 border-t border-slate-100 dark:border-slate-800 shrink-0">
        <button onclick="closeFilters()" class="w-full bg-primary text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/20">Apply Filters</button>
    </div>
</div>

<!-- Navigation Drawer -->
<div id="drawer-overlay" class="fixed inset-0 bg-black/50 z-[60] hidden transition-opacity duration-300 opacity-0"></div>
<div id="navigation-drawer" class="fixed top-0 left-0 bottom-0 w-[280px] bg-background-light dark:bg-background-dark z-[70] transform -translate-x-full transition-transform duration-300 ease-in-out border-r border-slate-200 dark:border-slate-800">
    <div class="flex flex-col h-full">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-2xl font-bold text-primary">VeloDrive</h2>
            <p class="text-xs text-slate-500 mt-1">Car Rental Services</p>
        </div>
        <div class="flex-1 overflow-y-auto py-4">
            <nav class="space-y-1 px-3">
                <a href="index.html" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-slate-500">home</span>
                    <span class="font-semibold">Home</span>
                </a>
                <a href="browse_cars.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-slate-500">explore</span>
                    <span class="font-semibold">Browse Cars</span>
                </a>
                <a href="my_bookings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-slate-500">calendar_today</span>
                    <span class="font-semibold">My Bookings</span>
                </a>
                <a href="favorites.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-slate-500">favorite</span>
                    <span class="font-semibold">Favorites</span>
                </a>
                <hr class="my-4 border-slate-200 dark:border-slate-800 mx-4">
                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-slate-500">person</span>
                    <span class="font-semibold">My Profile</span>
                </a>
            </nav>
        </div>
        <div class="p-6 border-t border-slate-200 dark:border-slate-800">
            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors">
                <span class="material-symbols-outlined">logout</span>
                <span class="font-semibold">Logout</span>
            </a>
        </div>
    </div>
</div>

<script>
function openDrawer() {
    const overlay = document.getElementById('drawer-overlay');
    const drawer = document.getElementById('navigation-drawer');
    overlay.classList.remove('hidden');
    setTimeout(() => {
        overlay.classList.add('opacity-100');
        drawer.classList.remove('-translate-x-full');
    }, 10);
}

function closeDrawer() {
    const overlay = document.getElementById('drawer-overlay');
    const drawer = document.getElementById('navigation-drawer');
    overlay.classList.remove('opacity-100');
    drawer.classList.add('-translate-x-full');
    setTimeout(() => {
        overlay.classList.add('hidden');
    }, 300);
}

document.getElementById('drawer-overlay').addEventListener('click', closeDrawer);

let allVehicles = [];
let filteredVehicles = [];
let currentCategory = 'all';
let searchQuery = '';
let minPrice = 0;
let maxPrice = 50000;
let selectedTransmission = 'all';
let featuredOnly = false;

function openFilters() {
    const overlay = document.getElementById('filters-overlay');
    const sheet = document.getElementById('filters-sheet');
    overlay.classList.remove('hidden');
    setTimeout(() => {
        overlay.classList.add('opacity-100');
        sheet.classList.remove('translate-y-full');
    }, 10);
}

function closeFilters() {
    const overlay = document.getElementById('filters-overlay');
    const sheet = document.getElementById('filters-sheet');
    overlay.classList.remove('opacity-100');
    sheet.classList.add('translate-y-full');
    setTimeout(() => {
        overlay.classList.add('hidden');
    }, 300);
    applyFilters();
}

function resetFilters() {
    document.getElementById('minPriceInput').value = 0;
    document.getElementById('maxPriceInput').value = 50000;
    document.getElementById('minPriceDisplay').textContent = '0';
    document.getElementById('maxPriceDisplay').textContent = '50000+';
    document.querySelector('input[name="transmission"][value="all"]').checked = true;
    document.getElementById('featuredFilter').checked = false;

    minPrice = 0;
    maxPrice = 50000;
    selectedTransmission = 'all';
    featuredOnly = false;

    // Clear search and category too for a full reset
    document.getElementById('searchInput').value = '';
    searchQuery = '';
    filterByCategory('all');

    applyFilters();
}

document.getElementById('filters-overlay').addEventListener('click', closeFilters);

document.getElementById('minPriceInput').addEventListener('input', (e) => {
    minPrice = parseInt(e.target.value);
    document.getElementById('minPriceDisplay').textContent = minPrice;
});

document.getElementById('maxPriceInput').addEventListener('input', (e) => {
    maxPrice = parseInt(e.target.value);
    document.getElementById('maxPriceDisplay').textContent = maxPrice >= 50000 ? '50000+' : maxPrice;
});

document.querySelectorAll('input[name="transmission"]').forEach(radio => {
    radio.addEventListener('change', (e) => {
        selectedTransmission = e.target.value;
    });
});

document.getElementById('featuredFilter').addEventListener('change', (e) => {
    featuredOnly = e.target.checked;
});

async function toggleFavorite(vehicleId, button) {
    try {
        const response = await fetch('../api/favorites.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ vehicle_id: vehicleId })
        });

        if (response.status === 401) {
            window.location.href = 'user_authentication.html';
            return;
        }

        const data = await response.json();
        if (data.success) {
            const icon = button.querySelector('.material-symbols-outlined');
            if (data.is_favorited) {
                button.classList.remove('text-white');
                button.classList.add('text-rose-500');
                icon.classList.add('fill-1');
            } else {
                button.classList.remove('text-rose-500');
                button.classList.add('text-white');
                icon.classList.remove('fill-1');
            }

            // Update allVehicles state so it stays if we re-render
            const vehicle = allVehicles.find(v => v.id == vehicleId);
            if (vehicle) vehicle.is_favorited = data.is_favorited;
        }
    } catch (error) {
        console.error('Error toggling favorite:', error);
    }
}

// Load vehicles from API
async function loadVehicles() {
    try {
        document.getElementById('loadingState').style.display = 'grid';
        document.getElementById('carsGrid').style.display = 'none';
        document.getElementById('errorState').style.display = 'none';
        document.getElementById('emptyState').style.display = 'none';
        
        const response = await fetch('../api/vehicles.php?status=available&limit=100');
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();
        
        if (data.success && data.data) {
            allVehicles = data.data;
            filteredVehicles = allVehicles;
            renderVehicles();
        } else {
            throw new Error(data.error || 'Failed to load vehicles');
        }
    } catch (error) {
        console.error('Error loading vehicles:', error);
        showError();
    }
}

// Render vehicles to the grid
function renderVehicles() {
    const grid = document.getElementById('carsGrid');
    const loadingState = document.getElementById('loadingState');
    const emptyState = document.getElementById('emptyState');
    const errorState = document.getElementById('errorState');
    const carCount = document.getElementById('carCount');
    
    loadingState.style.display = 'none';
    errorState.style.display = 'none';
    
    if (filteredVehicles.length === 0) {
        grid.style.display = 'none';
        emptyState.style.display = 'block';
        carCount.textContent = '0 cars';
        return;
    }
    
    emptyState.style.display = 'none';
    grid.style.display = 'grid';
    carCount.textContent = `${filteredVehicles.length} ${filteredVehicles.length === 1 ? 'car' : 'cars'}`;
    
    grid.innerHTML = filteredVehicles.map(vehicle => {
        const imageUrl = vehicle.display_image || 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&q=80&w=400';
        const vehicleName = `${vehicle.make} ${vehicle.model}`;
        const isFeatured = vehicle.is_featured;
        
        return `
            <div class="flex flex-col bg-white dark:bg-card-dark rounded-xl overflow-hidden shadow-sm border border-slate-100 dark:border-slate-800/50 hover:shadow-md transition-shadow">
                <a href="vehicle_details.php?id=${vehicle.id}" class="relative w-full aspect-[4/3] bg-slate-200 dark:bg-slate-800 bg-center bg-no-repeat bg-cover block" style='background-image: url("${imageUrl}");'>
                    ${isFeatured ? `
                    <div class="absolute top-2 left-2 flex items-center gap-1 bg-white/90 dark:bg-black/80 backdrop-blur-sm px-2 py-1 rounded-full border border-rating-gold/30 shadow-sm">
                        <span class="material-symbols-outlined text-[14px] text-rating-gold fill-1">star</span>
                        <span class="text-[10px] font-bold text-slate-800 dark:text-white uppercase tracking-tight">Top Rated</span>
                    </div>
                    ` : ''}
                    <button type="button" class="absolute top-2 right-2 size-8 bg-black/20 backdrop-blur-md rounded-full flex items-center justify-center ${vehicle.is_favorited ? 'text-rose-500' : 'text-white'} hover:bg-black/30 transition-colors" onclick="event.preventDefault(); toggleFavorite(${vehicle.id}, this)">
                        <span class="material-symbols-outlined text-lg ${vehicle.is_favorited ? 'fill-1' : ''}">favorite</span>
                    </button>
                </a>
                <div class="p-3">
                    <a href="vehicle_details.php?id=${vehicle.id}" class="text-slate-900 dark:text-white text-base font-bold leading-tight mb-1 hover:text-primary transition-colors block" title="${vehicleName}">${vehicleName}</a>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">${vehicle.year} • ${vehicle.fuel_type} • ${vehicle.seats} seats</p>
                    <p class="text-primary text-sm font-bold mb-2">₱${parseFloat(vehicle.daily_rate).toFixed(2)}<span class="text-slate-500 dark:text-slate-400 font-normal">/day</span></p>
                    <a href="checkout_summary.html?vehicle=${vehicle.id}" class="block w-full text-center py-2 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-primary/90 transition-colors">Book Now</a>
                </div>
            </div>
        `;
    }).join('');
}

// Filter vehicles by category
function filterByCategory(category) {
    currentCategory = category;
    applyFilters();
    
    // Update active tab
    document.querySelectorAll('#categoryTabs > div').forEach(tab => {
        const tabCategory = tab.getAttribute('data-category');
        if (tabCategory === category) {
            tab.className = 'flex h-10 shrink-0 items-center justify-center gap-x-2 rounded-full bg-primary px-5 text-white shadow-md shadow-primary/20 cursor-pointer';
            tab.querySelector('p').className = 'text-sm font-semibold';
        } else {
            tab.className = 'flex h-10 shrink-0 items-center justify-center gap-x-2 rounded-full bg-white dark:bg-card-dark border border-slate-200 dark:border-slate-800 px-5 text-slate-600 dark:text-slate-300 cursor-pointer';
            tab.querySelector('p').className = 'text-sm font-medium';
        }
    });
}

// Apply all filters
function applyFilters() {
    filteredVehicles = allVehicles.filter(vehicle => {
        // Category filter
        if (currentCategory !== 'all' && vehicle.vehicle_type.toLowerCase() !== currentCategory.toLowerCase()) {
            return false;
        }
        
        // Search filter
        if (searchQuery) {
            const searchLower = searchQuery.toLowerCase();
            const vehicleName = `${vehicle.make} ${vehicle.model}`.toLowerCase();
            const description = (vehicle.description || '').toLowerCase();
            
            if (!vehicleName.includes(searchLower) && !description.includes(searchLower)) {
                return false;
            }
        }

        // Price filter
        const price = parseFloat(vehicle.daily_rate);
        if (price < minPrice) return false;
        // If maxPrice is at 50000, consider it "No Limit"
        if (maxPrice < 50000 && price > maxPrice) {
            return false;
        }

        // Transmission filter
        if (selectedTransmission !== 'all' && vehicle.transmission.toLowerCase() !== selectedTransmission.toLowerCase()) {
            return false;
        }

        // Featured filter
        if (featuredOnly && !vehicle.is_featured) {
            return false;
        }
        
        return true;
    });
    
    renderVehicles();
}

// Show error state
function showError() {
    document.getElementById('loadingState').style.display = 'none';
    document.getElementById('carsGrid').style.display = 'none';
    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('errorState').style.display = 'block';
    document.getElementById('carCount').textContent = 'Error';
}

// Event listeners
document.addEventListener('DOMContentLoaded', () => {
    loadVehicles();
    
    // Category tabs
    document.querySelectorAll('#categoryTabs > div').forEach(tab => {
        tab.addEventListener('click', () => {
            const category = tab.getAttribute('data-category');
            filterByCategory(category);
        });
    });
    
    // Search input
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchQuery = e.target.value;
            applyFilters();
        }, 300);
    });
});
</script>

</body></html>
