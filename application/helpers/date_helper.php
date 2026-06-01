<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Date Helper
 * 
 * Provides date formatting and manipulation functions
 * Specifically designed for Indonesian locale
 */

if (!function_exists('format_date_id')) {
    /**
     * Format date in Indonesian format
     * 
     * @param string $date Date string (any format accepted by strtotime)
     * @param string $format Output format (default: 'd F Y')
     * @return string Formatted date in Indonesian
     */
    function format_date_id($date, $format = 'd F Y')
    {
        $ci =& get_instance();
        $ci->load->helper('language');
        
        // Indonesian month names
        $months = [
            'January' => 'Januari',
            'February' => 'Februari',
            'March' => 'Maret',
            'April' => 'April',
            'May' => 'Mei',
            'June' => 'Juni',
            'July' => 'Juli',
            'August' => 'Agustus',
            'September' => 'September',
            'October' => 'Oktober',
            'November' => 'November',
            'December' => 'Desember'
        ];
        
        // Indonesian day names
        $days = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu'
        ];
        
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        
        if ($timestamp === FALSE) {
            return $date; // Return original if invalid
        }
        
        $english_format = date($format, $timestamp);
        
        // Replace English month and day names with Indonesian
        $indonesian_format = str_replace(
            array_keys(array_merge($months, $days)),
            array_values(array_merge($months, $days)),
            $english_format
        );
        
        return $indonesian_format;
    }
}

if (!function_exists('format_datetime_id')) {
    /**
     * Format datetime in Indonesian format with time
     * 
     * @param string $datetime Datetime string
     * @param bool $include_seconds Whether to include seconds (default: false)
     * @return string Formatted datetime in Indonesian
     */
    function format_datetime_id($datetime, $include_seconds = FALSE)
    {
        $format = $include_seconds ? 'd F Y H:i:s' : 'd F Y H:i';
        $formatted = format_date_id($datetime, $format);
        
        // Replace AM/PM if using 12-hour format
        $formatted = str_replace(['AM', 'PM'], ['WIB'], $formatted);
        
        return $formatted;
    }
}

if (!function_exists('time_ago')) {
    /**
     * Convert timestamp to "time ago" format
     * e.g., "5 menit yang lalu", "2 jam yang lalu"
     * 
     * @param string|int $datetime Datetime string or timestamp
     * @return string Human-readable time ago string
     */
    function time_ago($datetime)
    {
        $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
        
        if ($timestamp === FALSE) {
            return $datetime;
        }
        
        $diff = time() - $timestamp;
        
        if ($diff < 0) {
            return 'baru saja';
        }
        
        $periods = [
            31536000 => 'tahun',
            2592000 => 'bulan',
            604800 => 'minggu',
            86400 => 'hari',
            3600 => 'jam',
            60 => 'menit',
            1 => 'detik'
        ];
        
        foreach ($periods as $seconds => $label) {
            $count = floor($diff / $seconds);
            
            if ($count >= 1) {
                return $count . ' ' . $label . ' yang lalu';
            }
        }
        
        return 'baru saja';
    }
}

if (!function_exists('format_duration')) {
    /**
     * Format duration in seconds to human-readable format
     * e.g., "1j 2j 3m" or "2 jam 30 menit"
     * 
     * @param int $seconds Duration in seconds
     * @param bool $short_format Use short format (default: false)
     * @return string Formatted duration
     */
    function format_duration($seconds, $short_format = FALSE)
    {
        $seconds = max(0, (int)$seconds);
        
        $years = floor($seconds / 31536000);
        $seconds %= 31536000;
        
        $months = floor($seconds / 2592000);
        $seconds %= 2592000;
        
        $days = floor($seconds / 86400);
        $seconds %= 86400;
        
        $hours = floor($seconds / 3600);
        $seconds %= 3600;
        
        $minutes = floor($seconds / 60);
        $seconds %= 60;
        
        if ($short_format) {
            $parts = [];
            if ($years > 0) $parts[] = $years . 'th';
            if ($months > 0) $parts[] = $months . 'bl';
            if ($days > 0) $parts[] = $days . 'h';
            if ($hours > 0) $parts[] = $hours . 'j';
            if ($minutes > 0) $parts[] = $minutes . 'm';
            if ($seconds > 0 && empty($parts)) $parts[] = $seconds . 'd';
            
            return implode(' ', $parts) ?: '0d';
        } else {
            $parts = [];
            if ($years > 0) $parts[] = $years . ' tahun';
            if ($months > 0) $parts[] = $months . ' bulan';
            if ($days > 0) $parts[] = $days . ' hari';
            if ($hours > 0) $parts[] = $hours . ' jam';
            if ($minutes > 0) $parts[] = $minutes . ' menit';
            if ($seconds > 0 && empty($parts)) $parts[] = $seconds . ' detik';
            
            return implode(', ', $parts) ?: '0 detik';
        }
    }
}

if (!function_exists('is_today')) {
    /**
     * Check if a date is today
     * 
     * @param string $date Date to check
     * @return bool TRUE if date is today
     */
    function is_today($date)
    {
        return date('Y-m-d', strtotime($date)) === date('Y-m-d');
    }
}

if (!function_exists('is_yesterday')) {
    /**
     * Check if a date is yesterday
     * 
     * @param string $date Date to check
     * @return bool TRUE if date is yesterday
     */
    function is_yesterday($date)
    {
        return date('Y-m-d', strtotime($date)) === date('Y-m-d', strtotime('-1 day'));
    }
}

if (!function_exists('business_days_between')) {
    /**
     * Calculate business days between two dates (excluding weekends)
     * 
     * @param string $start_date Start date
     * @param string $end_date End date
     * @return int Number of business days
     */
    function business_days_between($start_date, $end_date)
    {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        
        if ($start > $end) {
            return 0;
        }
        
        $days = 0;
        $interval = new DateInterval('P1D');
        
        while ($start <= $end) {
            if ($start->format('N') < 6) { // Monday = 1, Friday = 5
                $days++;
            }
            $start->add($interval);
        }
        
        return $days;
    }
}

if (!function_exists('add_business_days')) {
    /**
     * Add business days to a date (skipping weekends)
     * 
     * @param string $date Start date
     * @param int $days Number of business days to add
     * @return string New date string
     */
    function add_business_days($date, $days)
    {
        $result = new DateTime($date);
        $added = 0;
        
        while ($added < $days) {
            $result->modify('+1 day');
            if ($result->format('N') < 6) { // Monday = 1, Friday = 5
                $added++;
            }
        }
        
        return $result->format('Y-m-d');
    }
}

if (!function_exists('get_indonesian_holidays')) {
    /**
     * Get list of Indonesian national holidays for a year
     * Note: This is a simplified version. In production, you might want to
     * fetch from an API or maintain a database of holidays.
     * 
     * @param int $year Year to get holidays for
     * @return array Array of holiday dates (YYYY-MM-DD format)
     */
    function get_indonesian_holidays($year)
    {
        // Fixed holidays
        $holidays = [
            "{$year}-01-01", // Tahun Baru
            "{$year}-05-01", // Hari Buruh
            "{$year}-06-01", // Hari Lahir Pancasila (varies)
            "{$year}-08-17", // Hari Kemerdekaan
            "{$year}-12-25", // Natal
        ];
        
        // Note: Islamic holidays vary each year based on lunar calendar
        // For accurate dates, integrate with a holiday API
        
        return $holidays;
    }
}

if (!function_exists('is_holiday')) {
    /**
     * Check if a date is a holiday
     * 
     * @param string $date Date to check
     * @return bool TRUE if date is a holiday
     */
    function is_holiday($date)
    {
        $timestamp = strtotime($date);
        $year = date('Y', $timestamp);
        $date_str = date('Y-m-d', $timestamp);
        
        $holidays = get_indonesian_holidays($year);
        
        return in_array($date_str, $holidays);
    }
}

if (!function_exists('next_business_day')) {
    /**
     * Get the next business day (skip weekends and holidays)
     * 
     * @param string $date Starting date
     * @return string Next business day date
     */
    function next_business_day($date = 'today')
    {
        $result = new DateTime($date);
        
        do {
            $result->modify('+1 day');
        } while ($result->format('N') >= 6 || is_holiday($result->format('Y-m-d')));
        
        return $result->format('Y-m-d');
    }
}
