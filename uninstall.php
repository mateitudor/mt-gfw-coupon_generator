<?php
// Bail if not called by WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Read uninstall preferences
$delete_options = (int) get_option('gfwcg_delete_options_on_uninstall', 0) === 1;
$drop_tables = (int) get_option('gfwcg_drop_tables_on_uninstall', 0) === 1;

if ($delete_options) {
	// Remove plugin options
	delete_option('gfwcg_debug_mode');
	delete_option('gfwcg_delete_options_on_uninstall');
	delete_option('gfwcg_drop_tables_on_uninstall');
}

if ($drop_tables) {
	global $wpdb;
	$table = $wpdb->prefix . 'gfwcg_generators';
	$wpdb->query("DROP TABLE IF EXISTS {$table}");
}


