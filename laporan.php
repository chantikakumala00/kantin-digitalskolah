<?php
// File: laporan.php (Laporan Penjualan) - Termasuk dalam dashboard.php

// Pengecekan otorisasi (hanya admin yang bisa melihat laporan yang detail)
if ($_SESSION['role'] !== 'admin') {
    echo '<div class="bg-red-100 text-red-700 p-4 rounded-lg">Akses ditolak. Anda harus menjadi Admin untuk melihat Laporan.</div>';
    return;
}

// ----------------------------------------------------
// 1. LOGIC FILTER & PENGAMBILAN DATA
// ----------------------------------------------------
$filter_date = isset($_POST['filter_date']) ? $_POST['filter_date'] : date('Y-m-d');
$filter_condition = "WHERE t.tanggal_transaksi = ?";

// Query utama untuk mengambil transaksi
$sql = "SELECT 
            t.id, 
            t.tanggal_transaksi, 
            t.total_bayar, 
            t.nama_pembeli,
            u.username as kasir_name
        FROM 
            transactions t
        JOIN 
            users u ON t.kasir_id = u.id
        $filter_condition
        ORDER BY 
            t.created_at DESC";

$stmt = $koneksi->prepare($sql);
$stmt->bind_param("s", $filter_date);
$stmt->execute();
$transactions_result = $stmt->get_result();
$stmt->close();

$total_pendapatan = 0;
$transactions_list = [];
while ($row = $transactions_result->fetch_assoc()) {
    $total_pendapatan += $row['total_bayar'];
    $transactions_list[] = $row;
}

// ----------------------------------------------------
// 2. LOGIC DETAIL TRANSAKSI (jika ada)
// ----------------------------------------------------
$detail_transaksi = null;
if (isset($_GET['detail_id'])) {
    $detail_id = intval($_GET['detail_id']);

    // Ambil detail item
    $sql_detail = "SELECT 
                       td.*, 
                       m.nama_menu
                   FROM 
                       transaction_details td
                   JOIN 
                       menu m ON td.menu_id = m.id
                   WHERE 
                       td.transaction_id = ?";
    $stmt_detail = $koneksi->prepare($sql_detail);
    $stmt_detail->bind_param("i", $detail_id);
    $stmt_detail->execute();
    $detail_transaksi = $stmt_detail->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_detail->close();
}

?>

<!-- UI: Laporan Penjualan -->
<div class="space-y-6">

    <!-- Filter dan Ringkasan Pendapatan -->
    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
        
        <!-- Form Filter Tanggal -->
        <div class="md:col-span-1">
            <h2 class="text-xl font-bold text-gray-800 mb-2">Filter Tanggal</h2>
            <form action="dashboard.php?page=laporan" method="POST" class="flex space-x-2">
                <input type="date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>"
                       class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                <button type="submit"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition duration-200">
                    Filter
                </button>
            </form>
        </div>

        <!-- Ringkasan Pendapatan -->
        <div class="md:col-span-2 bg-green-100 p-4 rounded-xl border-2 border-green-400">
            <p class="text-sm font-medium text-green-700">Total Pendapatan pada Tanggal <?php echo date('d F Y', strtotime($filter_date)); ?>:</p>
            <p class="text-3xl font-black text-green-800">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></p>
        </div>
    </div>

    <!-- Tabel Daftar Transaksi -->
    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Daftar Transaksi (<?php echo count($transactions_list); ?> Transaksi)</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Transaksi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kasir</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pembeli</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Bayar</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($transactions_list)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 italic">Tidak ada transaksi pada tanggal ini.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($transactions_list as $row): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $row['id']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['kasir_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo empty($row['nama_pembeli']) ? 'Umum' : htmlspecialchars($row['nama_pembeli']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-600">Rp <?php echo number_format($row['total_bayar'], 0, ',', '.'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="dashboard.php?page=laporan&detail_id=<?php echo $row['id']; ?>" 
                               class="text-indigo-600 hover:text-indigo-900">Lihat Detail</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal/Tampilan Detail Transaksi -->
    <?php if ($detail_transaksi !== null && isset($_GET['detail_id'])): 
        // Ambil header transaksi yang sedang dilihat (dari list transaksi yang sudah difilter)
        $header = array_filter($transactions_list, function($t) { 
            return $t['id'] == $_GET['detail_id']; 
        });
        $header = reset($header);
        
        if ($header):
        ?>
        <div class="fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-50 flex justify-center items-center p-4">
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl p-6">
                
                <h3 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Detail Transaksi #<?php echo $header['id']; ?></h3>
                
                <!-- Info Header -->
                <div class="grid grid-cols-2 gap-2 mb-4 text-sm">
                    <p><strong>Tanggal:</strong> <?php echo date('d F Y', strtotime($header['tanggal_transaksi'])); ?></p>
                    <p><strong>Kasir:</strong> <?php echo htmlspecialchars($header['kasir_name']); ?></p>
                    <p><strong>Pembeli:</strong> <?php echo empty($header['nama_pembeli']) ? 'Umum' : htmlspecialchars($header['nama_pembeli']); ?></p>
                    <p><strong>Total:</strong> <span class="font-bold text-indigo-600">Rp <?php echo number_format($header['total_bayar'], 0, ',', '.'); ?></span></p>
                </div>

                <!-- Tabel Detail Item -->
                <div class="overflow-x-auto border rounded-lg mb-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Harga Satuan</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($detail_transaksi as $item): ?>
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($item['nama_menu']); ?></td>
                                <td class="px-4 py-2 text-center text-sm text-gray-500"><?php echo $item['jumlah_pesanan']; ?></td>
                                <td class="px-4 py-2 text-right text-sm text-gray-500">Rp <?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?></td>
                                <td class="px-4 py-2 text-right text-sm font-medium text-gray-900">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Tombol Tutup -->
                <div class="text-right">
                    <a href="dashboard.php?page=laporan&filter_date=<?php echo htmlspecialchars($filter_date); ?>" 
                       class="bg-red-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-600 transition duration-200">
                        Tutup
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

</div>