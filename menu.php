<?php
// File: menu.php (Kelola Menu) - Termasuk dalam dashboard.php

// Pengecekan otorisasi (sudah dilakukan di dashboard.php, tapi diulang untuk keamanan)
if ($_SESSION['role'] !== 'admin') {
    echo '<div class="bg-red-100 text-red-700 p-4 rounded-lg">Akses ditolak. Anda harus menjadi Admin untuk melihat halaman ini.</div>';
    return;
}

// ----------------------------------------------------
// 1. LOGIC CRUD
// ----------------------------------------------------
$message = '';
$edit_data = null;

// Hapus Menu
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $koneksi->prepare("DELETE FROM menu WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = '<div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4">Menu berhasil dihapus!</div>';
    } else {
        $message = '<div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4">Gagal menghapus menu.</div>';
    }
    $stmt->close();
}

// Ambil data untuk Edit
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $koneksi->prepare("SELECT * FROM menu WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
    }
    $stmt->close();
}

// Tambah / Edit Menu (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_menu'])) {
    $nama_menu = $_POST['nama_menu'];
    $kategori = $_POST['kategori'];
    $harga = intval($_POST['harga']);
    $stok = intval($_POST['stok']);
    $menu_id = isset($_POST['menu_id']) ? intval($_POST['menu_id']) : 0;

    if ($menu_id > 0) {
        // Update
        $stmt = $koneksi->prepare("UPDATE menu SET nama_menu = ?, kategori = ?, harga = ?, stok = ? WHERE id = ?");
        $stmt->bind_param("ssiii", $nama_menu, $kategori, $harga, $stok, $menu_id);
        $action_msg = "diperbarui";
    } else {
        // Insert
        $stmt = $koneksi->prepare("INSERT INTO menu (nama_menu, kategori, harga, stok) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $nama_menu, $kategori, $harga, $stok);
        $action_msg = "ditambahkan";
    }

    if ($stmt->execute()) {
        $message = '<div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4">Menu berhasil ' . $action_msg . '!</div>';
        $edit_data = null; // Reset form setelah berhasil update/insert
    } else {
        $message = '<div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4">Gagal ' . $action_msg . ' menu.</div>';
    }
    $stmt->close();
}


// ----------------------------------------------------
// 2. TAMPILKAN DAFTAR MENU
// ----------------------------------------------------
$menu_list = $koneksi->query("SELECT * FROM menu ORDER BY kategori, nama_menu");
?>

<!-- UI: Form Tambah/Edit Menu -->
<div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 mb-8">
    <h2 class="text-xl font-bold text-gray-800 mb-4"><?php echo $edit_data ? 'Edit' : 'Tambah'; ?> Menu</h2>
    <?php echo $message; ?>
    
    <form action="dashboard.php?page=menu" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <?php if ($edit_data): ?>
            <input type="hidden" name="menu_id" value="<?php echo $edit_data['id']; ?>">
        <?php endif; ?>
        
        <div class="md:col-span-1">
            <label for="nama_menu" class="block text-sm font-medium text-gray-700 mb-1">Nama Menu</label>
            <input type="text" id="nama_menu" name="nama_menu" required
                   value="<?php echo $edit_data ? htmlspecialchars($edit_data['nama_menu']) : ''; ?>"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div class="md:col-span-1">
            <label for="kategori" class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
            <select id="kategori" name="kategori" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                <option value="Makanan" <?php echo ($edit_data && $edit_data['kategori'] == 'Makanan') ? 'selected' : ''; ?>>Makanan</option>
                <option value="Minuman" <?php echo ($edit_data && $edit_data['kategori'] == 'Minuman') ? 'selected' : ''; ?>>Minuman</option>
                <option value="Lainnya" <?php echo ($edit_data && $edit_data['kategori'] == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
            </select>
        </div>

        <div class="md:col-span-1">
            <label for="harga" class="block text-sm font-medium text-gray-700 mb-1">Harga (Rp)</label>
            <input type="number" id="harga" name="harga" required min="100"
                   value="<?php echo $edit_data ? $edit_data['harga'] : ''; ?>"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div class="md:col-span-1">
            <label for="stok" class="block text-sm font-medium text-gray-700 mb-1">Stok</label>
            <input type="number" id="stok" name="stok" required min="0"
                   value="<?php echo $edit_data ? $edit_data['stok'] : ''; ?>"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div class="md:col-span-1 flex items-end">
            <button type="submit" name="submit_menu"
                    class="w-full bg-indigo-600 text-white py-2.5 rounded-lg font-semibold hover:bg-indigo-700 transition duration-200">
                <?php echo $edit_data ? 'Simpan Perubahan' : 'Tambah Menu Baru'; ?>
            </button>
        </div>
    </form>
</div>

<!-- UI: Tabel Daftar Menu -->
<div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Daftar Menu Kantin</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Menu</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php $i = 1; while ($row = $menu_list->fetch_assoc()): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $i++; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['nama_menu']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['kategori']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-<?php echo $row['stok'] < 10 ? 'red' : 'green'; ?>-600 font-semibold"><?php echo number_format($row['stok'], 0, ',', '.'); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="dashboard.php?page=menu&edit=<?php echo $row['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                        <a href="dashboard.php?page=menu&delete=<?php echo $row['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus menu ini?');" class="text-red-600 hover:text-red-900">Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>