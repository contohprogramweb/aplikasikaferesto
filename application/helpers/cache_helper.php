<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cache Helper untuk Sistem Manajemen Kafe & Resto
 * Sesuai SRS v4.0 Bab 7.3.2 dan NFR-PERF-15
 * 
 * Fitur:
 * - Set, get, delete cache dengan TTL
 * - Clear cache by group
 * - Key format: [group][identifier][hash]
 * - Default TTL: 3600 detik (1 jam)
 * - Logging cache hit/miss untuk debugging
 */

// ------------------------------------------------------------------------

/**
 * Set cache data dengan TTL
 * 
 * @param string $key Cache key
 * @param mixed $data Data yang akan di-cache (array, object, string, dll)
 * @param int $ttl Time To Live dalam detik (default: 3600 = 1 jam)
 * @return bool TRUE jika berhasil, FALSE jika gagal
 */
if (!function_exists('set_cache')) {
    function set_cache($key, $data, $ttl = 3600) {
        $ci = get_instance();
        $ci->load->driver('cache', ['adapter' => 'file', 'backup' => 'dummy']);
        
        // Log untuk debugging (opsional)
        log_message('debug', 'Cache SET: key=' . $key . ', ttl=' . $ttl . 's');
        
        return $ci->cache->save($key, $data, $ttl);
    }
}

// ------------------------------------------------------------------------

/**
 * Get cache data
 * 
 * @param string $key Cache key
 * @return mixed Data yang di-cache atau FALSE jika tidak ditemukan/expired
 */
if (!function_exists('get_cache')) {
    function get_cache($key) {
        $ci = get_instance();
        $ci->load->driver('cache', ['adapter' => 'file', 'backup' => 'dummy']);
        
        $data = $ci->cache->get($key);
        
        // Log untuk debugging (opsional)
        if ($data !== FALSE) {
            log_message('debug', 'Cache HIT: key=' . $key);
        } else {
            log_message('debug', 'Cache MISS: key=' . $key);
        }
        
        return $data;
    }
}

// ------------------------------------------------------------------------

/**
 * Delete cache by key
 * 
 * @param string $key Cache key
 * @return bool TRUE jika berhasil, FALSE jika gagal
 */
if (!function_exists('delete_cache')) {
    function delete_cache($key) {
        $ci = get_instance();
        $ci->load->driver('cache', ['adapter' => 'file', 'backup' => 'dummy']);
        
        // Log untuk debugging (opsional)
        log_message('debug', 'Cache DELETE: key=' . $key);
        
        return $ci->cache->delete($key);
    }
}

// ------------------------------------------------------------------------

/**
 * Clear cache by group
 * Group adalah prefix dari key, misal: 'menu', 'category'
 * 
 * @param string $group Nama group (e.g., 'menu', 'category')
 * @return int Jumlah cache yang dihapus
 */
if (!function_exists('clear_cache_group')) {
    function clear_cache_group($group) {
        $ci = get_instance();
        $ci->load->driver('cache', ['adapter' => 'file', 'backup' => 'dummy']);
        
        // Dapatkan semua file cache di direktori
        $cache_path = $ci->config->item('cache_path') ?: APPPATH . 'cache/';
        $deleted_count = 0;
        
        if (is_dir($cache_path)) {
            $files = glob($cache_path . '*');
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    // Baca isi file cache
                    $content = @file_get_contents($file);
                    
                    // Cek apakah key mengandung group prefix
                    // Format key CI3 file cache: ci_cache_[key]
                    if ($content !== FALSE) {
                        // Extract key dari serialized data
                        $pattern = '/ci_cache_' . preg_quote($group, '/') . '/i';
                        
                        if (preg_match($pattern, basename($file))) {
                            @unlink($file);
                            $deleted_count++;
                            log_message('debug', 'Cache group cleared: ' . basename($file));
                        }
                    }
                }
            }
        }
        
        log_message('debug', 'Cache CLEAR_GROUP: group=' . $group . ', deleted=' . $deleted_count);
        
        return $deleted_count;
    }
}

// ------------------------------------------------------------------------

/**
 * Generate cache key dengan format: [group][identifier][hash]
 * 
 * @param string $group Group name (e.g., 'menu', 'category')
 * @param string $identifier Identifier unik (e.g., menu ID, category ID)
 * @param array $additional_data Data tambahan untuk hash (opsional)
 * @return string Formatted cache key
 */
if (!function_exists('generate_cache_key')) {
    function generate_cache_key($group, $identifier = '', $additional_data = []) {
        // Buat hash dari identifier dan additional data
        $hash_input = $identifier . serialize($additional_data);
        $hash = substr(md5($hash_input), 0, 8);
        
        // Format: group_identifier_hash
        $key = $group;
        
        if (!empty($identifier)) {
            $key .= '_' . $identifier;
        }
        
        $key .= '_' . $hash;
        
        return $key;
    }
}

// ------------------------------------------------------------------------

/**
 * Warmup cache untuk menu dan kategori
 * Dipanggil saat aplikasi load pertama kali
 * 
 * @return bool TRUE jika berhasil
 */
if (!function_exists('warmup_cache')) {
    function warmup_cache() {
        $ci = get_instance();
        
        // Load model yang diperlukan
        $ci->load->model('menu_model');
        $ci->load->model('category_model');
        
        $warmed = FALSE;
        
        // Warmup menu cache
        $menu_key = 'menu_all';
        $menu_data = get_cache($menu_key);
        
        if ($menu_data === FALSE) {
            $menu_data = $ci->menu_model->get_all_available();
            if ($menu_data) {
                set_cache($menu_key, $menu_data, 3600);
                log_message('info', 'Cache warmed up: menu_all');
                $warmed = TRUE;
            }
        }
        
        // Warmup category cache
        $category_key = 'categories_all';
        $category_data = get_cache($category_key);
        
        if ($category_data === FALSE) {
            $category_data = $ci->category_model->get_all_active();
            if ($category_data) {
                set_cache($category_key, $category_data, 3600);
                log_message('info', 'Cache warmed up: categories_all');
                $warmed = TRUE;
            }
        }
        
        return $warmed;
    }
}

// ------------------------------------------------------------------------

/**
 * Get cache statistics untuk monitoring
 * 
 * @return array ['total_files' => x, 'total_size' => x, 'oldest' => x, 'newest' => x]
 */
if (!function_exists('get_cache_stats')) {
    function get_cache_stats() {
        $ci = get_instance();
        $cache_path = $ci->config->item('cache_path') ?: APPPATH . 'cache/';
        
        $stats = [
            'total_files' => 0,
            'total_size' => 0,
            'oldest' => null,
            'newest' => null,
            'groups' => []
        ];
        
        if (is_dir($cache_path)) {
            $files = glob($cache_path . '*');
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    $stats['total_files']++;
                    $stats['total_size'] += filesize($file);
                    
                    $mtime = filemtime($file);
                    
                    if ($stats['oldest'] === null || $mtime < $stats['oldest']) {
                        $stats['oldest'] = $mtime;
                    }
                    
                    if ($stats['newest'] === null || $mtime > $stats['newest']) {
                        $stats['newest'] = $mtime;
                    }
                    
                    // Extract group dari filename
                    if (preg_match('/ci_cache_([a-zA-Z]+)_/', basename($file), $matches)) {
                        $group = $matches[1];
                        if (!isset($stats['groups'][$group])) {
                            $stats['groups'][$group] = 0;
                        }
                        $stats['groups'][$group]++;
                    }
                }
            }
        }
        
        return $stats;
    }
}
