<?php
/**
 * Plugin Name: Coupon Generator for Gravity Forms & WooCommerce
 * Plugin URI: https://mateitudor.com
 * Description: Generate WooCommerce coupon codes from Gravity Forms submissions
 * Version: 1.0.1
 * Author: Matei Tudor
 * Author URI: https://mateitudor.com
 * Text Domain: gravity-forms-woocommerce-coupon-generator
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * HPOS compatible: yes
 * License: Unlicense
 * License URI: https://unlicense.org/
 * 
 * @package GFWCG
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GFWCG_VERSION', '1.0.1');
define('GFWCG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GFWCG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GFWCG_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('GFWCG_PLUGIN_NAME', 'gravity-forms-woocommerce-coupon-generator');

// Load autoloader first
require_once GFWCG_PLUGIN_DIR . 'classes/class-gfwcg-autoloader.php';

/**
 * Load plugin textdomain
 */
function gfwcg_load_textdomain() {
	load_plugin_textdomain(
		'gravity-forms-woocommerce-coupon-generator',
		false,
		dirname(GFWCG_PLUGIN_BASENAME) . '/languages'
	);
}
add_action('plugins_loaded', 'gfwcg_load_textdomain');

/**
 * Get localized string with fallback
 *
 * @param string $text Text to translate
 * @param string $context Context for translation
 * @return string Translated text
 */
function gfwcg_get_text($text, $context = '') {
	if (!empty($context)) {
		return _x($text, $context, 'gravity-forms-woocommerce-coupon-generator');
	}
	return __($text, 'gravity-forms-woocommerce-coupon-generator');
}

/**
 * Echo localized string with fallback
 *
 * @param string $text Text to translate
 * @param string $context Context for translation
 */
function gfwcg_e($text, $context = '') {
	echo gfwcg_get_text($text, $context);
}

/**
 * Process product and category arrays from POST data
 *
 * @param array $post_data The POST data array
 * @return array Array with processed product and category data
 */
function gfwcg_process_product_category_arrays($post_data) {
	$processed = array(
		'product_ids' => array(),
		'exclude_product_ids' => array(),
		'product_categories' => array(),
		'exclude_product_categories' => array()
	);

	// Handle product IDs
	if (isset($post_data['product_ids']) && is_array($post_data['product_ids'])) {
		$processed['product_ids'] = array_map('intval', array_filter($post_data['product_ids']));
	}

	// Handle exclude product IDs
	if (isset($post_data['exclude_product_ids']) && is_array($post_data['exclude_product_ids'])) {
		$processed['exclude_product_ids'] = array_map('intval', array_filter($post_data['exclude_product_ids']));
	}

	// Handle product categories
	if (isset($post_data['product_categories']) && is_array($post_data['product_categories'])) {
		$processed['product_categories'] = array_map('intval', array_filter($post_data['product_categories']));
	}

	// Handle exclude product categories
	if (isset($post_data['exclude_product_categories']) && is_array($post_data['exclude_product_categories'])) {
		$processed['exclude_product_categories'] = array_map('intval', array_filter($post_data['exclude_product_categories']));
	}

	return $processed;
}

/**
 * Safely unserialize and validate array data
 *
 * @param string $serialized_data The serialized data
 * @param string $type The type of data ('product_ids', 'category_ids', etc.)
 * @return array|false The unserialized array or false if invalid
 */
function gfwcg_safe_unserialize($serialized_data, $type = '') {
	if (empty($serialized_data)) {
		return false;
	}

	$unserialized = maybe_unserialize($serialized_data);
	
	if (!is_array($unserialized) || empty($unserialized)) {
		return false;
	}

	// Validate array contents based on type
	switch ($type) {
		case 'product_ids':
		case 'exclude_products':
			// Ensure all values are integers
			$validated = array();
			foreach ($unserialized as $id) {
				if (is_numeric($id) && intval($id) > 0) {
					$validated[] = intval($id);
				}
			}
			return !empty($validated) ? $validated : false;
			
		case 'category_ids':
		case 'product_categories':
		case 'exclude_categories':
			// Ensure all values are integers
			$validated = array();
			foreach ($unserialized as $id) {
				if (is_numeric($id) && intval($id) > 0) {
					$validated[] = intval($id);
				}
			}
			return !empty($validated) ? $validated : false;
			
		default:
			// For unknown types, just return the array if it's not empty
			return $unserialized;
	}
}

/**
 * Get product data from serialized product IDs
 *
 * @param string $serialized_product_ids Serialized product IDs
 * @return array Array of product data with name, url, and id
 */
function gfwcg_get_products_from_ids($serialized_product_ids) {
	$product_ids = gfwcg_safe_unserialize($serialized_product_ids, 'product_ids');
	if (!$product_ids) {
		return array();
	}

	$products = array();
	foreach ($product_ids as $product_id) {
		$product = wc_get_product($product_id);
		if ($product) {
			$products[] = array(
				'name' => $product->get_name(),
				'url' => get_permalink($product->get_id()),
				'id' => $product->get_id()
			);
		}
	}

	return $products;
}

/**
 * Get category data from serialized category IDs
 *
 * @param string $serialized_category_ids Serialized category IDs
 * @return array Array of category data with name, url, and id
 */
function gfwcg_get_categories_from_ids($serialized_category_ids) {
	$category_ids = gfwcg_safe_unserialize($serialized_category_ids, 'category_ids');
	if (!$category_ids) {
		return array();
	}

	$categories = array();
	foreach ($category_ids as $category_id) {
		$category = get_term($category_id, 'product_cat');
		if ($category && !is_wp_error($category)) {
			$category_url = get_term_link($category, 'product_cat');
			$categories[] = array(
				'name' => $category->name,
				'url' => is_wp_error($category_url) ? '' : $category_url,
				'id' => $category->term_id
			);
		}
	}

	return $categories;
}

/**
 * Get the current view from the URL parameters
 *
 * @return string The current view (list, grid, or edit)
 */
function gfwcg_get_current_view() {
    $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
    return in_array($view, array('list', 'grid', 'edit')) ? $view : 'list';
}

// Check if Gravity Forms and WooCommerce are active
function gfwcg_check_dependencies() {
    if (!class_exists('GFForms') || !class_exists('WooCommerce')) {
        add_action('admin_notices', 'gfwcg_missing_dependencies_notice');
        return false;
    }
    return true;
}

function gfwcg_missing_dependencies_notice() {
    ?>
    <div class="error">
        <p><?php _e('Generator Cod de Reducere Gravity Forms WooCommerce necesită atât Gravity Forms cât și WooCommerce să fie instalate și activate.', 'gravity-forms-woocommerce-coupon-generator'); ?></p>
    </div>
    <?php
}

// Declare HPOS compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Initialize the plugin
function gfwcg_init() {
    if (!gfwcg_check_dependencies()) {
        return;
    }

    // Load all required files using autoloader
    global $gfwcg_autoloader;
    if ($gfwcg_autoloader && method_exists($gfwcg_autoloader, 'load_admin_files')) {
        $gfwcg_autoloader->load_admin_files();
        
        // Load email class after WooCommerce is fully loaded
        add_action('woocommerce_init', function() use ($gfwcg_autoloader) {
            if ($gfwcg_autoloader && method_exists($gfwcg_autoloader, 'load_email_class')) {
                $gfwcg_autoloader->load_email_class();
            }
        });
    }

    // Initialize components after WordPress is fully loaded
    add_action('init', function() {
        new GFWCG_Admin(GFWCG_PLUGIN_NAME, GFWCG_VERSION);
        new GFWCG_Generator();
        new GFWCG_Coupon();
    });
}
add_action('plugins_loaded', 'gfwcg_init');

// Deactivation hook
register_deactivation_hook(__FILE__, 'gfwcg_deactivate');
function gfwcg_deactivate() {
    // Clean up any temporary data if needed
    // Note: We don't delete tables on deactivation to preserve data
}

// Activation hook
register_activation_hook(__FILE__, 'gfwcg_activate');
function gfwcg_activate() {
    if (!gfwcg_check_dependencies()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Generator Cod de Reducere Gravity Forms WooCommerce necesită atât Gravity Forms cât și WooCommerce să fie instalate și activate.', 'gravity-forms-woocommerce-coupon-generator'));
    }

    // Ensure WordPress upgrade functions are loaded
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Create database tables
    global $gfwcg_autoloader;
    if ($gfwcg_autoloader && method_exists($gfwcg_autoloader, 'load_class')) {
        $gfwcg_autoloader->load_class('GFWCG_DB');
        if (class_exists('GFWCG_DB')) {
            GFWCG_DB::create_tables();
        }
    }
}

// Register the custom email class
add_filter('woocommerce_email_classes', 'register_gfwcg_email_class');
function register_gfwcg_email_class($email_classes) {
    // Load our custom email class using autoloader
    global $gfwcg_autoloader;
    if ($gfwcg_autoloader && method_exists($gfwcg_autoloader, 'load_email_class') && $gfwcg_autoloader->load_email_class()) {
        $email_classes['GFWCG_Email'] = new GFWCG_Email();
    }
    return $email_classes;
}



/**
 * Register REST API endpoints for blocks
 */
function gfwcg_register_rest_api() {
	register_rest_route('gfwcg/v1', '/generators', array(
		'methods' => 'GET',
		'callback' => 'gfwcg_get_generators_rest',
		'permission_callback' => '__return_true'
	));
}

add_action('rest_api_init', 'gfwcg_register_rest_api');
add_action('rest_api_init', 'gfwcg_cors_headers', 15);

function gfwcg_cors_headers() {
    // Remove default CORS headers sent by WordPress REST API
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    
    add_filter('rest_pre_serve_request', function($value) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
        return $value;
    });
}

/**
 * Add CORS headers for admin-ajax.php
 */
function gfwcg_admin_cors_headers() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, DELETE");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
}
add_action('admin_init', 'gfwcg_admin_cors_headers');
    


/**
 * REST API callback to get generators
 */
function gfwcg_get_generators_rest($request) {
    try {
        if (!class_exists('GFWCG_DB')) {
            return rest_ensure_response(array());
        }
        
        $db = new GFWCG_DB();
        $generators = $db->get_generators();
        
        $formatted_generators = array();
        foreach ($generators as $generator) {
            $formatted_generators[] = array(
                'id' => $generator->id,
                'title' => array('rendered' => $generator->title),
                'slug' => $generator->slug,
                'status' => $generator->status
            );
        }
        
        return rest_ensure_response($formatted_generators);
    } catch (Exception $e) {
        return rest_ensure_response(array());
    }
} 