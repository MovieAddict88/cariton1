<?php
/**
 * Admin Login Page
 * Car Rental System
 */

require_once 'includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$admin['id']]);
            
            redirect('index.php');
        } else {
            $error = 'Invalid username or password';
        }
    } catch (PDOException $e) {
        // If table doesn't exist, redirect to install
        if (strpos($e->getMessage(), 'admin_users') !== false) {
            redirect('install.php');
        }
        $error = 'Connection error. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Car Rental System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700;0..1&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-effect {
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
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

    </style>
</head>
<body class="bg-gradient-to-br from-blue-600 to-blue-800 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-8 border border-white/20 shadow-2xl">
            <div class="text-center mb-8">
                <div class="bg-white/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-4xl text-white">directions_car</span>
                </div>
                <h1 class="text-2xl font-bold text-white">DriveAdmin</h1>
                <p class="text-blue-200 mt-2">Admin Control Panel</p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-500/50 text-red-100 rounded-lg p-4 mb-6">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-blue-100 mb-1">Username</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-blue-200">
                            <span class="material-symbols-outlined">person</span>
                        </span>
                        <input type="text" name="username" required
                            class="w-full pl-12 pr-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-blue-300 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-white"
                            placeholder="Enter your username">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-blue-100 mb-1">Password</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-blue-200">
                            <span class="material-symbols-outlined">lock</span>
                        </span>
                        <input type="password" name="password" required
                            class="w-full pl-12 pr-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-blue-300 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-white"
                            placeholder="Enter your password">
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-blue-100 text-sm">
                        <input type="checkbox" class="rounded bg-white/10 border-white/20">
                        Remember me
                    </label>
                    <a href="#" class="text-blue-200 text-sm hover:text-white">Forgot password?</a>
                </div>
                
                <button type="submit" class="w-full bg-white text-blue-600 font-semibold py-3 rounded-lg hover:bg-blue-50 transition shadow-lg">
                    Sign In
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <a href="install.php" class="text-blue-300 text-sm hover:text-white">
                    First time? Run installation
                </a>
            </div>
        </div>
    </div>
</body>
</html>
