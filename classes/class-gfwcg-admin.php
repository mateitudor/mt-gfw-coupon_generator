<?php
/**
 * Admin Class
 *
 * @package Gravity Forms WooCommerce Coupon Generator
 */

if (!defined('ABSPATH')) {
	exit;
}

// Autoloader will handle file includes

/**
 * The admin-specific functionality of the plugin.
 *
 * @link	   https://example.com
 * @since	   1.0.0
 *
 * @package	   Gravity_Forms_WooCommerce_Coupon_Generator
 * @subpackage Gravity_Forms_WooCommerce_Coupon_Generator/admin
 */

class GFWCG_Admin {
	private $plugin_name;
	private $version;

	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('wp_ajax_gfwcg_get_form_fields', array($this, 'ajax_get_form_fields'));
		add_action('wp_ajax_gfwcg_save_generator', array($this, 'ajax_save_generator'));
		add_action('wp_ajax_gfwcg_delete_generator', array($this, 'ajax_delete_generator'));
		add_action('wp_ajax_gfwcg_search_products', array($this, 'ajax_search_products'));
		add_action('wp_ajax_gfwcg_search_categories', array($this, 'ajax_search_categories'));
		add_action('wp_ajax_gfwcg_search_tags', array($this, 'ajax_search_tags'));
		add_action('wp_ajax_gfwcg_get_form_validation_settings', array($this, 'ajax_get_form_validation_settings'));
		add_action('admin_init', array($this, 'admin_init'));
	}

	public function add_admin_menu() {
		$main_slug = 'gfwcg-generators';

		// Main menu
		add_menu_page(
			__('Gravity Forms WooCommerce Coupon Generator', 'gravity-forms-woocommerce-coupon-generator'),
			__('Coupon Generator', 'gravity-forms-woocommerce-coupon-generator'),
			'manage_options',
			$main_slug,
			array($this, 'display_admin_page'),
			'dashicons-tag',
			30
		);

		// List/Grid/Edit page
		add_submenu_page(
			$main_slug,
			__('Generators', 'gravity-forms-woocommerce-coupon-generator'),
			__('Generators', 'gravity-forms-woocommerce-coupon-generator'),
			'manage_options',
			$main_slug,
			array($this, 'display_admin_page')
		);

		// Add New page
		add_submenu_page(
			$main_slug,
			__('Add New Generator', 'gravity-forms-woocommerce-coupon-generator'),
			__('Add New', 'gravity-forms-woocommerce-coupon-generator'),
			'manage_options',
			'gfwcg-add-generator',
			array($this, 'display_add_page')
		);

		// Settings page
		add_submenu_page(
			$main_slug,
			__('Settings', 'gravity-forms-woocommerce-coupon-generator'),
			__('Settings', 'gravity-forms-woocommerce-coupon-generator'),
			'manage_options',
			'gfwcg-settings',
			array($this, 'display_settings_page')
		);
	}

	public function enqueue_styles($hook) {
		// Only load on our plugin pages
		if (strpos($hook, 'gfwcg-generators') === false && strpos($hook, 'gfwcg-add-generator') === false && strpos($hook, 'gfwcg-settings') === false) {
			return;
		}

		// Enqueue plugin admin styles
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url(dirname(__FILE__)) . 'assets/css/gfwcg-admin.css',
			array(),
			$this->version,
			'all'
		);

		// Enqueue WooCommerce admin styles for consistency
		if (class_exists('WooCommerce')) {
			wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION);
			// Enqueue WooCommerce color variables if available (since WC 8.6+)
			if (file_exists(WC()->plugin_path() . '/assets/css/admin/variables.css')) {
				wp_enqueue_style('woocommerce_admin_variables', WC()->plugin_url() . '/assets/css/admin/variables.css', array('woocommerce_admin_styles'), WC_VERSION);
			}
		}
	}

	public function enqueue_scripts($hook) {
		// Only load on our plugin pages
		if (strpos($hook, 'gfwcg-generators') === false && strpos($hook, 'gfwcg-add-generator') === false && strpos($hook, 'gfwcg-settings') === false) {
			return;
		}

		// Enqueue custom select component first
		wp_enqueue_script(
			'gfwcg-select',
			plugin_dir_url(dirname(__FILE__)) . 'assets/js/gfwcg-select.js',
			array(),
			$this->version,
			true
		);

		// Enqueue main generator form script
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url(dirname(__FILE__)) . 'assets/js/gfwcg-generator-form.js',
			array('jquery', 'gfwcg-select'),
			$this->version,
			true
		);

		// Localize script
		wp_localize_script($this->plugin_name, 'gfwcgAdmin', array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('gfwcg_admin_nonce'),
			'selectFieldText' => __('Select a field', 'gravity-forms-woocommerce-coupon-generator'),
			'confirmDeleteText' => __('Are you sure you want to delete this generator?', 'gravity-forms-woocommerce-coupon-generator'),
			'errorText' => __('An error occurred. Please try again.', 'gravity-forms-woocommerce-coupon-generator'),
			'requiredFieldsText' => __('Please fill in all required fields.', 'gravity-forms-woocommerce-coupon-generator')
		));
	}

	/**
	 * Display the admin page
	 */
	public function display_admin_page() {
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'gravity-forms-woocommerce-coupon-generator'));
		}

		// Views are loaded by autoloader

		// Get the current view and ID
		$view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
		$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

		// Set the current submenu
		gfwcg_set_current_submenu('list');

		// Display the appropriate view
		switch ($view) {
			case 'edit':
				if (!$id) {
					wp_die(__('Invalid generator ID.', 'gravity-forms-woocommerce-coupon-generator'));
				}
				$generator = GFWCG_DB::get_generator($id);
				if ($generator) {
					gfwcg_display_generator_form($generator);
				}
				break;

			case 'add':
				gfwcg_display_generator_form();
				break;

			case 'grid':
				$generators = GFWCG_DB::get_generators();
				gfwcg_display_grid_view($generators);
				break;

			default:
				$generators = GFWCG_DB::get_generators();
				gfwcg_display_list_view($generators);
				break;
		}
	}

	public function display_add_page() {
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'gravity-forms-woocommerce-coupon-generator'));
		}

		// Set the current submenu
		gfwcg_set_current_submenu('add');

		// Pre-generate a new ID and pass a stub generator so the form shows the ID
		$fake = (object) array(
			'id' => GFWCG_DB::get_next_available_id(),
			'form_id' => 0,
			'email_field_id' => 0,
			'name_field_id' => 0,
			'coupon_type' => 'random',
			'coupon_length' => 8,
			'coupon_prefix' => '',
			'coupon_suffix' => '',
			'coupon_separator' => '',
			'discount_type' => 'percentage',
			'discount_amount' => 0,
			'individual_use' => 0,
			'usage_limit_per_coupon' => 0,
			'usage_limit_per_user' => 0,
			'allow_free_shipping' => 0,
			'exclude_sale_items' => 0,
			'expiry_days' => 0,
			'send_email' => 0,
			'use_wc_email_template' => 1,
			'email_subject' => '',
			'email_message' => '',
			'email_from_name' => '',
			'email_from_email' => '',
			'description' => '',
			'frontend_template' => ''
		);
		gfwcg_display_generator_form($fake);
	}

	public function display_settings_page() {
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'gravity-forms-woocommerce-coupon-generator'));
		}

		// Set the current submenu
		gfwcg_set_current_submenu('settings');

		// Display the settings page
		require_once plugin_dir_path(dirname(__FILE__)) . 'views/admin-settings.php';
	}

	private function display_generator_form($id = 0) {
		$generator = $id ? $this->get_generator($id) : null;
		gfwcg_display_generator_form($generator);
	}

	private function display_generators_list() {
		$generators = GFWCG_DB::get_generators();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e('Coupon Generators', 'gravity-forms-woocommerce-coupon-generator'); ?></h1>
			<a href="<?php echo esc_url(admin_url('admin.php?page=gfwcg-add-generator')); ?>" class="page-title-action">
				<?php _e('Add New', 'gravity-forms-woocommerce-coupon-generator'); ?>
			</a>
			<?php gfwcg_display_view_switcher(); ?>
			<hr class="wp-header-end">

			<?php gfwcg_display_list_view($generators); ?>
		</div>
		<?php
	}

	private function display_generators_grid() {
		$generators = GFWCG_DB::get_generators();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e('Coupon Generators', 'gravity-forms-woocommerce-coupon-generator'); ?></h1>
			<a href="<?php echo esc_url(admin_url('admin.php?page=gfwcg-add-generator')); ?>" class="page-title-action">
				<?php _e('Add New', 'gravity-forms-woocommerce-coupon-generator'); ?>
			</a>
			<?php gfwcg_display_view_switcher(); ?>
			<hr class="wp-header-end">

			<?php gfwcg_display_grid_view($generators); ?>
		</div>
		<?php
	}

	public function ajax_get_form_fields() {
		check_ajax_referer('gfwcg_admin_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'gravity-forms-woocommerce-coupon-generator')));
		}

		$form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
		if (!$form_id) {
			wp_send_json_error(array('message' => __('Invalid form ID.', 'gravity-forms-woocommerce-coupon-generator')));
		}

		$form = GFAPI::get_form($form_id);
		if (!$form) {
			wp_send_json_error(array('message' => __('Form not found.', 'gravity-forms-woocommerce-coupon-generator')));
		}

		$fields = $this->get_form_fields($form);
		if (empty($fields)) {
			wp_send_json_error(array('message' => __('No valid fields found in this form.', 'gravity-forms-woocommerce-coupon-generator')));
		}

		wp_send_json_success(array('fields' => $fields));
	}

	private function get_form_fields($form) {
		$fields = array();
		if (!is_array($form) || !isset($form['fields'])) {
			return $fields;
		}

		foreach ($form['fields'] as $field) {
			// Skip fields that don't have an ID or label
			if (!isset($field->id) || !isset($field->label)) {
				continue;
			}

			// Handle different field types
			switch ($field->type) {
				case 'email':
				case 'text':
				case 'name':
				case 'hidden':
					$fields[] = array(
						'id' => $field->id,
						'label' => $field->label,
						'type' => $field->type,
						'required' => isset($field->isRequired) ? (bool)$field->isRequired : false
					);
					break;
			}
		}

		return $fields;
	}

	public function ajax_save_generator() {
		try {
			check_ajax_referer('gfwcg_admin_nonce', 'nonce');

			if (!current_user_can('manage_options')) {
				wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'gravity-forms-woocommerce-coupon-generator')));
			}

			// Debug: Log the incoming data
			gfwcg_debug_log('AJAX save generator called with POST data: ' . print_r($_POST, true));

			// Process product, category, and tag arrays
			$processed_arrays = gfwcg_process_product_category_arrays($_POST);
			$product_ids = $processed_arrays['product_ids'];
			$exclude_product_ids = $processed_arrays['exclude_product_ids'];
			$product_categories = $processed_arrays['product_categories'];
			$exclude_product_categories = $processed_arrays['exclude_product_categories'];
			$product_tags = $processed_arrays['product_tags'];
			$exclude_product_tags = $processed_arrays['exclude_product_tags'];
			$product_tags = $processed_arrays['product_tags'];
			$exclude_product_tags = $processed_arrays['exclude_product_tags'];

			$data = array(
				'title' => isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '',
				'form_id' => isset($_POST['form_id']) ? intval($_POST['form_id']) : 0,
				'email_field_id' => isset($_POST['email_field_id']) ? intval($_POST['email_field_id']) : 0,
				'name_field_id' => isset($_POST['name_field_id']) ? intval($_POST['name_field_id']) : 0,
				'coupon_type' => isset($_POST['coupon_type']) ? sanitize_text_field($_POST['coupon_type']) : 'random',
				'coupon_field_id' => isset($_POST['coupon_field_id']) ? intval($_POST['coupon_field_id']) : 0,
				'coupon_length' => isset($_POST['coupon_length']) ? intval($_POST['coupon_length']) : 8,
				'coupon_prefix' => isset($_POST['coupon_prefix']) ? sanitize_text_field($_POST['coupon_prefix']) : '',
				'coupon_suffix' => isset($_POST['coupon_suffix']) ? sanitize_text_field($_POST['coupon_suffix']) : '',
				'coupon_separator' => isset($_POST['coupon_separator']) ? sanitize_text_field($_POST['coupon_separator']) : '',
				'discount_type' => isset($_POST['discount_type']) ? sanitize_text_field($_POST['discount_type']) : 'percentage',
				'discount_amount' => isset($_POST['discount_amount']) ? floatval($_POST['discount_amount']) : 0,
				'individual_use' => isset($_POST['individual_use']) ? 1 : 0,
				'usage_limit_per_coupon' => isset($_POST['usage_limit_per_coupon']) && $_POST['usage_limit_per_coupon'] !== '' ? intval($_POST['usage_limit_per_coupon']) : 0,
				'usage_limit_per_user' => isset($_POST['usage_limit_per_user']) && $_POST['usage_limit_per_user'] !== '' ? intval($_POST['usage_limit_per_user']) : 0,
				'minimum_amount' => isset($_POST['minimum_amount']) && $_POST['minimum_amount'] !== '' ? floatval($_POST['minimum_amount']) : 0,
				'maximum_amount' => isset($_POST['maximum_amount']) && $_POST['maximum_amount'] !== '' ? floatval($_POST['maximum_amount']) : 0,
				'exclude_sale_items' => isset($_POST['exclude_sale_items']) ? 1 : 0,
				'allow_free_shipping' => isset($_POST['allow_free_shipping']) ? 1 : 0,
				'expiry_days' => isset($_POST['expiry_days']) ? intval($_POST['expiry_days']) : 0,
				'send_email' => isset($_POST['send_email']) ? 1 : 0,
				'use_wc_email_template' => isset($_POST['use_wc_email_template']) ? 1 : 0,
				'email_subject' => isset($_POST['email_subject']) ? sanitize_text_field($_POST['email_subject']) : '',
				'email_message' => isset($_POST['email_message']) ? wp_kses_post($_POST['email_message']) : '',
				'email_from_name' => isset($_POST['email_from_name']) ? sanitize_text_field($_POST['email_from_name']) : '',
				'email_from_email' => isset($_POST['email_from_email']) ? sanitize_email($_POST['email_from_email']) : '',
				'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
				'frontend_template' => isset($_POST['frontend_template']) ? wp_kses_post($_POST['frontend_template']) : '',
				'validation_required_message' => isset($_POST['validation_required_message']) ? sanitize_text_field($_POST['validation_required_message']) : '',
				'validation_email_message' => isset($_POST['validation_email_message']) ? sanitize_text_field($_POST['validation_email_message']) : '',
				'validation_duplicate_message' => isset($_POST['validation_duplicate_message']) ? sanitize_text_field($_POST['validation_duplicate_message']) : '',
				'validation_error_header' => isset($_POST['validation_error_header']) ? sanitize_text_field($_POST['validation_error_header']) : '',
				'product_ids' => !empty($product_ids) ? serialize($product_ids) : null,
				'exclude_products' => !empty($exclude_product_ids) ? serialize($exclude_product_ids) : null,
				'product_categories' => !empty($product_categories) ? serialize($product_categories) : null,
				'exclude_categories' => !empty($exclude_product_categories) ? serialize($exclude_product_categories) : null,
				'product_tags' => !empty($product_tags) ? serialize($product_tags) : null,
				'exclude_product_tags' => !empty($exclude_product_tags) ? serialize($exclude_product_tags) : null,
				'product_tags' => !empty($product_tags) ? serialize($product_tags) : null,
				'exclude_product_tags' => !empty($exclude_product_tags) ? serialize($exclude_product_tags) : null,
				'status' => 'active'
			);

			// Debug: Log the processed data
			gfwcg_debug_log('Processed data for saving: ' . print_r($data, true));

			if (empty($data['title'])) {
				wp_send_json_error(array('message' => __('Title is required.', 'gravity-forms-woocommerce-coupon-generator')));
			}

			if (!$data['form_id'] || !$data['email_field_id']) {
				wp_send_json_error(array('message' => __('Form ID and Email Field are required.', 'gravity-forms-woocommerce-coupon-generator')));
			}

			if (isset($_POST['id']) && $_POST['id']) {
				$data['id'] = intval($_POST['id']);
			}

			// Ensure database is migrated to include validation message columns
			GFWCG_DB::migrate_database();

			$result = GFWCG_DB::save_generator($data);

			if ($result === false) {
				wp_send_json_error(array('message' => __('Error saving generator.', 'gravity-forms-woocommerce-coupon-generator')));
			}

			// Debug: Log the saved data
			gfwcg_debug_log('Generator saved with validation messages:');
			gfwcg_debug_log('Required: ' . ($data['validation_required_message'] ?? 'not set'));
			gfwcg_debug_log('Email: ' . ($data['validation_email_message'] ?? 'not set'));
			gfwcg_debug_log('Duplicate: ' . ($data['validation_duplicate_message'] ?? 'not set'));

			wp_send_json_success(array(
				'message' => __('Generator saved successfully.', 'gravity-forms-woocommerce-coupon-generator'),
				'redirect_url' => admin_url('admin.php?page=gfwcg-generators&view=edit&id=' . intval($result)),
				'generator_id' => $result
			));

		} catch (Exception $e) {
			gfwcg_debug_log('Error in ajax_save_generator: ' . $e->getMessage());
			gfwcg_debug_log('Stack trace: ' . $e->getTraceAsString());
			wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
		} catch (Error $e) {
			gfwcg_debug_log('Fatal error in ajax_save_generator: ' . $e->getMessage());
			gfwcg_debug_log('Stack trace: ' . $e->getTraceAsString());
			wp_send_json_error(array('message' => 'Fatal error: ' . $e->getMessage()));
		}
	}

	public function ajax_delete_generator() {
		check_ajax_referer('gfwcg_admin_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'gravity-forms-woocommerce-coupon-generator')));
		}

		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		if (!$id) {
			wp_send_json_error(array('message' => __('Invalid generator ID.', 'gravity-forms-woocommerce-coupon-generator')));
		}

		$result = GFWCG_DB::delete_generator($id);

		if ($result === false) {
			wp_send_json_error(array('message' => __('Error deleting generator.', 'gravity-forms-woocommerce-coupon-generator')));
		}

		wp_send_json_success(array('message' => __('Generator deleted successfully.', 'gravity-forms-woocommerce-coupon-generator')));
	}

	/**
	 * Generic AJAX search handler
	 */
	private function ajax_search_generic($search_type) {
		// Check nonce - try both POST and GET
		$nonce_valid = false;
		if (isset($_POST['security']) && wp_verify_nonce($_POST['security'], 'gfwcg_admin_nonce')) {
			$nonce_valid = true;
		} elseif (isset($_GET['security']) && wp_verify_nonce($_GET['security'], 'gfwcg_admin_nonce')) {
			$nonce_valid = true;
		}

		if (!$nonce_valid) {
			wp_send_json_error('Invalid nonce');
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error('Permission denied');
		}

		// Get search term from either POST or GET
		$term = '';
		if (isset($_POST['term'])) {
			$term = sanitize_text_field($_POST['term']);
		} elseif (isset($_GET['term'])) {
			$term = sanitize_text_field($_GET['term']);
		}

		$results = array();

		if ($search_type === 'products') {
			// If no term provided, return recent products for preload
			if (empty($term)) {
				$args = array(
					'post_type' => 'product',
					'post_status' => 'publish',
					'posts_per_page' => 20,
					'orderby' => 'date',
					'order' => 'DESC'
				);
			} else {
				// Search for products
				$args = array(
					'post_type' => 'product',
					'post_status' => 'publish',
					'posts_per_page' => 20,
					's' => $term
				);
			}

			$query = new WP_Query($args);

			if ($query->have_posts()) {
				while ($query->have_posts()) {
					$query->the_post();
					$product = wc_get_product(get_the_ID());
					if ($product) {
						$results[$product->get_id()] = $product->get_name();
					}
				}
			}

			wp_reset_postdata();
		} elseif ($search_type === 'categories') {
			// If no term provided, return all categories for preload
			if (empty($term)) {
				$args = array(
					'taxonomy' => 'product_cat',
					'hide_empty' => false,
					'number' => 100,
					'orderby' => 'name',
					'order' => 'ASC'
				);
			} else {
				// Search for categories
				$args = array(
					'taxonomy' => 'product_cat',
					'hide_empty' => false,
					'number' => 100,
					'orderby' => 'name',
					'order' => 'ASC',
					'name__like' => $term
				);
			}

			$terms = get_terms($args);

			if (!is_wp_error($terms) && !empty($terms)) {
				foreach ($terms as $term_obj) {
					$category_path = $this->get_category_hierarchy($term_obj->term_id);
					$results[$term_obj->term_id] = $category_path;
				}
			}
		} elseif ($search_type === 'tags') {
			// Product tags search
			if (empty($term)) {
				$args = array(
					'taxonomy' => 'product_tag',
					'hide_empty' => false,
					'number' => 100,
					'orderby' => 'name',
					'order' => 'ASC'
				);
			} else {
				$args = array(
					'taxonomy' => 'product_tag',
					'hide_empty' => false,
					'number' => 100,
					'orderby' => 'name',
					'order' => 'ASC',
					'name__like' => $term
				);
			}

			$terms = get_terms($args);
			if (!is_wp_error($terms) && !empty($terms)) {
				foreach ($terms as $term_obj) {
					$results[$term_obj->term_id] = $term_obj->name;
				}
			}
		}

		wp_send_json($results);
	}

	/**
	 * AJAX handler for product search
	 */
	public function ajax_search_products() {
		$this->ajax_search_generic('products');
	}

		/**
	 * AJAX handler for searching product categories
	 */
	public function ajax_search_categories() {
		$this->ajax_search_generic('categories');
	}

	/**
	 * AJAX handler for product tags search
	 */
	public function ajax_search_tags() {
		$this->ajax_search_generic('tags');
	}

	/**
	 * AJAX handler for getting form validation settings
	 */
	public function ajax_get_form_validation_settings() {
		check_ajax_referer('gfwcg_admin_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'gravity-forms-woocommerce-coupon-generator')));
		}

		$form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;

		if (!$form_id) {
			wp_send_json_error(array('message' => __('Invalid form ID.', 'gravity-forms-woocommerce-coupon-generator')));
		}

		// Get the form
		$form = GFAPI::get_form($form_id);
		if (!$form) {
			wp_send_json_error(array('message' => __('Form not found.', 'gravity-forms-woocommerce-coupon-generator')));
		}

		$validation_settings = array(
			'required_message' => '',
			'email_message' => '',
			'duplicate_message' => '',
			'error_header' => ''
		);

		// Check for email fields and their validation settings
		foreach ($form['fields'] as $field) {
			if ($field['type'] === 'email') {
				// Check for required message
				if (!empty($field['requiredMessage'])) {
					$validation_settings['required_message'] = $field['requiredMessage'];
				}

				// Check for email validation message
				if (!empty($field['emailMessage'])) {
					$validation_settings['email_message'] = $field['emailMessage'];
				}

				// Check for duplicate message
				if (!empty($field['errorMessage'])) {
					$validation_settings['duplicate_message'] = $field['errorMessage'];
				}

				// Also check for noDuplicates setting
				if (isset($field['noDuplicates']) && $field['noDuplicates'] && !empty($field['errorMessage'])) {
					$validation_settings['duplicate_message'] = $field['errorMessage'];
				}
			}
		}

		// Check if we found any validation settings
		$has_settings = !empty($validation_settings['required_message']) ||
					   !empty($validation_settings['email_message']) ||
					   !empty($validation_settings['duplicate_message']);

		if ($has_settings) {
			wp_send_json_success($validation_settings);
		} else {
			wp_send_json_error(array('message' => __('No validation settings found for this form.', 'gravity-forms-woocommerce-coupon-generator')));
		}
	}

	/**
	 * Build hierarchical category path
	 */
	private function get_category_hierarchy($term_id) {
		$term = get_term($term_id, 'product_cat');
		if (!$term || is_wp_error($term)) {
			return '';
		}

		$hierarchy = array($term->name);
		$parent_id = $term->parent;

		while ($parent_id > 0) {
			$parent = get_term($parent_id, 'product_cat');
			if ($parent && !is_wp_error($parent)) {
				array_unshift($hierarchy, $parent->name);
				$parent_id = $parent->parent;
			} else {
				break;
			}
		}

		return implode(' > ', $hierarchy);
	}

	private function get_generator($id) {
		return GFWCG_DB::get_generator($id);
	}

	public function admin_init() {
		// Migrate database to remove duplicate columns
		GFWCG_DB::migrate_database();

		// Verify table structure
		GFWCG_DB::verify_table_structure();

		// Register settings
		register_setting('gfwcg_settings', 'gfwcg_debug_mode');
		register_setting('gfwcg_settings', 'gfwcg_delete_options_on_uninstall');
		register_setting('gfwcg_settings', 'gfwcg_drop_tables_on_uninstall');
	}

	/**
	 * Handle form submission
	 */
	private function handle_form_submission() {
		if (!isset($_POST['submit']) || !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gfwcg_admin_nonce')) {
			return;
		}

		// Get form data
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		$title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
		$form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
		$email_field_id = isset($_POST['email_field_id']) ? intval($_POST['email_field_id']) : 0;
		$coupon_type = isset($_POST['coupon_type']) ? sanitize_text_field($_POST['coupon_type']) : '';
		$discount_type = isset($_POST['discount_type']) ? sanitize_text_field($_POST['discount_type']) : '';
		$discount_amount = isset($_POST['discount_amount']) ? floatval($_POST['discount_amount']) : 0;
		$coupon_length = isset($_POST['coupon_length']) ? intval($_POST['coupon_length']) : 8;
		$coupon_prefix = isset($_POST['coupon_prefix']) ? sanitize_text_field($_POST['coupon_prefix']) : '';
		$coupon_suffix = isset($_POST['coupon_suffix']) ? sanitize_text_field($_POST['coupon_suffix']) : '';
		$coupon_separator = isset($_POST['coupon_separator']) ? sanitize_text_field($_POST['coupon_separator']) : '';
		$individual_use = isset($_POST['individual_use']) ? 1 : 0;
		$usage_limit_per_coupon = isset($_POST['usage_limit_per_coupon']) && $_POST['usage_limit_per_coupon'] !== '' ? intval($_POST['usage_limit_per_coupon']) : 0;
		$usage_limit_per_user = isset($_POST['usage_limit_per_user']) && $_POST['usage_limit_per_user'] !== '' ? intval($_POST['usage_limit_per_user']) : 0;
		$minimum_amount = isset($_POST['minimum_amount']) && $_POST['minimum_amount'] !== '' ? floatval($_POST['minimum_amount']) : 0;
		$maximum_amount = isset($_POST['maximum_amount']) && $_POST['maximum_amount'] !== '' ? floatval($_POST['maximum_amount']) : 0;
		$exclude_sale_items = isset($_POST['exclude_sale_items']) ? 1 : 0;
		$allow_free_shipping = isset($_POST['allow_free_shipping']) ? 1 : 0;
		$expiry_days = isset($_POST['expiry_days']) ? intval($_POST['expiry_days']) : 0;
		$send_email = isset($_POST['send_email']) ? 1 : 0;
		$email_subject = isset($_POST['email_subject']) ? sanitize_text_field($_POST['email_subject']) : '';
		$email_message = isset($_POST['email_message']) ? wp_kses_post($_POST['email_message']) : '';
		$email_from_name = isset($_POST['email_from_name']) ? sanitize_text_field($_POST['email_from_name']) : '';
		$email_from_email = isset($_POST['email_from_email']) ? sanitize_email($_POST['email_from_email']) : '';
		$description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';

		// Process product and category arrays
		$processed_arrays = gfwcg_process_product_category_arrays($_POST);
		$product_ids = $processed_arrays['product_ids'];
		$exclude_product_ids = $processed_arrays['exclude_product_ids'];
		$product_categories = $processed_arrays['product_categories'];
		$exclude_product_categories = $processed_arrays['exclude_product_categories'];
		$product_tags = $processed_arrays['product_tags'];
		$exclude_product_tags = $processed_arrays['exclude_product_tags'];

		// Validate required fields
		if (empty($title) || empty($form_id) || empty($email_field_id) || empty($coupon_type) || empty($discount_type) || empty($discount_amount)) {
			add_settings_error('gfwcg_admin', 'gfwcg_admin_error', __('Please fill in all required fields.', 'gravity-forms-woocommerce-coupon-generator'), 'error');
			return;
		}

		// Prepare data
		$data = array(
			'title' => $title,
			'form_id' => $form_id,
			'email_field_id' => $email_field_id,
			'coupon_type' => $coupon_type,
			'coupon_field_id' => isset($_POST['coupon_field_id']) ? intval($_POST['coupon_field_id']) : 0,
			'discount_type' => $discount_type,
			'discount_amount' => $discount_amount,
			'coupon_length' => $coupon_length,
			'coupon_prefix' => $coupon_prefix,
			'coupon_suffix' => $coupon_suffix,
			'coupon_separator' => $coupon_separator,
			'individual_use' => $individual_use,
			'usage_limit_per_coupon' => $usage_limit_per_coupon,
			'usage_limit_per_user' => $usage_limit_per_user,
			'minimum_amount' => $minimum_amount,
			'maximum_amount' => $maximum_amount,
			'exclude_sale_items' => $exclude_sale_items,
			'allow_free_shipping' => $allow_free_shipping,
			'expiry_days' => $expiry_days,
			'send_email' => $send_email,
			'use_wc_email_template' => isset($_POST['use_wc_email_template']) ? 1 : 0,
			'email_subject' => $email_subject,
			'email_message' => $email_message,
			'email_from_name' => $email_from_name,
			'email_from_email' => $email_from_email,
			'description' => $description,
			'frontend_template' => isset($_POST['frontend_template']) ? wp_kses_post($_POST['frontend_template']) : '',
			'product_ids' => !empty($product_ids) ? serialize($product_ids) : null,
			'exclude_products' => !empty($exclude_product_ids) ? serialize($exclude_product_ids) : null,
			'product_categories' => !empty($product_categories) ? serialize($product_categories) : null,
			'exclude_categories' => !empty($exclude_product_categories) ? serialize($exclude_product_categories) : null,
			'product_tags' => !empty($product_tags) ? serialize($product_tags) : null,
			'exclude_product_tags' => !empty($exclude_product_tags) ? serialize($exclude_product_tags) : null,
			'status' => 'active',
			'updated_at' => current_time('mysql')
		);

		// Add or update generator
		if ($id) {
			$result = GFWCG_DB::update_generator($id, $data);
			if ($result) {
				add_settings_error('gfwcg_admin', 'gfwcg_admin_success', __('Generator updated successfully.', 'gravity-forms-woocommerce-coupon-generator'), 'updated');
			} else {
				add_settings_error('gfwcg_admin', 'gfwcg_admin_error', __('Error updating generator.', 'gravity-forms-woocommerce-coupon-generator'), 'error');
			}
		} else {
			$data['created_at'] = current_time('mysql');
			$result = GFWCG_DB::add_generator($data);
			if ($result) {
				add_settings_error('gfwcg_admin', 'gfwcg_admin_success', __('Generator added successfully.', 'gravity-forms-woocommerce-coupon-generator'), 'updated');
			} else {
				add_settings_error('gfwcg_admin', 'gfwcg_admin_error', __('Error adding generator.', 'gravity-forms-woocommerce-coupon-generator'), 'error');
			}
		}

		// Redirect to list view
		wp_redirect(admin_url('admin.php?page=gfwcg-generators'));
		exit;
	}
}
