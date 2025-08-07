<?php
/**
 * Helper functions for GFWCG plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Check if debug mode is enabled
 */
function gfwcg_is_debug_enabled() {
	return get_option('gfwcg_debug_mode', false);
}

/**
 * Debug logging function
 */
function gfwcg_debug_log($message) {
	if (gfwcg_is_debug_enabled()) {
		error_log('GFWCG: ' . $message);
	}
} 