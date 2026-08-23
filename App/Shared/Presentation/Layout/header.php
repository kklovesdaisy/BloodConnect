<?php
$basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
if ($basePath === '/' || $basePath === '\\') {
    $basePath = '';
}

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

function activeLink($url, $currentPath)
{
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

    $cleanPath = '/' . trim(str_replace($basePath, '', $currentPath), '/');
    $cleanPath = $cleanPath === '/' ? '/' : rtrim($cleanPath, '/');

    $normalizedUrl = '/' . trim($url, '/');
    $normalizedUrl = $normalizedUrl === '/' ? '/' : rtrim($normalizedUrl, '/');

    return $cleanPath === $normalizedUrl
        ? 'text-red-600 border-b-2 border-red-600 pb-1 lg:pb-1 font-bold'
        : 'text-gray-700 hover:text-red-600 transition font-semibold';
}

// On the home page the logo doubles as the discreet entry to the admin
// console login; everywhere else it navigates back home.
$homeBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$homeCleanPath = '/' . trim(str_replace($homeBase, '', $currentPath), '/');
$isHomePage = ($homeCleanPath === '/' || $homeCleanPath === '');
$logoHref = $basePath . ($isHomePage ? '/admin/login' : '/');
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<header class="fixed top-0 left-0 w-full bg-white shadow-md z-50">
    <!-- Centered content wrapper at 1440px max width -->
    <div class="max-w-[1600px] mx-auto w-full px-6 md:px-12">

        <div class="h-24 flex items-center justify-between"> <!-- Increased header height to h-24 to fit larger text comfortably -->

            <!-- Logo (Maximized Size) -->
            <a href="<?= $logoHref ?>" class="flex items-center gap-3 z-50" <?= $isHomePage ? 'title="Administrator Console"' : '' ?>>
                <div class="text-red-600 text-5xl"> <!-- Scaled up to 5xl -->
                    <i class="fa-solid fa-droplet"></i>
                </div>
                <div>
                    <!-- Scaled up to text-3xl for immediate visual weight -->
                    <h1 class="font-black text-3xl text-gray-900 tracking-tight">BloodConnect</h1>
                    <!-- Scaled up to text-base -->
                    <p class="text-base text-gray-500 font-medium tracking-wide">Donate Blood, Save Lives</p>
                </div>
            </a>

            <!-- Navigation Routes (Scaled up to text-lg / 18px) -->
            <nav class="hidden lg:flex items-center gap-10 text-lg">
                <a href="<?= $basePath ?>/" class="<?= activeLink('/', $currentPath) ?>">Home</a>
                <a href="<?= $basePath ?>/about" class="<?= activeLink('/about', $currentPath) ?>">About Us</a>
                <!-- <a href="<?= $basePath ?>/register" class="<?= activeLink('/register', $currentPath) ?>">Search Donor</a> -->
                <!-- <a href="<?= $basePath ?>/register" class="<?= activeLink('/register', $currentPath) ?>">Blood Requests</a> -->
                <!-- <a href="<?= $basePath ?>/donor/register" class="<?= activeLink('/donor/register', $currentPath) ?>">Donors</a> -->
                <a href="<?= $basePath ?>/contact" class="<?= activeLink('/contact', $currentPath) ?>">Contact Us</a>
            </nav>

            <!-- Buttons (Scaled up to text-lg and adjusted padding) -->
            <div class="hidden lg:flex items-center gap-4">
                <a href="<?= $basePath ?>/login" class="flex items-center gap-2 px-6 py-3 rounded-xl border-2 border-red-600 text-red-600 hover:bg-red-50 transition text-lg font-bold">
                    <i class="fa-regular fa-user"></i> Login
                </a>
                <a href="<?= $basePath ?>/register" class="flex items-center gap-2 px-6 py-3 rounded-xl bg-red-600 text-white hover:bg-red-700 transition text-lg font-bold">
                    <i class="fa-solid fa-user-plus"></i> Register
                </a>
            </div>

            <!-- Mobile Menu Button -->
            <button id="menu-btn" class="block lg:hidden text-gray-700 hover:text-red-600 focus:outline-none z-50">
                <i class="fa-solid fa-bars text-4xl" id="menu-icon"></i>
            </button>

        </div>

    </div>

    <!-- Mobile Dropdown Menu -->
    <div id="mobile-menu" class="hidden lg:hidden absolute top-24 left-0 w-full bg-white border-b border-gray-100 shadow-lg flex flex-col px-8 py-8 gap-5 text-xl font-bold transition-all duration-300">
        <a href="<?= $basePath ?>/" class="<?= activeLink('/', $currentPath) ?> py-2 border-none">Home</a>
        <a href="<?= $basePath ?>/about" class="<?= activeLink('/about', $currentPath) ?> py-2 border-none">About Us</a>
        <a href="<?= $basePath ?>/register" class="<?= activeLink('/register', $currentPath) ?> py-2 border-none">Search Donor</a>
        <a href="<?= $basePath ?>/register" class="<?= activeLink('/register', $currentPath) ?> py-2 border-none">Blood Requests</a>
        <a href="<?= $basePath ?>/donor/register" class="<?= activeLink('/donor/register', $currentPath) ?> py-2 border-none">Donors</a>
        <a href="<?= $basePath ?>/contact" class="<?= activeLink('/contact', $currentPath) ?> py-2 border-none">Contact Us</a>

        <hr class="border-gray-200 my-3">

        <div class="flex flex-col gap-4">
            <a href="<?= $basePath ?>/login" class="flex items-center justify-center gap-2 px-6 py-4 rounded-xl border-2 border-red-600 text-red-600 hover:bg-red-50 transition text-lg font-bold">
                <i class="fa-regular fa-user"></i> Login
            </a>
            <a href="<?= $basePath ?>/register" class="flex items-center justify-center gap-2 px-6 py-4 rounded-xl bg-red-600 text-white hover:bg-red-700 transition text-lg font-bold">
                <i class="fa-solid fa-user-plus"></i> Register
            </a>
        </div>
    </div>
</header>

<!-- Adjusted Space for the taller fixed navbar -->
<div class="h-24"></div>

<!-- Mobile Menu Toggle Script -->
<script>
    const menuBtn = document.getElementById('menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const menuIcon = document.getElementById('menu-icon');

    menuBtn.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');

        if (mobileMenu.classList.contains('hidden')) {
            menuIcon.className = 'fa-solid fa-bars text-4xl';
        } else {
            menuIcon.className = 'fa-solid fa-xmark text-4xl';
        }
    });
</script>