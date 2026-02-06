<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Vehicle Details - VeloDrive</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet"/>
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
                        "rating-gold": "#FFB800",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    }
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-white min-h-screen pb-24">
    <header class="sticky top-0 z-50 bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between max-w-7xl mx-auto p-4">
            <a href="javascript:history.back()" class="flex items-center justify-center size-10 rounded-full hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <h2 class="font-bold text-lg flex-1 text-center">Vehicle Details</h2>
            <div class="size-10"></div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto p-4">
        <!-- Loading State -->
        <div id="loadingState" class="py-20 text-center">
            <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-primary border-r-transparent"></div>
            <p class="mt-4 text-slate-500">Loading vehicle details...</p>
        </div>

        <!-- Error State -->
        <div id="errorState" class="py-20 text-center hidden">
            <span class="material-symbols-outlined text-6xl text-slate-400 mb-4">error_outline</span>
            <p class="text-slate-500 mb-4">Vehicle not found or an error occurred.</p>
            <a href="browse_cars.php" class="px-6 py-2 bg-primary text-white rounded-lg font-semibold">Back to Browse</a>
        </div>

        <!-- Vehicle Content -->
        <div id="vehicleContent" class="hidden">
            <!-- Image Gallery -->
            <div class="relative rounded-2xl overflow-hidden aspect-video bg-slate-200 dark:bg-slate-800 mb-6 shadow-lg">
                <img id="mainImage" src="" alt="Vehicle Image" class="w-full h-full object-cover">
                <div id="featuredBadge" class="absolute top-4 left-4 hidden bg-white/90 dark:bg-black/80 backdrop-blur-sm px-3 py-1.5 rounded-full border border-rating-gold/30 shadow-sm flex items-center gap-1">
                    <span class="material-symbols-outlined text-[18px] text-rating-gold fill-1">star</span>
                    <span class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-tight">Top Rated</span>
                </div>
            </div>

            <!-- Thumbnail List (if multiple images) -->
            <div id="thumbnailList" class="flex gap-2 mb-6 overflow-x-auto pb-2 hide-scrollbar"></div>

            <div class="bg-white dark:bg-card-dark rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-800/50">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <div>
                        <p id="vehicleType" class="text-primary text-sm font-bold uppercase tracking-widest mb-1"></p>
                        <h1 id="vehicleName" class="text-3xl font-black tracking-tight"></h1>
                        <p id="vehicleYear" class="text-slate-500 dark:text-slate-400"></p>
                    </div>
                    <div class="text-left md:text-right">
                        <p class="text-slate-500 text-sm">Daily Rate</p>
                        <p class="text-3xl font-black text-primary"><span id="vehiclePrice"></span><span class="text-sm font-normal text-slate-500">/day</span></p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                    <div class="flex flex-col items-center justify-center p-4 rounded-xl bg-slate-50 dark:bg-background-dark border border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary mb-2">settings_input_component</span>
                        <span class="text-xs text-slate-500 uppercase font-bold">Transmission</span>
                        <span id="vehicleTransmission" class="text-sm font-bold mt-1"></span>
                    </div>
                    <div class="flex flex-col items-center justify-center p-4 rounded-xl bg-slate-50 dark:bg-background-dark border border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary mb-2">local_gas_station</span>
                        <span class="text-xs text-slate-500 uppercase font-bold">Fuel Type</span>
                        <span id="vehicleFuel" class="text-sm font-bold mt-1"></span>
                    </div>
                    <div class="flex flex-col items-center justify-center p-4 rounded-xl bg-slate-50 dark:bg-background-dark border border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary mb-2">group</span>
                        <span class="text-xs text-slate-500 uppercase font-bold">Seats</span>
                        <span id="vehicleSeats" class="text-sm font-bold mt-1"></span>
                    </div>
                    <div class="flex flex-col items-center justify-center p-4 rounded-xl bg-slate-50 dark:bg-background-dark border border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary mb-2">work</span>
                        <span class="text-xs text-slate-500 uppercase font-bold">Luggage</span>
                        <span id="vehicleLuggage" class="text-sm font-bold mt-1"></span>
                    </div>
                </div>

                <div class="mb-8">
                    <h3 class="text-lg font-bold mb-3">Description</h3>
                    <p id="vehicleDescription" class="text-slate-600 dark:text-slate-300 leading-relaxed"></p>
                </div>

                <div id="featuresSection" class="mb-8 hidden">
                    <h3 class="text-lg font-bold mb-3">Key Features</h3>
                    <div id="featuresList" class="grid grid-cols-1 sm:grid-cols-2 gap-2"></div>
                </div>

                <a id="bookNowBtn" href="#" class="block w-full text-center py-4 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/25 hover:bg-primary/90 transition-all active:scale-[0.98]">
                    Book This Car Now
                </a>
            </div>
        </div>
    </main>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const vehicleId = urlParams.get('id');

        async function loadVehicleDetails() {
            if (!vehicleId) {
                showError();
                return;
            }

            try {
                const response = await fetch(`../api/vehicles.php?id=${vehicleId}`);
                const data = await response.json();

                if (data.success && data.data && data.data.length > 0) {
                    displayVehicle(data.data[0]);
                } else {
                    showError();
                }
            } catch (error) {
                console.error('Error loading vehicle:', error);
                showError();
            }
        }

        function displayVehicle(vehicle) {
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('vehicleContent').classList.remove('hidden');

            document.title = `${vehicle.make} ${vehicle.model} - VeloDrive`;
            document.getElementById('vehicleName').textContent = `${vehicle.make} ${vehicle.model}`;
            document.getElementById('vehicleType').textContent = vehicle.vehicle_type;
            document.getElementById('vehicleYear').textContent = `${vehicle.year} • ${vehicle.color}`;
            document.getElementById('vehiclePrice').textContent = `₱${parseFloat(vehicle.daily_rate).toFixed(2)}`;
            document.getElementById('vehicleTransmission').textContent = vehicle.transmission;
            document.getElementById('vehicleFuel').textContent = vehicle.fuel_type;
            document.getElementById('vehicleSeats').textContent = `${vehicle.seats} People`;
            document.getElementById('vehicleLuggage').textContent = `${vehicle.luggage_capacity || 2} Bags`;
            document.getElementById('vehicleDescription').textContent = vehicle.description || 'No description available for this vehicle.';

            if (vehicle.is_featured) {
                document.getElementById('featuredBadge').classList.remove('hidden');
            }

            // Images
            const images = vehicle.images || [];
            const mainImage = document.getElementById('mainImage');
            mainImage.src = vehicle.display_image;

            if (images.length > 1) {
                const thumbContainer = document.getElementById('thumbnailList');
                images.forEach(img => {
                    const thumb = document.createElement('div');
                    thumb.className = 'shrink-0 size-20 rounded-lg overflow-hidden border-2 border-transparent cursor-pointer hover:border-primary transition-all';
                    thumb.innerHTML = `<img src="${img}" class="w-full h-full object-cover">`;
                    thumb.onclick = () => mainImage.src = img;
                    thumbContainer.appendChild(thumb);
                });
            }

            // Features
            const features = vehicle.features;
            if (features) {
                let featuresArray = [];
                try {
                    featuresArray = typeof features === 'string' ? JSON.parse(features) : features;
                } catch(e) {}

                if (Array.isArray(featuresArray) && featuresArray.length > 0) {
                    document.getElementById('featuresSection').classList.remove('hidden');
                    const list = document.getElementById('featuresList');
                    featuresArray.forEach(f => {
                        const item = document.createElement('div');
                        item.className = 'flex items-center gap-2 text-slate-600 dark:text-slate-300';
                        item.innerHTML = `<span class="material-symbols-outlined text-primary text-sm">check_circle</span> <span class="text-sm">${f}</span>`;
                        list.appendChild(item);
                    });
                }
            }

            document.getElementById('bookNowBtn').href = `checkout_summary.html?vehicle=${vehicle.id}`;
        }

        function showError() {
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('errorState').classList.remove('hidden');
        }

        loadVehicleDetails();
    </script>
</body>
</html>
