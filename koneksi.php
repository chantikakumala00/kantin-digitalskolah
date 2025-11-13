<?php
// File: koneksi.php

// Konfigurasi Database
$host = "localhost";
$user = "root"; // Ganti dengan user database Anda
$password = ""; // Ganti dengan password database Anda
$database = "kasir_kantin"; // Ganti dengan nama database yang Anda buat

// Buat koneksi
$koneksi = new mysqli($host, $user, $password, $database);

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Fungsi untuk memastikan pengguna telah login
function check_login() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}
?>