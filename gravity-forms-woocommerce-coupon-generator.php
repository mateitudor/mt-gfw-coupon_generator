<?php
/**
 * Plugin Name: Coupon Generator for Gravity Forms & WooCommerce
* Plugin URI: https://storiabooks.com
* Description: Generate WooCommerce coupon codes from Gravity Forms submissions
 * Version: 1.0.1
 * Author: Storia Books
 * Author URI: https://storiabooks.com
 * Text Domain: gravity-forms-woocommerce-coupon-generator
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * HPOS compatible: yes
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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
    $gfwcg_autoloader->load_admin_files();
    
    // Load email class after WooCommerce is fully loaded
    add_action('woocommerce_init', function() use ($gfwcg_autoloader) {
        $gfwcg_autoloader->load_email_class();
    });

    // Initialize components
    new GFWCG_Admin(GFWCG_PLUGIN_NAME, GFWCG_VERSION);
    new GFWCG_Generator();
    new GFWCG_Coupon();
    

}
add_action('plugins_loaded', 'gfwcg_init');

// Activation hook
register_activation_hook(__FILE__, 'gfwcg_activate');
function gfwcg_activate() {
    if (!gfwcg_check_dependencies()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Generator Cod de Reducere Gravity Forms WooCommerce necesită atât Gravity Forms cât și WooCommerce să fie instalate și activate.', 'gravity-forms-woocommerce-coupon-generator'));
    }

    // Create database tables
    global $gfwcg_autoloader;
    $gfwcg_autoloader->load_class('GFWCG_DB');
    GFWCG_DB::create_tables();
}

// Register the custom email class
add_filter('woocommerce_email_classes', 'register_gfwcg_email_class');
function register_gfwcg_email_class($email_classes) {
    // Load our custom email class using autoloader
    global $gfwcg_autoloader;
    if ($gfwcg_autoloader->load_email_class()) {
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