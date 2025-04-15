<?php
/**
 * Admin Class
 *
 * @package Gravity Forms WooCommerce Coupon Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include view files
require_once plugin_dir_path(dirname(__FILE__)) . 'views/admin-list.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'views/admin-grid.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'views/admin-single.php';

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Gravity_Forms_WooCommerce_Coupon_Generator
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
    }

    public function enqueue_styles($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'gfwcg-generators') === false) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/gfwcg-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    public function enqueue_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'gfwcg-generators') === false && strpos($hook, 'gfwcg-add-generator') === false) {
            return;
        }

        // Enqueue Select2
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'));

        // Enqueue our admin script
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/gfwcg-generator-form.js',
            array('jquery', 'select2'),
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

        // Include view files
        require_once plugin_dir_path(dirname(__FILE__)) . 'views/admin-list.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'views/admin-grid.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'views/admin-single.php';

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

        // Display the add form
        $this->display_generator_form();
    }

    private function display_generator_form($id = 0) {
        $generator = $id ? $this->get_generator($id) : null;
        $forms = GFAPI::get_forms();
        $form_fields = array();

        if ($generator && $generator->form_id) {
            $form = GFAPI::get_form($generator->form_id);
            if ($form) {
                $form_fields = $this->get_form_fields($form);
            }
        }
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php echo $id ? __('Edit Generator', 'gravity-forms-woocommerce-coupon-generator') : __('Add New Generator', 'gravity-forms-woocommerce-coupon-generator'); ?>
            </h1>
            <a href="<?php echo admin_url('admin.php?page=gfwcg-generators'); ?>" class="page-title-action">
                <?php _e('Back to List', 'gravity-forms-woocommerce-coupon-generator'); ?>
            </a>
            <hr class="wp-header-end">

            <form method="post" action="" class="gfwcg-generator-form">
                <?php wp_nonce_field('gfwcg_admin_nonce', 'nonce'); ?>
                <input type="hidden" name="id" value="<?php echo $id; ?>">

                <div class="gfwcg-form-section">
                    <h2><?php _e('Basic Information', 'gravity-forms-woocommerce-coupon-generator'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="generator_id"><?php _e('Generator ID', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="generator_id" class="regular-text" value="<?php echo $id; ?>" readonly>
                                <p class="description"><?php _e('This is the unique identifier for this generator.', 'gravity-forms-woocommerce-coupon-generator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="title"><?php _e('Title', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="title" id="title" class="regular-text" required
                                       value="<?php echo $generator ? esc_attr($generator->title) : ''; ?>">
                                <p class="description"><?php _e('Enter a title for this generator.', 'gravity-forms-woocommerce-coupon-generator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="form_id"><?php _e('Gravity Form', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <select name="form_id" id="form_id" required>
                                    <option value=""><?php _e('Select a form', 'gravity-forms-woocommerce-coupon-generator'); ?></option>
                                    <?php foreach ($forms as $form): ?>
                                        <option value="<?php echo $form['id']; ?>" 
                                                <?php selected($generator ? $generator->form_id : 0, $form['id']); ?>>
                                            <?php echo esc_html($form['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="email_field_id"><?php _e('Email Field', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <select name="email_field_id" id="email_field_id" required>
                                    <option value=""><?php _e('Select email field', 'gravity-forms-woocommerce-coupon-generator'); ?></option>
                                    <?php foreach ($form_fields as $field): ?>
                                        <option value="<?php echo $field['id']; ?>"
                                                <?php selected($generator ? $generator->email_field_id : 0, $field['id']); ?>
                                                data-type="<?php echo esc_attr($field['type']); ?>">
                                            <?php echo esc_html($field['label']); ?>
                                            <?php if ($field['required']): ?>
                                                <span class="required">*</span>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Select the field that contains the email address.', 'gravity-forms-woocommerce-coupon-generator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="name_field_id"><?php _e('Name Field', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <select name="name_field_id" id="name_field_id">
                                    <option value=""><?php _e('Select name field', 'gravity-forms-woocommerce-coupon-generator'); ?></option>
                                    <?php foreach ($form_fields as $field): ?>
                                        <option value="<?php echo $field['id']; ?>"
                                                <?php selected($generator ? $generator->name_field_id : 0, $field['id']); ?>
                                                data-type="<?php echo esc_attr($field['type']); ?>">
                                            <?php echo esc_html($field['label']); ?>
                                            <?php if ($field['required']): ?>
                                                <span class="required">*</span>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Select the field that contains the name (optional).', 'gravity-forms-woocommerce-coupon-generator'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="gfwcg-form-section">
                    <h2><?php _e('Coupon Settings', 'gravity-forms-woocommerce-coupon-generator'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="coupon_length"><?php _e('Coupon Length', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="coupon_length" id="coupon_length" min="4" max="32" 
                                       value="<?php echo $generator ? esc_attr($generator->coupon_length) : '8'; ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="coupon_prefix"><?php _e('Coupon Prefix', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="coupon_prefix" id="coupon_prefix" class="regular-text" 
                                       value="<?php echo $generator ? esc_attr($generator->coupon_prefix) : ''; ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="coupon_suffix"><?php _e('Coupon Suffix', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="coupon_suffix" id="coupon_suffix" class="regular-text" 
                                       value="<?php echo $generator ? esc_attr($generator->coupon_suffix) : ''; ?>">
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="gfwcg-form-section">
                    <h2><?php _e('Usage Restrictions', 'gravity-forms-woocommerce-coupon-generator'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="individual_use"><?php _e('Individual Use', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" name="individual_use" id="individual_use" value="1" 
                                       <?php checked($generator ? $generator->individual_use : 0, 1); ?>>
                                <label for="individual_use"><?php _e('Check this box if the coupon cannot be used in conjunction with other coupons.', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="usage_limit"><?php _e('Usage Limit', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="usage_limit" id="usage_limit" min="0" 
                                       value="<?php echo $generator ? esc_attr($generator->usage_limit) : '1'; ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="usage_limit_per_user"><?php _e('Usage Limit Per User', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="usage_limit_per_user" id="usage_limit_per_user" min="0" 
                                       value="<?php echo $generator ? esc_attr($generator->usage_limit_per_user) : '1'; ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="minimum_amount"><?php _e('Minimum Amount', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="minimum_amount" id="minimum_amount" step="0.01" min="0" 
                                       value="<?php echo $generator ? esc_attr($generator->minimum_amount) : '0'; ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="maximum_amount"><?php _e('Maximum Amount', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="maximum_amount" id="maximum_amount" step="0.01" min="0" 
                                       value="<?php echo $generator ? esc_attr($generator->maximum_amount) : '0'; ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="exclude_sale_items"><?php _e('Exclude Sale Items', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" name="exclude_sale_items" id="exclude_sale_items" value="1" 
                                       <?php checked($generator ? $generator->exclude_sale_items : 0, 1); ?>>
                                <label for="exclude_sale_items"><?php _e('Check this box if the coupon should not apply to items on sale.', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="gfwcg-form-section">
                    <h2><?php _e('Email Settings', 'gravity-forms-woocommerce-coupon-generator'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="email_subject"><?php _e('Email Subject', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="email_subject" id="email_subject" class="regular-text" 
                                       value="<?php echo $generator ? esc_attr($generator->email_subject) : __('Your Coupon Code', 'gravity-forms-woocommerce-coupon-generator'); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="email_message"><?php _e('Email Message', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <textarea name="email_message" id="email_message" class="large-text" rows="5"><?php 
                                    echo $generator ? esc_textarea($generator->email_message) : 
                                        __('Your coupon code is: {coupon_code}', 'gravity-forms-woocommerce-coupon-generator');
                                ?></textarea>
                                <p class="description"><?php _e('Use {coupon_code} to insert the generated coupon code.', 'gravity-forms-woocommerce-coupon-generator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="email_from_name"><?php _e('From Name', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="email_from_name" id="email_from_name" class="regular-text" 
                                       value="<?php echo $generator ? esc_attr($generator->email_from_name) : get_bloginfo('name'); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="email_from_email"><?php _e('From Email', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <input type="email" name="email_from_email" id="email_from_email" class="regular-text" 
                                       value="<?php echo $generator ? esc_attr($generator->email_from_email) : get_bloginfo('admin_email'); ?>">
                            </td>
                        </tr>
                    </table>
                </div>

                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" 
                           value="<?php echo $id ? __('Update Generator', 'gravity-forms-woocommerce-coupon-generator') : __('Add Generator', 'gravity-forms-woocommerce-coupon-generator'); ?>">
                </p>
            </form>
        </div>
        <?php
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
        check_ajax_referer('gfwcg_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'gravity-forms-woocommerce-coupon-generator')));
        }

        $data = array(
            'title' => isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '',
            'form_id' => isset($_POST['form_id']) ? intval($_POST['form_id']) : 0,
            'email_field_id' => isset($_POST['email_field_id']) ? intval($_POST['email_field_id']) : 0,
            'name_field_id' => isset($_POST['name_field_id']) ? intval($_POST['name_field_id']) : 0,
            'coupon_type' => isset($_POST['coupon_type']) ? sanitize_text_field($_POST['coupon_type']) : 'random',
            'coupon_length' => isset($_POST['coupon_length']) ? intval($_POST['coupon_length']) : 8,
            'coupon_prefix' => isset($_POST['coupon_prefix']) ? sanitize_text_field($_POST['coupon_prefix']) : '',
            'coupon_suffix' => isset($_POST['coupon_suffix']) ? sanitize_text_field($_POST['coupon_suffix']) : '',
            'discount_type' => isset($_POST['discount_type']) ? sanitize_text_field($_POST['discount_type']) : 'percentage',
            'discount_amount' => isset($_POST['discount_amount']) ? floatval($_POST['discount_amount']) : 0,
            'individual_use' => isset($_POST['individual_use']) ? 1 : 0,
            'usage_limit_per_coupon' => isset($_POST['usage_limit_per_coupon']) ? intval($_POST['usage_limit_per_coupon']) : 1,
            'usage_limit_per_user' => isset($_POST['usage_limit_per_user']) ? intval($_POST['usage_limit_per_user']) : 1,
            'minimum_amount' => isset($_POST['minimum_amount']) ? floatval($_POST['minimum_amount']) : 0,
            'maximum_amount' => isset($_POST['maximum_amount']) ? floatval($_POST['maximum_amount']) : 0,
            'exclude_sale_items' => isset($_POST['exclude_sale_items']) ? 1 : 0,
            'send_email' => isset($_POST['send_email']) ? 1 : 0,
            'email_subject' => isset($_POST['email_subject']) ? sanitize_text_field($_POST['email_subject']) : '',
            'email_message' => isset($_POST['email_message']) ? wp_kses_post($_POST['email_message']) : '',
            'email_from_name' => isset($_POST['email_from_name']) ? sanitize_text_field($_POST['email_from_name']) : '',
            'email_from_email' => isset($_POST['email_from_email']) ? sanitize_email($_POST['email_from_email']) : '',
            'status' => 'active'
        );

        if (empty($data['title'])) {
            wp_send_json_error(array('message' => __('Title is required.', 'gravity-forms-woocommerce-coupon-generator')));
        }

        if (!$data['form_id'] || !$data['email_field_id']) {
            wp_send_json_error(array('message' => __('Form ID and Email Field are required.', 'gravity-forms-woocommerce-coupon-generator')));
        }

        if (isset($_POST['id']) && $_POST['id']) {
            $data['id'] = intval($_POST['id']);
        }

        $result = GFWCG_DB::save_generator($data);

        if ($result === false) {
            wp_send_json_error(array('message' => __('Error saving generator.', 'gravity-forms-woocommerce-coupon-generator')));
        }

        wp_send_json_success(array(
            'message' => __('Generator saved successfully.', 'gravity-forms-woocommerce-coupon-generator'),
            'redirect_url' => admin_url('admin.php?page=gfwcg-generators')
        ));
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

    private function get_generator($id) {
        return GFWCG_DB::get_generator($id);
    }

    public function admin_init() {
        // Verify table structure
        GFWCG_DB::verify_table_structure();
        
        // Register settings
        register_setting('gfwcg_options', 'gfwcg_settings');
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
        $individual_use = isset($_POST['individual_use']) ? 1 : 0;
        $usage_limit_per_coupon = isset($_POST['usage_limit_per_coupon']) ? intval($_POST['usage_limit_per_coupon']) : 1;
        $usage_limit_per_user = isset($_POST['usage_limit_per_user']) ? intval($_POST['usage_limit_per_user']) : 1;
        $minimum_amount = isset($_POST['minimum_amount']) ? floatval($_POST['minimum_amount']) : 0;
        $maximum_amount = isset($_POST['maximum_amount']) ? floatval($_POST['maximum_amount']) : 0;
        $exclude_sale_items = isset($_POST['exclude_sale_items']) ? 1 : 0;
        $send_email = isset($_POST['send_email']) ? 1 : 0;
        $email_subject = isset($_POST['email_subject']) ? sanitize_text_field($_POST['email_subject']) : '';
        $email_message = isset($_POST['email_message']) ? wp_kses_post($_POST['email_message']) : '';
        $email_from_name = isset($_POST['email_from_name']) ? sanitize_text_field($_POST['email_from_name']) : '';
        $email_from_email = isset($_POST['email_from_email']) ? sanitize_email($_POST['email_from_email']) : '';

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
            'discount_type' => $discount_type,
            'discount_amount' => $discount_amount,
            'coupon_length' => $coupon_length,
            'coupon_prefix' => $coupon_prefix,
            'coupon_suffix' => $coupon_suffix,
            'individual_use' => $individual_use,
            'usage_limit_per_coupon' => $usage_limit_per_coupon,
            'usage_limit_per_user' => $usage_limit_per_user,
            'minimum_amount' => $minimum_amount,
            'maximum_amount' => $maximum_amount,
            'exclude_sale_items' => $exclude_sale_items,
            'send_email' => $send_email,
            'email_subject' => $email_subject,
            'email_message' => $email_message,
            'email_from_name' => $email_from_name,
            'email_from_email' => $email_from_email,
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