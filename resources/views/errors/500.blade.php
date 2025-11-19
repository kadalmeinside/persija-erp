<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terjadi Kesalahan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Teko:wght@500;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .font-teko {
            font-family: 'Teko', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <div class="flex items-center justify-center min-h-screen">
        <main class="text-center p-8 w-full max-w-lg">
            <div class="relative w-32 h-32 mx-auto mb-8">
                <div class="w-full h-full bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center">
                    <div class="w-24 h-24 bg-white dark:bg-gray-800 rounded-full opacity-50 transform scale-75"></div>
                </div>
                <div class="absolute top-1/2 left-1/2 w-8 h-1 bg-gray-400 dark:bg-gray-500 rounded-full transform -translate-x-1/2 -translate-y-1/2 rotate-45"></div>
                 <div class="absolute top-1/2 left-1/2 w-8 h-1 bg-gray-400 dark:bg-gray-500 rounded-full transform -translate-x-1/2 -translate-y-1/2 -rotate-45"></div>
            </div>

            <h1 class="text-8xl md:text-9xl font-bold font-teko text-gray-500">500</h1>
            <h2 class="mt-2 text-2xl md:text-3xl font-semibold text-gray-800 dark:text-white">Terjadi Masalah Internal</h2>
            <p class="mt-4 text-gray-600 dark:text-gray-400">Maaf, terjadi sedikit masalah teknis di sisi kami, seperti bola yang tiba-tiba kempes. Tim kami sedang memperbaikinya.</p>
            <div class="mt-8">
                <a href="/" class="inline-block bg-gray-600 text-white font-bold py-3 px-8 rounded-md text-lg hover:bg-gray-700 transition-transform hover:scale-105">
                    Coba Lagi Nanti
                </a>
            </div>
        </main>
    </div>
</body>
</html>
