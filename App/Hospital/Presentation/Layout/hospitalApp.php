<?php

/** @var string $content */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BloodConnect - Hospital</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>

<body class="bg-gradient-to-b from-[#fdf6f6] to-[#faf0f0] text-slate-800 antialiased">

    <div class="flex min-h-screen">

        <?php require __DIR__ . '/sidebar.php'; ?>

        <div class="flex-1 flex flex-col">

            <?php require __DIR__ . '/topbar.php'; ?>

            <main class="flex-1 p-6">
                <?= $content ?>
            </main>

        </div>

    </div>

</body>

</html>
