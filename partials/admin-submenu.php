<?php
/**
 * Admin Submenu Template
 *
 * @package Gravity_Forms_WooCommerce_Coupon_Generator
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Register admin submenu pages
 *
 * @param string $main_slug The main menu slug
 * @param callable $callback The callback function for the pages
 */
function gfwcg_register_admin_submenu($main_slug, $callback) {
	// List page
	add_submenu_page(
		$main_slug,
		__('Generators', 'gravity-forms-woocommerce-coupon-generator'),
		__('Generators', 'gravity-forms-woocommerce-coupon-generator'),
		'manage_options',
		$main_slug,
		$callback
	);

	// Add New page
	add_submenu_page(
		$main_slug,
		__('Add New Generator', 'gravity-forms-woocommerce-coupon-generator'),
		__('Add New', 'gravity-forms-woocommerce-coupon-generator'),
		'manage_options',
		'gfwcg-add-generator',
		$callback
	);
}

/**
 * Set the current admin submenu page
 *
 * @param string $action The current action
 */
function gfwcg_set_current_submenu($action) {
	global $plugin_page;
	
	if ($action === 'add') {
		$plugin_page = 'gfwcg-add-generator';
	} else {
		$plugin_page = 'gfwcg-generators';
	}
}