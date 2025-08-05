<?php
/**
 * Helper Functions
 * 
 * @package Gravity Forms WooCommerce Coupon Generator
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Check if debug mode is enabled
 * 
 * @return bool
 */
function gfwcg_is_debug_enabled() {
	return get_option('gfwcg_debug_mode', 0) == 1;
}

/**
 * Log debug message if debug mode is enabled
 * 
 * @param string $message The message to log
 * @return void
 */
function gfwcg_debug_log($message) {
	if (gfwcg_is_debug_enabled()) {
		error_log('GFWCG: ' . $message);
	}
} 