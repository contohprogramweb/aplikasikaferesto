<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * General Language File - Bahasa Indonesia
 * Sistem Manajemen Kafe & Restoran v4.0
 */

// Common UI Elements
$lang['app_name'] = 'Sistem Manajemen Kafe';
$lang['app_title'] = 'RestoPOS';
$lang['dashboard'] = 'Dashboard';
$lang['home'] = 'Beranda';
$lang['login'] = 'Masuk';
$lang['logout'] = 'Keluar';
$lang['register'] = 'Daftar';
$lang['forgot_password'] = 'Lupa Kata Sandi';
$lang['reset_password'] = 'Atur Ulang Kata Sandi';
$lang['submit'] = 'Kirim';
$lang['save'] = 'Simpan';
$lang['cancel'] = 'Batal';
$lang['delete'] = 'Hapus';
$lang['edit'] = 'Ubah';
$lang['view'] = 'Lihat';
$lang['search'] = 'Cari';
$lang['filter'] = 'Filter';
$lang['clear'] = 'Bersihkan';
$lang['close'] = 'Tutup';
$lang['confirm'] = 'Konfirmasi';
$lang['yes'] = 'Ya';
$lang['no'] = 'Tidak';
$lang['ok'] = 'OK';
$lang['back'] = 'Kembali';
$lang['next'] = 'Lanjut';
$lang['previous'] = 'Sebelumnya';
$lang['loading'] = 'Memuat...';
$lang['processing'] = 'Memproses...';
$lang['success'] = 'Berhasil';
$lang['error'] = 'Gagal';
$lang['warning'] = 'Peringatan';
$lang['info'] = 'Informasi';

// Status Labels
$lang['status_pending'] = 'Menunggu';
$lang['status_confirmed'] = 'Diterima';
$lang['status_preparing'] = 'Dimasak';
$lang['status_ready'] = 'Siap';
$lang['status_delivered'] = 'Diantar';
$lang['status_completed'] = 'Selesai';
$lang['status_cancelled'] = 'Dibatalkan';
$lang['status_unavailable'] = 'Tidak Tersedia';

// Table Status
$lang['table_available'] = 'Tersedia';
$lang['table_occupied'] = 'Terisi';
$lang['table_waiting_payment'] = 'Menunggu Bayar';
$lang['table_cleaning'] = 'Dibersihkan';
$lang['table_closed'] = 'Tutup';
$lang['table_damaged'] = 'Rusak';

// Order Status
$lang['order_new'] = 'Pesanan Baru';
$lang['order_accepted'] = 'Diterima';
$lang['order_cooking'] = 'Sedang Dimasak';
$lang['order_ready_to_serve'] = 'Siap Diantar';
$lang['order_served'] = 'Sudah Diantar';
$lang['order_paid'] = 'Lunas';

// Time Related
$lang['time_now'] = 'Sekarang';
$lang['time_ago'] = 'yang lalu';
$lang['duration'] = 'Durasi';
$lang['wait_time'] = 'Waktu Tunggu';
$lang['minutes'] = 'menit';
$lang['hours'] = 'jam';
$lang['seconds'] = 'detik';

// Messages
$lang['msg_confirm_delete'] = 'Apakah Anda yakin ingin menghapus data ini?';
$lang['msg_save_success'] = 'Data berhasil disimpan.';
$lang['msg_update_success'] = 'Data berhasil diperbarui.';
$lang['msg_delete_success'] = 'Data berhasil dihapus.';
$lang['msg_error_occurred'] = 'Terjadi kesalahan. Silakan coba lagi.';
$lang['msg_no_data'] = 'Tidak ada data yang tersedia.';
$lang['msg_loading'] = 'Sedang memuat data...';
$lang['msg_session_expired'] = 'Sesi Anda telah berakhir. Silakan masuk kembali.';
$lang['msg_unauthorized'] = 'Anda tidak memiliki izin untuk mengakses halaman ini.';

// Buttons
$lang['btn_add_new'] = 'Tambah Baru';
$lang['btn_export'] = 'Ekspor';
$lang['btn_import'] = 'Impor';
$lang['btn_print'] = 'Cetak';
$lang['btn_refresh'] = 'Segarkan';
$lang['btn_apply'] = 'Terapkan';
$lang['btn_accept_all'] = 'Terima Semua';
$lang['btn_select_all'] = 'Pilih Semua';
$lang['btn_deselect_all'] = 'Batal Pilih Semua';

// Payment
$lang['payment'] = 'Pembayaran';
$lang['payment_method'] = 'Metode Pembayaran';
$lang['payment_cash'] = 'Tunai';
$lang['payment_debit'] = 'Kartu Debit';
$lang['payment_credit'] = 'Kartu Kredit';
$lang['payment_qris'] = 'QRIS';
$lang['payment_transfer'] = 'Transfer Bank';
$lang['amount_paid'] = 'Jumlah Dibayar';
$lang['change_amount'] = 'Kembalian';
$lang['exact_amount'] = 'Uang Pas';

// Discount & Tax
$lang['discount'] = 'Diskon';
$lang['discount_percent'] = 'Diskon (%)';
$lang['discount_nominal'] = 'Diskon (Rp)';
$lang['tax'] = 'Pajak';
$lang['service_charge'] = 'Biaya Layanan';
$lang['subtotal'] = 'Subtotal';
$lang['total'] = 'Total';
$lang['grand_total'] = 'Grand Total';

// Roles
$lang['role_admin'] = 'Administrator';
$lang['role_manager'] = 'Manajer';
$lang['role_staff'] = 'Staf';
$lang['role_waiter'] = 'Pelayan';
$lang['role_cashier'] = 'Kasir';
$lang['role_kitchen'] = 'Dapur';
$lang['role_customer'] = 'Pelanggan';

// Navigation
$lang['nav_menu'] = 'Menu';
$lang['nav_orders'] = 'Pesanan';
$lang['nav_tables'] = 'Meja';
$lang['nav_reports'] = 'Laporan';
$lang['nav_settings'] = 'Pengaturan';
$lang['nav_users'] = 'Pengguna';
$lang['nav_categories'] = 'Kategori';
$lang['nav_items'] = 'Item Menu';

// System
$lang['sys_version'] = 'Versi Sistem';
$lang['sys_last_update'] = 'Pembaruan Terakhir';
$lang['sys_maintenance'] = 'Dalam Perawatan';
$lang['sys_online'] = 'Online';
$lang['sys_offline'] = 'Offline';

// Validation
$lang['val_required'] = 'Bidang {field} wajib diisi.';
$lang['val_min_length'] = 'Bidang {field} minimal {param} karakter.';
$lang['val_max_length'] = 'Bidang {field} maksimal {param} karakter.';
$lang['val_valid_email'] = 'Bidang {field} harus berisi alamat email yang valid.';
$lang['val_valid_number'] = 'Bidang {field} harus berisi angka.';
$lang['val_matches'] = 'Bidang {field} tidak cocok dengan {param}.';
$lang['val_is_unique'] = 'Bidang {field} sudah digunakan.';

// Date & Time Format
$lang['date_format'] = 'd/m/Y';
$lang['time_format'] = 'H:i';
$lang['datetime_format'] = 'd/m/Y H:i';
$lang['today'] = 'Hari Ini';
$lang['yesterday'] = 'Kemarin';
$lang['tomorrow'] = 'Besok';
