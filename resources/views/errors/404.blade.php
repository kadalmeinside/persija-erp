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
            
            <div class="relative w-48 h-24 mx-auto mb-8">
                <div class="absolute bottom-0 left-0 w-2 h-full bg-gray-300 dark:bg-gray-600 rounded-t-sm"></div>
                <div class="absolute bottom-0 right-0 w-2 h-full bg-gray-300 dark:bg-gray-600 rounded-t-sm"></div>
                <div class="absolute top-0 left-0 w-full h-2 bg-gray-300 dark:bg-gray-600"></div>
                <div class="absolute top-2 left-2 right-2 bottom-0 border-2 border-dashed border-gray-200 dark:border-gray-700"></div>
                <div class="absolute -right-12 bottom-2 w-6 h-6 bg-red-500 rounded-full shadow-lg animate-bounce"></div>
            </div>

            <h1 class="text-8xl md:text-9xl font-bold font-teko text-red-600">404</h1>
            <h2 class="mt-2 text-2xl md:text-3xl font-semibold text-gray-800 dark:text-white">Halaman Tidak Ditemukan</h2>
            <p class="mt-4 text-gray-600 dark:text-gray-400">Maaf, halaman yang Anda cari sepertinya salah umpan atau sudah keluar lapangan. Mari kembali ke tengah lapangan.</p>
            <div class="mt-8">
                <a href="/" class="inline-block bg-red-600 text-white font-bold py-3 px-8 rounded-md text-lg hover:bg-red-700 transition-transform hover:scale-105">
                    Kembali ke Halaman Utama
                </a>
            </div>
        </main>
    </div>
</body>
</html>
