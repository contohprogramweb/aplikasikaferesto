<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Currency Helper
 * 
 * Provides currency formatting and conversion functions
 * Specifically designed for Indonesian Rupiah (IDR)
 */

if (!function_exists('format_currency')) {
    /**
     * Format a number as Indonesian Rupiah
     * 
     * @param float|int $amount The amount to format
     * @param bool $include_symbol Whether to include "Rp" symbol (default: true)
     * @param int $decimals Number of decimal places (default: 0 for IDR)
     * @return string Formatted currency string
     */
    function format_currency($amount, $include_symbol = TRUE, $decimals = 0)
    {
        $ci =& get_instance();
        
        // Get settings from config or use defaults
        $currency_symbol = $ci->config->item('currency_symbol') ?: 'Rp ';
        $thousands_separator = $ci->config->item('currency_thousands_separator') ?: '.';
        $decimal_separator = $ci->config->item('currency_decimal_separator') ?: ',';
        
        $formatted = number_format(
            (float)$amount,
            $decimals,
            $decimal_separator,
            $thousands_separator
        );
        
        return $include_symbol ? $currency_symbol . $formatted : $formatted;
    }
}

if (!function_exists('format_currency_short')) {
    /**
     * Format currency in short form (e.g., 1.5jt, 2.3m)
     * Useful for displaying large amounts compactly
     * 
     * @param float|int $amount The amount to format
     * @return string Short formatted currency string
     */
    function format_currency_short($amount)
    {
        $amount = (float)$amount;
        
        if ($amount >= 1000000000) {
            return 'Rp ' . number_format($amount / 1000000000, 1, ',', '.') . 'B';
        } elseif ($amount >= 1000000) {
            return 'Rp ' . number_format($amount / 1000000, 1, ',', '.') . 'jt';
        } elseif ($amount >= 1000) {
            return 'Rp ' . number_format($amount / 1000, 1, ',', '.') . 'rb';
        } else {
            return format_currency($amount);
        }
    }
}

if (!function_exists('parse_currency')) {
    /**
     * Parse a currency string back to a numeric value
     * Handles various input formats
     * 
     * @param string $string The currency string to parse
     * @return float|bool Parsed numeric value or FALSE on failure
     */
    function parse_currency($string)
    {
        if (empty($string)) {
            return FALSE;
        }
        
        // Remove currency symbols and whitespace
        $cleaned = preg_replace('/[^\d,\.-]/', '', $string);
        
        // Replace comma with dot for decimal
        $cleaned = str_replace(',', '.', $cleaned);
        
        // Remove any non-numeric characters except minus and dot
        $cleaned = preg_replace('/[^\d.-]/', '', $cleaned);
        
        $value = (float)$cleaned;
        
        return is_numeric($value) ? $value : FALSE;
    }
}

if (!function_exists('calculate_discount')) {
    /**
     * Calculate discount amount
     * 
     * @param float $subtotal The subtotal amount
     * @param float $discount_value The discount value (percentage or nominal)
     * @param string $type The discount type: 'percentage' or 'nominal'
     * @param float $max_discount Maximum discount allowed (optional)
     * @return array ['discount_amount' => float, 'final_amount' => float]
     */
    function calculate_discount($subtotal, $discount_value, $type = 'nominal', $max_discount = NULL)
    {
        $discount_amount = 0;
        
        if ($type === 'percentage') {
            $discount_amount = ($subtotal * $discount_value) / 100;
        } else {
            $discount_amount = (float)$discount_value;
        }
        
        // Ensure discount doesn't exceed subtotal
        if ($discount_amount > $subtotal) {
            $discount_amount = $subtotal;
        }
        
        // Apply max discount cap if specified
        if ($max_discount !== NULL && $discount_amount > $max_discount) {
            $discount_amount = $max_discount;
        }
        
        $final_amount = $subtotal - $discount_amount;
        
        // Ensure final amount is not negative
        if ($final_amount < 0) {
            $final_amount = 0;
        }
        
        return [
            'discount_amount' => round($discount_amount, 2),
            'final_amount' => round($final_amount, 2)
        ];
    }
}

if (!function_exists('validate_discount')) {
    /**
     * Validate discount parameters
     * 
     * @param float $subtotal The subtotal amount
     * @param float $discount_value The discount value
     * @param string $type The discount type
     * @return array ['valid' => bool, 'message' => string]
     */
    function validate_discount($subtotal, $discount_value, $type = 'nominal')
    {
        $ci =& get_instance();
        $ci->load->helper('language');
        
        // Check if values are numeric
        if (!is_numeric($subtotal) || !is_numeric($discount_value)) {
            return ['valid' => FALSE, 'message' => 'Nilai harus berupa angka'];
        }
        
        // Check for negative values
        if ($subtotal < 0 || $discount_value < 0) {
            return ['valid' => FALSE, 'message' => 'Nilai tidak boleh negatif'];
        }
        
        // Check if subtotal is zero
        if ($subtotal == 0) {
            return ['valid' => FALSE, 'message' => 'Subtotal tidak boleh nol'];
        }
        
        if ($type === 'percentage') {
            // Percentage should be between 0 and 100
            if ($discount_value < 0 || $discount_value > 100) {
                return ['valid' => FALSE, 'message' => 'Persentase diskon harus antara 0-100%'];
            }
            
            // Calculate actual discount amount
            $discount_amount = ($subtotal * $discount_value) / 100;
            
            if ($discount_amount > $subtotal) {
                return ['valid' => FALSE, 'message' => 'Diskon tidak boleh melebihi subtotal'];
            }
        } else {
            // Nominal discount should not exceed subtotal
            if ($discount_value > $subtotal) {
                return ['valid' => FALSE, 'message' => 'Diskon tidak boleh melebihi subtotal'];
            }
        }
        
        return ['valid' => TRUE, 'message' => 'Diskon valid'];
    }
}

if (!function_exists('round_currency')) {
    /**
     * Round currency to nearest acceptable denomination
     * For IDR, commonly rounded to nearest 100 or 1000
     * 
     * @param float $amount The amount to round
     * @param int $nearest Round to nearest this value (default: 100)
     * @param string $direction Rounding direction: 'up', 'down', 'nearest' (default: 'nearest')
     * @return float Rounded amount
     */
    function round_currency($amount, $nearest = 100, $direction = 'nearest')
    {
        $amount = (float)$amount;
        
        switch ($direction) {
            case 'up':
                return ceil($amount / $nearest) * $nearest;
            case 'down':
                return floor($amount / $nearest) * $nearest;
            case 'nearest':
            default:
                return round($amount / $nearest) * $nearest;
        }
    }
}

if (!function_exists('split_bill')) {
    /**
     * Split a bill among multiple people
     * 
     * @param float $total The total amount to split
     * @param int $people Number of people to split among
     * @return array ['per_person' => float, 'remainder' => float]
     */
    function split_bill($total, $people)
    {
        if ($people <= 0) {
            return ['per_person' => 0, 'remainder' => $total];
        }
        
        $per_person = floor(($total / $people) * 100) / 100;
        $remainder = $total - ($per_person * $people);
        
        return [
            'per_person' => $per_person,
            'remainder' => round($remainder, 2)
        ];
    }
}
