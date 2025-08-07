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

/**
 * Debug function to test validation system
 */
function gfwcg_test_validation_system() {
	if (!current_user_can('manage_options')) {
		return;
	}
	
	echo "<div style='background: #f0f0f0; padding: 20px; margin: 20px; border: 1px solid #ccc;'>";
	echo "<h3>GFWCG Validation System Test</h3>";
	
	// Test if generator class exists
	if (class_exists('GFWCG_Generator')) {
		echo "<p style='color: green;'>✅ GFWCG_Generator class exists</p>";
		
		// Test generator instance
		$generator = GFWCG_Generator::get_instance();
		if ($generator) {
			echo "<p style='color: green;'>✅ Generator instance created successfully</p>";
		} else {
			echo "<p style='color: red;'>❌ Failed to create generator instance</p>";
		}
	} else {
		echo "<p style='color: red;'>❌ GFWCG_Generator class not found</p>";
	}
	
	// Test Gravity Forms hooks
	$hooks = array(
		'gform_field_validation',
		'gform_field_validation_message',
		'gform_pre_render',
		'gform_enqueue_scripts'
	);
	
	echo "<h4>Gravity Forms Hooks:</h4>";
	foreach ($hooks as $hook) {
		if (has_filter($hook)) {
			echo "<p style='color: green;'>✅ $hook</p>";
		} else {
			echo "<p style='color: red;'>❌ $hook</p>";
		}
	}
	
	// Test database
	global $wpdb;
	$table_name = $wpdb->prefix . 'gfwcg_generators';
	$generators = $wpdb->get_results("SELECT * FROM $table_name WHERE status = 'active' LIMIT 5");
	
	echo "<h4>Database Test:</h4>";
	if (empty($generators)) {
		echo "<p style='color: orange;'>⚠️ No active generators found</p>";
	} else {
		echo "<p style='color: green;'>✅ Found " . count($generators) . " active generator(s)</p>";
		
		foreach ($generators as $gen) {
			echo "<p>Generator ID: {$gen->id}, Form ID: {$gen->form_id}, Email Field: {$gen->email_field_id}</p>";
		}
	}
	
	echo "</div>";
}

/**
 * Simple validation debug function
 */
function gfwcg_debug_validation() {
	if (!current_user_can('manage_options')) {
		return;
	}
	
	echo "<div style='background: #f9f9f9; padding: 15px; margin: 10px; border: 1px solid #ddd;'>";
	echo "<h4>GFWCG Validation Debug</h4>";
	
	// Check class
	if (class_exists('GFWCG_Generator')) {
		echo "<p style='color: green;'>✅ Generator class exists</p>";
	} else {
		echo "<p style='color: red;'>❌ Generator class missing</p>";
	}
	
	// Check hooks
	$hooks = array('gform_field_validation_message', 'gform_enqueue_scripts');
	foreach ($hooks as $hook) {
		if (has_filter($hook)) {
			echo "<p style='color: green;'>✅ $hook registered</p>";
		} else {
			echo "<p style='color: red;'>❌ $hook missing</p>";
		}
	}
	
	// Check database
	global $wpdb;
	$count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gfwcg_generators WHERE status = 'active'");
	echo "<p>Active generators: $count</p>";
	
	echo "</div>";
}

// Add test function to admin footer if debug mode is enabled
add_action('admin_footer', function() {
	if (get_option('gfwcg_debug_mode') && current_user_can('manage_options')) {
		echo "<script>
		console.log('GFWCG Debug Mode Active');
		// Add test button to admin
		jQuery(document).ready(function($) {
			if ($('.wrap h1').length && $('.wrap h1').text().includes('Coupon Generators')) {
				$('.wrap h1').after('<button id=\"test-validation\" class=\"button\">Test Validation System</button>');
				$('#test-validation').on('click', function() {
					window.open('" . admin_url('admin-ajax.php') . "?action=gfwcg_test_validation', '_blank');
				});
			}
		});
		</script>";
	}
});

// AJAX handler for validation test
add_action('wp_ajax_gfwcg_test_validation', function() {
	if (!current_user_can('manage_options')) {
		wp_die('Unauthorized');
	}
	
	ob_start();
	gfwcg_test_validation_system();
	$output = ob_get_clean();
	
	wp_die($output, 'GFWCG Validation Test');
}); 

// Add debug info to admin footer
add_action('admin_footer', function() {
	if (current_user_can('manage_options') && isset($_GET['page']) && strpos($_GET['page'], 'gfwcg') !== false) {
		echo "<script>
		jQuery(document).ready(function($) {
			$('.wrap h1').after('<button id=\"debug-validation\" class=\"button\">Debug Validation</button>');
			$('#debug-validation').on('click', function() {
				window.open('" . admin_url('admin-ajax.php') . "?action=gfwcg_debug_validation', '_blank');
			});
		});
		</script>";
	}
});

// AJAX handler for debug
add_action('wp_ajax_gfwcg_debug_validation', function() {
	if (!current_user_can('manage_options')) {
		wp_die('Unauthorized');
	}
	
	ob_start();
	gfwcg_debug_validation();
	$output = ob_get_clean();
	
	wp_die($output, 'GFWCG Validation Debug');
}); 