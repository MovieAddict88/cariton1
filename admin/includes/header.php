<?php
// admin/includes/header.php
require_once 'config.php';
if (!isLoggedIn() && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    redirect('login.php');
}
$selected_currency = $_GET['currency'] ?? $_SESSION['currency'] ?? DEFAULT_CURRENCY;
$_SESSION['currency'] = $selected_currency;
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= $page_title ?? 'Admin' ?> - DriveAdmin</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
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
                    colors: {
                        "primary": "#137fec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101922",
                        "surface-dark": "#1c2632",
                        "surface-light": "#ffffff",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    }
                },
            },
        }
    </script>
    <link rel="stylesheet" href="includes/responsive.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined' !important;
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-feature-settings: 'liga';
            -webkit-font-smoothing: antialiased;
        }
        .fill-1 { font-variation-settings: 'FILL' 1; }
        
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
            max-width: clamp(280px, 100%, 2560px);
            margin: 0 auto;
            padding: clamp(0.5rem, 2vw, 2rem);
        }
        
        .text-responsive {
            font-size: clamp(0.875rem, 2vw, 1rem);
        }
        
        .heading-responsive {
            font-size: clamp(1.25rem, 3vw, 2.5rem);
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-white transition-colors duration-200">
<div class="fixed inset-0 bg-black/50 z-40 hidden" id="drawer-overlay"></div>
