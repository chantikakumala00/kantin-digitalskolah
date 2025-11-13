<?php
// File: transaksi.php (Transaksi Penjualan) - Termasuk dalam dashboard.php

// ----------------------------------------------------
// 1. LOGIC UTAMA TRANSAKSI
// ----------------------------------------------------

// Inisialisasi keranjang belanja (cart)
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$menu_list_result = $koneksi->query("SELECT id, nama_menu, harga, stok FROM menu WHERE stok > 0 ORDER BY nama_menu");
$menu_options = [];
while($row = $menu_list_result->fetch_assoc()) {
    $menu_options[$row['id']] = $row;
}

// Tambah Item ke Keranjang
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $menu_id = intval($_POST['menu_id']);
    $jumlah = intval($_POST['jumlah_pesanan']);

    if (isset($menu_options[$menu_id]) && $jumlah > 0) {
        $item = $menu_options[$menu_id];

        // Cek Stok
        if ($jumlah > $item['stok']) {
            $error_message = "Stok " . $item['nama_menu'] . " tidak mencukupi. Sisa stok: " . $item['stok'];
        } else {
            // Cek apakah item sudah ada di keranjang
            if (isset($_SESSION['cart'][$menu_id])) {
                // Pastikan total kuantitas tidak melebihi stok
                if (($_SESSION['cart'][$menu_id]['jumlah'] + $jumlah) > $item['stok']) {
                    $error_message = "Total pesanan " . $item['nama_menu'] . " melebihi stok yang tersedia.";
                } else {
                    $_SESSION['cart'][$menu_id]['jumlah'] += $jumlah;
                }
            } else {
                $_SESSION['cart'][$menu_id] = [
                    'id' => $menu_id,
                    'nama_menu' => $item['nama_menu'],
                    'harga' => $item['harga'],
                    'jumlah' => $jumlah
                ];
            }
        }
    }
}

// Hapus Item dari Keranjang
if (isset($_GET['remove_item'])) {
    $menu_id = intval($_GET['remove_item']);
    if (isset($_SESSION['cart'][$menu_id])) {
        unset($_SESSION['cart'][$menu_id]);
        header("Location: dashboard.php?page=transaksi");
        exit();
    }
}

// Proses Pembayaran dan Simpan Transaksi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    $total_bayar = floatval($_POST['total_bayar_hidden']);
    $dibayar = floatval($_POST['dibayar']);
    $nama_pembeli = $_POST['nama_pembeli'];
    $kasir_id = $_SESSION['user_id'];
    $kembalian = $dibayar - $total_bayar;

    if ($total_bayar > 0 && $dibayar >= $total_bayar && !empty($_SESSION['cart'])) {
        // Mulai Transaksi Database
        $koneksi->begin_transaction();
        $success = true;

        try {
            // 1. Simpan Header Transaksi
            $stmt = $koneksi->prepare("INSERT INTO transactions (tanggal_transaksi, total_bayar, dibayar, kembalian, kasir_id, nama_pembeli) VALUES (CURDATE(), ?, ?, ?, ?, ?)");
            $stmt->bind_param("dddis", $total_bayar, $dibayar, $kembalian, $kasir_id, $nama_pembeli);
            $stmt->execute();
            $transaction_id = $stmt->insert_id;
            $stmt->close();

            // 2. Simpan Detail Transaksi dan Update Stok
            foreach ($_SESSION['cart'] as $item) {
                $subtotal = $item['harga'] * $item['jumlah'];
                
                // Simpan Detail
                $stmt = $koneksi->prepare("INSERT INTO transaction_details (transaction_id, menu_id, jumlah_pesanan, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiddi", $transaction_id, $item['id'], $item['jumlah'], $item['harga'], $subtotal);
                $stmt->execute();
                $stmt->close();

                // Update Stok (Kurangi Stok)
                $stmt = $koneksi->prepare("UPDATE menu SET stok = stok - ? WHERE id = ?");
                $stmt->bind_param("ii", $item['jumlah'], $item['id']);
                $stmt->execute();
                $stmt->close();
            }

            // Commit Transaksi jika semua berhasil
            $koneksi->commit();
            $success_message = "Transaksi Berhasil! Kembalian: Rp " . number_format($kembalian, 0, ',', '.');
            $_SESSION['cart'] = []; // Kosongkan keranjang
            
        } catch (mysqli_sql_exception $exception) {
            $koneksi->rollback();
            $error_message = "Transaksi Gagal: " . $exception->getMessage();
            $success = false;
        }
    } else if ($total_bayar > 0) {
        $error_message = "Pembayaran harus lebih besar atau sama dengan Total Bayar.";
    }
}

// Hitung Total Belanja
$grand_total = 0;
foreach ($_SESSION['cart'] as $item) {
    $grand_total += $item['harga'] * $item['jumlah'];
}

?>

<!-- UI: Tampilan Transaksi (POS) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Kolom 1: Pemilihan Menu dan Keranjang -->
    <div class="lg:col-span-2 space-y-6">
        
        <!-- Form Tambah ke Keranjang -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
            <h2 class="text-xl font-bold text-indigo-700 mb-4">1. Tambah Pesanan</h2>
            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <?php if (isset($success_message)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <form action="dashboard.php?page=transaksi" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label for="menu_id" class="block text-sm font-medium text-gray-700 mb-1">Pilih Menu</label>
                    <select id="menu_id" name="menu_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- Pilih Menu Makanan/Minuman --</option>
                        <?php foreach ($menu_options as $id => $item): ?>
                            <option value="<?php echo $id; ?>">
                                <?php echo htmlspecialchars($item['nama_menu']); ?> (Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?> | Stok: <?php echo $item['stok']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="jumlah_pesanan" class="block text-sm font-medium text-gray-700 mb-1">Jumlah Pesanan</label>
                    <input type="number" id="jumlah_pesanan" name="jumlah_pesanan" required min="1" value="1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <div class="sm:col-span-3">
                    <button type="submit" name="add_to_cart"
                            class="w-full bg-green-500 text-white py-2.5 rounded-lg font-semibold hover:bg-green-600 transition duration-200">
                        Masukkan Pesanan
                    </button>
                </div>
            </form>
        </div>

        <!-- Keranjang Belanja -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
            <h2 class="text-xl font-bold text-gray-800 mb-4">2. Detail Pesanan (Keranjang)</h2>
            <div class="overflow-x-auto">
                <?php if (empty($_SESSION['cart'])): ?>
                    <p class="text-gray-500 italic">Keranjang belanja kosong. Silakan tambah item.</p>
                <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Menu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['nama_menu']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $item['jumlah']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-600">Rp <?php echo number_format($item['harga'] * $item['jumlah'], 0, ',', '.'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="dashboard.php?page=transaksi&remove_item=<?php echo $item['id']; ?>" class="text-red-600 hover:text-red-900">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                         <tr class="bg-gray-50">
                            <td colspan="3" class="px-6 py-3 text-right text-base font-bold text-gray-800">TOTAL BELANJA</td>
                            <td colspan="2" class="px-6 py-3 text-left text-base font-extrabold text-green-600">Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></td>
                        </tr>
                    </tfoot>
                </table>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Kolom 2: Pembayaran -->
    <div class="lg:col-span-1">
        <div class="bg-indigo-600 text-white p-6 rounded-xl shadow-2xl sticky top-4">
            <h2 class="text-2xl font-extrabold mb-4">3. Proses Pembayaran</h2>
            <form action="dashboard.php?page=transaksi" method="POST" onsubmit="return validatePayment()">
                <input type="hidden" name="total_bayar_hidden" id="total_bayar_hidden" value="<?php echo $grand_total; ?>">

                <!-- Total Bayar Display -->
                <div class="mb-4 p-4 bg-white rounded-lg text-indigo-700">
                    <label class="block text-sm font-medium mb-1">TOTAL BAYAR</label>
                    <p class="text-3xl font-black" id="display_grand_total">Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></p>
                </div>

                <!-- Input Nama Pembeli -->
                <div class="mb-4">
                    <label for="nama_pembeli" class="block text-sm font-medium mb-1">Nama Pembeli (Opsional)</label>
                    <input type="text" id="nama_pembeli" name="nama_pembeli" placeholder="Contoh: Siswa Kelas X-A"
                           class="w-full px-3 py-2 text-gray-800 border-gray-300 rounded-lg focus:ring-yellow-500 focus:border-yellow-500">
                </div>

                <!-- Input Dibayar -->
                <div class="mb-6">
                    <label for="dibayar" class="block text-sm font-medium mb-1">Jumlah Uang Dibayar (Rp)</label>
                    <input type="number" id="dibayar" name="dibayar" required min="<?php echo $grand_total; ?>"
                           class="w-full px-3 py-3 text-gray-800 border-gray-300 rounded-lg text-xl font-bold focus:ring-yellow-500 focus:border-yellow-500"
                           oninput="calculateChange()">
                </div>
                
                <!-- Kembalian Display -->
                <div class="mb-6 p-4 bg-indigo-700 rounded-lg">
                    <label class="block text-sm font-medium mb-1">KEMBALIAN</label>
                    <p class="text-2xl font-black text-yellow-300" id="display_kembalian">Rp 0</p>
                </div>

                <button type="submit" name="process_payment" id="payment_button"
                        class="w-full bg-yellow-400 text-indigo-900 py-3 rounded-lg font-extrabold text-lg hover:bg-yellow-500 transition duration-200 shadow-xl"
                        <?php echo empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                    PROSES BAYAR & SIMPAN
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function calculateChange() {
        const total = parseFloat(document.getElementById('total_bayar_hidden').value);
        const paid = parseFloat(document.getElementById('dibayar').value);
        const change = paid >= total ? paid - total : 0;
        
        // Format ke Rupiah
        const formatter = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        });
        
        document.getElementById('display_kembalian').innerText = formatter.format(change);
        
        // Disable tombol jika pembayaran kurang
        const button = document.getElementById('payment_button');
        if (paid < total || isNaN(paid)) {
            button.disabled = true;
            button.classList.remove('bg-yellow-400', 'hover:bg-yellow-500');
            button.classList.add('bg-gray-400');
        } else {
            button.disabled = false;
            button.classList.remove('bg-gray-400');
            button.classList.add('bg-yellow-400', 'hover:bg-yellow-500');
        }
    }
    
    // Validasi saat submit
    function validatePayment() {
        const total = parseFloat(document.getElementById('total_bayar_hidden').value);
        if (total <= 0) {
            alert('Keranjang belanja masih kosong.');
            return false;
        }
        const paid = parseFloat(document.getElementById('dibayar').value);
        if (paid < total) {
            alert('Jumlah uang yang dibayarkan tidak mencukupi!');
            return false;
        }
        return true;
    }
    
    // Panggil saat halaman dimuat untuk inisialisasi
    document.addEventListener('DOMContentLoaded', calculateChange);
</script>