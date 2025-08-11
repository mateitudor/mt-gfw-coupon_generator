<?php
/**
 * GFWCG Autoloader Class
 *
 * Centralized autoloader for all plugin files to eliminate redundant includes
 *
 * @package Gravity Forms WooCommerce Coupon Generator
 */

if (!defined('ABSPATH')) {
	exit;
}

class GFWCG_Autoloader {
	
	/**
	 * Plugin base directory
	 *
	 * @var string
	 */
	private $plugin_dir;
	
	/**
	 * Loaded files cache to prevent double includes
	 *
	 * @var array
	 */
	private $loaded_files = array();
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->plugin_dir = GFWCG_PLUGIN_DIR;
	}
	
	/**
	 * Load a class file
	 *
	 * @param string $class_name Class name
	 * @return bool Whether the class was loaded
	 */
	public function load_class($class_name) {
		$file_path = $this->get_class_file_path($class_name);
		
		if ($file_path && file_exists($file_path)) {
			if (!isset($this->loaded_files[$file_path])) {
				require_once $file_path;
				$this->loaded_files[$file_path] = true;
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Load a partial file
	 *
	 * @param string $partial_name Partial name (without .php extension)
	 * @return bool Whether the partial was loaded
	 */
	public function load_partial($partial_name) {
		$file_path = $this->plugin_dir . 'partials/' . $partial_name . '.php';
		
		if (file_exists($file_path)) {
			if (!isset($this->loaded_files[$file_path])) {
				require_once $file_path;
				$this->loaded_files[$file_path] = true;
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Load a view file
	 *
	 * @param string $view_name View name (without .php extension)
	 * @return bool Whether the view was loaded
	 */
	public function load_view($view_name) {
		$file_path = $this->plugin_dir . 'views/' . $view_name . '.php';
		
		if (file_exists($file_path)) {
			if (!isset($this->loaded_files[$file_path])) {
				require_once $file_path;
				$this->loaded_files[$file_path] = true;
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Load multiple files at once
	 *
	 * @param array $files Array of file paths or names
	 * @param string $type Type of files ('class', 'partial', 'view', or 'custom')
	 * @return array Array of successfully loaded files
	 */
	public function load_files($files, $type = 'custom') {
		$loaded = array();
		
		foreach ($files as $file) {
			$success = false;
			
			switch ($type) {
				case 'class':
					$success = $this->load_class($file);
					break;
				case 'partial':
					$success = $this->load_partial($file);
					break;
				case 'view':
					$success = $this->load_view($file);
					break;
				case 'custom':
				default:
					$success = $this->load_custom_file($file);
					break;
			}
			
			if ($success) {
				$loaded[] = $file;
			}
		}
		
		return $loaded;
	}
	
	/**
	 * Load a custom file by full path
	 *
	 * @param string $file_path Full file path
	 * @return bool Whether the file was loaded
	 */
	public function load_custom_file($file_path) {
		if (file_exists($file_path)) {
			if (!isset($this->loaded_files[$file_path])) {
				require_once $file_path;
				$this->loaded_files[$file_path] = true;
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Get the file path for a class
	 *
	 * @param string $class_name Class name
	 * @return string|false File path or false if not found
	 */
	private function get_class_file_path($class_name) {
		$class_map = array(
			'GFWCG_DB' => 'classes/class-gfwcg-db.php',
			'GFWCG_Admin' => 'classes/class-gfwcg-admin.php',
			'GFWCG_Generator' => 'classes/class-gfwcg-generator.php',
			'GFWCG_Coupon' => 'classes/class-gfwcg-coupon.php',
			'GFWCG_Email' => 'classes/class-gfwcg-email.php',
		);
		
		if (isset($class_map[$class_name])) {
			return $this->plugin_dir . $class_map[$class_name];
		}
		
		return false;
	}
	
	/**
	 * Load all core classes
	 *
	 * @return array Array of loaded classes
	 */
	public function load_core_classes() {
		$core_classes = array(
			'GFWCG_DB',
			'GFWCG_Admin',
			'GFWCG_Generator',
			'GFWCG_Coupon',
		);
		
		return $this->load_files($core_classes, 'class');
	}
	
	/**
	 * Load all admin views
	 *
	 * @return array Array of loaded views
	 */
	public function load_admin_views() {
		$admin_views = array(
			'admin-list',
			'admin-grid',
			'admin-single',
		);
		
		return $this->load_files($admin_views, 'view');
	}
	
	/**
	 * Load all partials
	 *
	 * @return array Array of loaded partials
	 */
	public function load_partials() {
		$partials = array(
			'admin-header',
			'admin-submenu',
			'admin-switcher',
			'gfwcg-actions',
			'gfwcg-shortcodes',
		);
		
		return $this->load_files($partials, 'partial');
	}
	
	/**
	 * Load all required files for admin functionality
	 *
	 * @return array Array of loaded files
	 */
	public function load_admin_files() {
		$loaded = array();
		
		// Load core classes (excluding email class which needs WooCommerce)
		$loaded['classes'] = $this->load_core_classes();
		
		// Load admin views
		$loaded['views'] = $this->load_admin_views();
		
		// Load partials
		$loaded['partials'] = $this->load_partials();
		
		return $loaded;
	}
	
	/**
	 * Load all required files for frontend functionality
	 *
	 * @return array Array of loaded files
	 */
	public function load_frontend_files() {
		$loaded = array();
		
		// Load core classes (excluding email class which needs WooCommerce)
		$loaded['classes'] = $this->load_core_classes();
		
		// Load shortcodes
		$loaded['shortcodes'] = $this->load_partial('gfwcg-shortcodes');
		
		return $loaded;
	}
	
	/**
	 * Check if a file has been loaded
	 *
	 * @param string $file_path File path
	 * @return bool Whether the file is loaded
	 */
	public function is_loaded($file_path) {
		return isset($this->loaded_files[$file_path]);
	}
	
	/**
	 * Get all loaded files
	 *
	 * @return array Array of loaded file paths
	 */
	public function get_loaded_files() {
		return array_keys($this->loaded_files);
	}
	
	/**
	 * Load email class after WooCommerce is loaded
	 *
	 * @return bool Whether the email class was loaded
	 */
	public function load_email_class() {
		// Only load if WooCommerce is available
		if (class_exists('WooCommerce') && class_exists('WC_Email')) {
			return $this->load_class('GFWCG_Email');
		}
		return false;
	}
	
	/**
	 * Clear loaded files cache
	 */
	public function clear_cache() {
		$this->loaded_files = array();
	}
}

// Initialize the autoloader
$gfwcg_autoloader = new GFWCG_Autoloader();

/**
 * Helper function to load a class
 *
 * @param string $class_name Class name
 * @return bool Whether the class was loaded
 */
function gfwcg_load_class($class_name) {
	global $gfwcg_autoloader;
	return $gfwcg_autoloader->load_class($class_name);
}

/**
 * Helper function to load a partial
 *
 * @param string $partial_name Partial name
 * @return bool Whether the partial was loaded
 */
function gfwcg_load_partial($partial_name) {
	global $gfwcg_autoloader;
	return $gfwcg_autoloader->load_partial($partial_name);
}

/**
 * Helper function to load a view
 *
 * @param string $view_name View name
 * @return bool Whether the view was loaded
 */
function gfwcg_load_view($view_name) {
	global $gfwcg_autoloader;
	return $gfwcg_autoloader->load_view($view_name);
}

/**
 * Helper function to load multiple files
 *
 * @param array $files Array of files
 * @param string $type File type
 * @return array Array of loaded files
 */
function gfwcg_load_files($files, $type = 'custom') {
	global $gfwcg_autoloader;
	return $gfwcg_autoloader->load_files($files, $type);
}

/**
 * Helper function to load email class
 *
 * @return bool Whether the email class was loaded
 */
function gfwcg_load_email_class() {
	global $gfwcg_autoloader;
	return $gfwcg_autoloader->load_email_class();
}