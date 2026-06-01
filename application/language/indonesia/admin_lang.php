<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin Language File - Bahasa Indonesia
 * Sistem Manajemen Kafe & Restoran v4.0
 */

// Dashboard
$lang['admin_dashboard'] = 'Dashboard Admin';
$lang['admin_welcome'] = 'Selamat Datang, %s';
$lang['admin_stats_today'] = 'Statistik Hari Ini';
$lang['admin_total_orders'] = 'Total Pesanan';
$lang['admin_total_revenue'] = 'Total Pendapatan';
$lang['admin_pending_orders'] = 'Pesanan Tertunda';
$lang['admin_active_tables'] = 'Meja Aktif';
$lang['admin_total_customers'] = 'Total Pelanggan';
$lang['admin_low_stock_items'] = 'Item Stok Menipis';

// Menu Management (UC-ADM-01)
$lang['adm_menu_title'] = 'Manajemen Menu';
$lang['adm_menu_add'] = 'Tambah Item Menu';
$lang['adm_menu_edit'] = 'Ubah Item Menu';
$lang['adm_menu_delete'] = 'Hapus Item Menu';
$lang['adm_menu_name'] = 'Nama Menu';
$lang['adm_menu_description'] = 'Deskripsi';
$lang['adm_menu_category'] = 'Kategori';
$lang['adm_menu_price'] = 'Harga';
$lang['adm_menu_cost'] = 'Harga Modal';
$lang['adm_menu_image'] = 'Gambar';
$lang['adm_menu_available'] = 'Tersedia';
$lang['adm_menu_unavailable'] = 'Tidak Tersedia';
$lang['adm_menu_preparation_time'] = 'Waktu Persiapan';
$lang['adm_menu_allergens'] = 'Alergen';
$lang['adm_menu_spicy_level'] = 'Level Pedas';
$lang['adm_menu_calories'] = 'Kalori';
$lang['adm_menu_tags'] = 'Tag';
$lang['adm_menu_sort_order'] = 'Urutan';
$lang['adm_menu_featured'] = 'Unggulan';
$lang['adm_menu_success_add'] = 'Item menu berhasil ditambahkan';
$lang['adm_menu_success_update'] = 'Item menu berhasil diperbarui';
$lang['adm_menu_success_delete'] = 'Item menu berhasil dihapus';

// Category Management (UC-ADM-02)
$lang['adm_cat_title'] = 'Manajemen Kategori';
$lang['adm_cat_add'] = 'Tambah Kategori';
$lang['adm_cat_edit'] = 'Ubah Kategori';
$lang['adm_cat_delete'] = 'Hapus Kategori';
$lang['adm_cat_name'] = 'Nama Kategori';
$lang['adm_cat_description'] = 'Deskripsi';
$lang['adm_cat_icon'] = 'Ikon';
$lang['adm_cat_color'] = 'Warna';
$lang['adm_cat_display_order'] = 'Urutan Tampil';
$lang['adm_cat_active'] = 'Aktif';
$lang['adm_cat_inactive'] = 'Nonaktif';
$lang['adm_cat_success_add'] = 'Kategori berhasil ditambahkan';
$lang['adm_cat_success_update'] = 'Kategori berhasil diperbarui';
$lang['adm_cat_success_delete'] = 'Kategori berhasil dihapus';

// Table Management (UC-ADM-03)
$lang['adm_table_title'] = 'Manajemen Meja';
$lang['adm_table_add'] = 'Tambah Meja';
$lang['adm_table_edit'] = 'Ubah Meja';
$lang['adm_table_delete'] = 'Hapus Meja';
$lang['adm_table_code'] = 'Kode Meja';
$lang['adm_table_capacity'] = 'Kapasitas';
$lang['adm_table_location'] = 'Lokasi';
$lang['adm_table_status'] = 'Status';
$lang['adm_table_qr_code'] = 'QR Code';
$lang['adm_table_generate_qr'] = 'Generate QR Code';
$lang['adm_table_print_qr'] = 'Cetak QR Code';
$lang['adm_table_regenerate_all'] = 'Generate Ulang Semua QR';
$lang['adm_table_position_x'] = 'Posisi X';
$lang['adm_table_position_y'] = 'Posisi Y';
$lang['adm_table_section'] = 'Seksi';
$lang['adm_table_success_add'] = 'Meja berhasil ditambahkan';
$lang['adm_table_success_update'] = 'Meja berhasil diperbarui';
$lang['adm_table_success_delete'] = 'Meja berhasil dihapus';

// User Management (UC-ADM-04)
$lang['adm_user_title'] = 'Manajemen Pengguna';
$lang['adm_user_add'] = 'Tambah Pengguna';
$lang['adm_user_edit'] = 'Ubah Pengguna';
$lang['adm_user_delete'] = 'Hapus Pengguna';
$lang['adm_user_username'] = 'Username';
$lang['adm_user_email'] = 'Email';
$lang['adm_user_password'] = 'Kata Sandi';
$lang['adm_user_confirm_password'] = 'Konfirmasi Kata Sandi';
$lang['adm_user_fullname'] = 'Nama Lengkap';
$lang['adm_user_phone'] = 'Nomor Telepon';
$lang['adm_user_role'] = 'Peran';
$lang['adm_user_status'] = 'Status';
$lang['adm_user_active'] = 'Aktif';
$lang['adm_user_inactive'] = 'Nonaktif';
$lang['adm_user_last_login'] = 'Login Terakhir';
$lang['adm_user_created_at'] = 'Dibuat Pada';
$lang['adm_user_change_password'] = 'Ubah Kata Sandi';
$lang['adm_user_reset_password'] = 'Reset Kata Sandi';
$lang['adm_user_success_add'] = 'Pengguna berhasil ditambahkan';
$lang['adm_user_success_update'] = 'Pengguna berhasil diperbarui';
$lang['adm_user_success_delete'] = 'Pengguna berhasil dihapus';

// Order Management
$lang['adm_order_title'] = 'Manajemen Pesanan';
$lang['adm_order_view'] = 'Lihat Detail Pesanan';
$lang['adm_order_number'] = 'Nomor Pesanan';
$lang['adm_order_table'] = 'Meja';
$lang['adm_order_customer'] = 'Pelanggan';
$lang['adm_order_items'] = 'Item';
$lang['adm_order_total'] = 'Total';
$lang['adm_order_status'] = 'Status';
$lang['adm_order_date'] = 'Tanggal';
$lang['adm_order_payment_method'] = 'Metode Pembayaran';
$lang['adm_order_payment_status'] = 'Status Pembayaran';
$lang['adm_order_notes'] = 'Catatan';
$lang['adm_order_timeline'] = 'Linimasa';
$lang['adm_order_cancel'] = 'Batalkan Pesanan';
$lang['adm_order_refund'] = 'Pengembalian Dana';
$lang['adm_order_void'] = 'Void Pesanan';
$lang['adm_order_print_kitchen'] = 'Cetak ke Dapur';
$lang['adm_order_print_receipt'] = 'Cetak Struk';

// Reports
$lang['adm_report_title'] = 'Laporan';
$lang['adm_report_sales'] = 'Laporan Penjualan';
$lang['adm_report_inventory'] = 'Laporan Inventaris';
$lang['adm_report_customer'] = 'Laporan Pelanggan';
$lang['adm_report_employee'] = 'Laporan Karyawan';
$lang['adm_report_daily'] = 'Laporan Harian';
$lang['adm_report_weekly'] = 'Laporan Mingguan';
$lang['adm_report_monthly'] = 'Laporan Bulanan';
$lang['adm_report_yearly'] = 'Laporan Tahunan';
$lang['adm_report_custom_range'] = 'Rentang Khusus';
$lang['adm_report_export_pdf'] = 'Ekspor PDF';
$lang['adm_report_export_excel'] = 'Ekspor Excel';
$lang['adm_report_export_csv'] = 'Ekspor CSV';
$lang['adm_report_date_from'] = 'Dari Tanggal';
$lang['adm_report_date_to'] = 'Sampai Tanggal';
$lang['adm_report_generate'] = 'Generate Laporan';

// Settings
$lang['adm_settings_title'] = 'Pengaturan';
$lang['adm_settings_general'] = 'Umum';
$lang['adm_settings_restaurant_name'] = 'Nama Restoran';
$lang['adm_settings_address'] = 'Alamat';
$lang['adm_settings_phone'] = 'Telepon';
$lang['adm_settings_email'] = 'Email';
$lang['adm_settings_logo'] = 'Logo';
$lang['adm_settings_tax_rate'] = 'Tarif Pajak (%)';
$lang['adm_settings_service_rate'] = 'Biaya Layanan (%)';
$lang['adm_settings_currency'] = 'Mata Uang';
$lang['adm_settings_timezone'] = 'Zona Waktu';
$lang['adm_settings_language'] = 'Bahasa';
$lang['adm_settings_receipt_header'] = 'Header Struk';
$lang['adm_settings_receipt_footer'] = 'Footer Struk';
$lang['adm_settings_business_hours'] = 'Jam Operasional';
$lang['adm_settings_holiday_schedule'] = 'Jadwal Libur';
$lang['adm_settings_notification'] = 'Notifikasi';
$lang['adm_settings_email_config'] = 'Konfigurasi Email';
$lang['adm_settings_sms_config'] = 'Konfigurasi SMS';
$lang['adm_settings_printer'] = 'Printer';
$lang['adm_settings_backup'] = 'Backup Data';
$lang['adm_settings_system_log'] = 'Log Sistem';
$lang['adm_settings_success'] = 'Pengaturan berhasil disimpan';

// Activity Log
$lang['adm_log_title'] = 'Log Aktivitas';
$lang['adm_log_user'] = 'Pengguna';
$lang['adm_log_action'] = 'Aksi';
$lang['adm_log_description'] = 'Deskripsi';
$lang['adm_log_module'] = 'Modul';
$lang['adm_log_ip_address'] = 'Alamat IP';
$lang['adm_log_timestamp'] = 'Waktu';
$lang['adm_log_filter_date'] = 'Filter Tanggal';
$lang['adm_log_export'] = 'Ekspor Log';

// System
$lang['adm_sys_info'] = 'Informasi Sistem';
$lang['adm_sys_version'] = 'Versi';
$lang['adm_sys_php_version'] = 'Versi PHP';
$lang['adm_sys_database'] = 'Database';
$lang['adm_sys_server'] = 'Server';
$lang['adm_sys_disk_usage'] = 'Penggunaan Disk';
$lang['adm_sys_memory_usage'] = 'Penggunaan Memori';
$lang['adm_sys_cache'] = 'Cache';
$lang['adm_sys_clear_cache'] = 'Bersihkan Cache';
$lang['adm_sys_maintenance_mode'] = 'Mode Perawatan';
$lang['adm_sys_enable'] = 'Aktifkan';
$lang['adm_sys_disable'] = 'Nonaktifkan';

// Permissions
$lang['adm_permission_title'] = 'Manajemen Izin';
$lang['adm_permission_role'] = 'Peran';
$lang['adm_permission_module'] = 'Modul';
$lang['adm_permission_create'] = 'Buat';
$lang['adm_permission_read'] = 'Baca';
$lang['adm_permission_update'] = 'Perbarui';
$lang['adm_permission_delete'] = 'Hapus';
$lang['adm_permission_export'] = 'Ekspor';
$lang['adm_permission_import'] = 'Impor';
$lang['adm_permission_select_all'] = 'Pilih Semua';

// Messages
$lang['adm_msg_confirm_delete'] = 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.';
$lang['adm_msg_save_success'] = 'Data berhasil disimpan.';
$lang['adm_msg_update_success'] = 'Data berhasil diperbarui.';
$lang['adm_msg_delete_success'] = 'Data berhasil dihapus.';
$lang['adm_msg_error_occurred'] = 'Terjadi kesalahan. Silakan coba lagi.';
$lang['adm_msg_validation_error'] = 'Validasi gagal. Periksa kembali input Anda.';
$lang['adm_msg_duplicate_entry'] = 'Data sudah ada.';
$lang['adm_msg_foreign_key_constraint'] = 'Tidak dapat menghapus karena terdapat data terkait.';
$lang['adm_msg_upload_failed'] = 'Gagal mengunggah file.';
$lang['adm_msg_file_too_large'] = 'Ukuran file terlalu besar.';
$lang['adm_msg_invalid_file_type'] = 'Tipe file tidak valid.';
$lang['adm_msg_session_expired'] = 'Sesi telah berakhir. Silakan login kembali.';
$lang['adm_msg_unauthorized'] = 'Anda tidak memiliki izin untuk melakukan tindakan ini.';
$lang['adm_msg_maintenance_enabled'] = 'Mode perawatan diaktifkan. Hanya administrator yang dapat mengakses.';

// Quick Actions
$lang['adm_quick_add_item'] = 'Tambah Item Cepat';
$lang['adm_quick_add_table'] = 'Tambah Meja Cepat';
$lang['adm_quick_add_user'] = 'Tambah Pengguna Cepat';
$lang['adm_quick_view_orders'] = 'Lihat Pesanan';
$lang['adm_quick_view_reports'] = 'Lihat Laporan';

// Filters
$lang['adm_filter_all'] = 'Semua';
$lang['adm_filter_active'] = 'Aktif';
$lang['adm_filter_inactive'] = 'Nonaktif';
$lang['adm_filter_available'] = 'Tersedia';
$lang['adm_filter_unavailable'] = 'Tidak Tersedia';
$lang['adm_filter_today'] = 'Hari Ini';
$lang['adm_filter_this_week'] = 'Minggu Ini';
$lang['adm_filter_this_month'] = 'Bulan Ini';
$lang['adm_filter_this_year'] = 'Tahun Ini';
