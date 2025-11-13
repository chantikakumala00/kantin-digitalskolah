<?php
// File: dashboard.php (Menu Utama)

include 'koneksi.php';
check_login(); // Memastikan pengguna telah login

// Logic untuk Logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: index.php");
    exit();
}

$role = $_SESSION['role'];
$page = isset($_GET['page']) ? $_GET['page'] : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Kasir Kantin Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <div class="w-64 bg-indigo-700 text-white flex flex-col shadow-xl">
            <div class="p-6 text-2xl font-bold border-b border-indigo-600">
                Kantin POS
            </div>
            <nav class="flex-grow p-4 space-y-2">
                <a href="dashboard.php" 
                   class="flex items-center p-3 rounded-lg transition duration-200 
                   <?php echo $page == '' ? 'bg-indigo-800' : 'hover:bg-indigo-600'; ?>">
                    <span class="mr-3">&#9733;</span> Dashboard
                </a>
                
                <?php if ($role == 'admin'): ?>
                <a href="dashboard.php?page=menu" 
                   class="flex items-center p-3 rounded-lg transition duration-200 
                   <?php echo $page == 'menu' ? 'bg-indigo-800' : 'hover:bg-indigo-600'; ?>">
                    <span class="mr-3">&#9776;</span> Kelola Menu
                </a>
                <?php endif; ?>

                <a href="dashboard.php?page=transaksi" 
                   class="flex items-center p-3 rounded-lg transition duration-200 
                   <?php echo $page == 'transaksi' ? 'bg-indigo-800' : 'hover:bg-indigo-600'; ?>">
                    <span class="mr-3">&#36;</span> Transaksi Penjualan
                </a>
                
                <a href="dashboard.php?page=laporan" 
                   class="flex items-center p-3 rounded-lg transition duration-200 
                   <?php echo $page == 'laporan' ? 'bg-indigo-800' : 'hover:bg-indigo-600'; ?>">
                    <span class="mr-3">&#128200;</span> Laporan Penjualan
                </a>
            </nav>
            <div class="p-4 border-t border-indigo-600">
                <a href="dashboard.php?action=logout" 
                   class="flex items-center justify-center bg-red-500 text-white py-2 rounded-lg font-semibold hover:bg-red-600 transition duration-200">
                    <span class="mr-2">&#9210;</span> Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="flex items-center justify-between bg-white p-4 shadow-md">
                <div class="text-xl font-semibold text-gray-800">
                    <?php 
                        if ($page == 'menu') echo 'Kelola Menu';
                        else if ($page == 'transaksi') echo 'Transaksi Penjualan';
                        else if ($page == 'laporan') echo 'Laporan Penjualan';
                        else echo 'Selamat Datang';
                    ?>
                </div>
                <div class="text-gray-600">
                    Login sebagai: <span class="font-bold text-indigo-600"><?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                <div class="container mx-auto">
                    <?php
                    // Memuat konten berdasarkan parameter 'page'
                    if ($page == 'menu' && $role == 'admin') {
                        include 'menu.php';
                    } elseif ($page == 'transaksi') {
                        include 'transaksi.php';
                    } elseif ($page == 'laporan') {
                        include 'laporan.php';
                    } else {
                        // Default Dashboard Content
                        ?>
                        <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                            <h2 class="text-2xl font-bold text-gray-800 mb-4">Ringkasan Sistem</h2>
                            <p class="text-gray-600 mb-6">
                                Anda telah berhasil login sebagai <span class="font-semibold text-indigo-600"><?php echo htmlspecialchars($_SESSION['role']); ?></span>. Silakan gunakan menu di samping untuk mengelola data atau melakukan transaksi.
                            </p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <!-- Card 1: Kelola Menu -->
                                <?php if ($role == 'admin'): ?>
                                <a href="dashboard.php?page=menu" class="bg-indigo-100 p-5 rounded-xl shadow hover:shadow-lg transition duration-300 transform hover:scale-[1.02]">
                                    <h3 class="text-xl font-bold text-indigo-700 mb-2">1. Kelola Menu</h3>
                                    <p class="text-indigo-600 text-sm">Tambah, edit, dan hapus data makanan, harga, dan stok.</p>
                                </a>
                                <?php endif; ?>
                                
                                <!-- Card 2: Transaksi -->
                                <a href="dashboard.php?page=transaksi" class="bg-green-100 p-5 rounded-xl shadow hover:shadow-lg transition duration-300 transform hover:scale-[1.02]">
                                    <h3 class="text-xl font-bold text-green-700 mb-2">2. Transaksi Penjualan</h3>
                                    <p class="text-green-600 text-sm">Proses pesanan baru, hitung total, dan catat pembayaran.</p>
                                </a>

                                <!-- Card 3: Laporan -->
                                <a href="dashboard.php?page=laporan" class="bg-yellow-100 p-5 rounded-xl shadow hover:shadow-lg transition duration-300 transform hover:scale-[1.02]">
                                    <h3 class="text-xl font-bold text-yellow-700 mb-2">3. Laporan Penjualan</h3>
                                    <p class="text-yellow-600 text-sm">Lihat riwayat transaksi dan total pendapatan harian/periode.</p>
                                </a>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>