<?php
/**
 * Generator Actions Partial
 *
 * @package Gravity Forms WooCommerce Coupon Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display the edit button for a generator
 *
 * @param int $generator_id The generator ID
 * @param string $button_class Additional CSS classes for the button
 * @return void
 */
function gfwcg_display_edit_button($generator_id, $button_class = '') {
    ?>
    <a href="<?php echo esc_url(admin_url('admin.php?page=gfwcg-generators&view=edit&id=' . $generator_id)); ?>" 
       class="button button-small <?php echo esc_attr($button_class); ?>">
        <?php _e('Edit', 'gravity-forms-woocommerce-coupon-generator'); ?>
    </a>
    <?php
}

/**
 * Display the delete button for a generator
 *
 * @param int $generator_id The generator ID
 * @param string $button_class Additional CSS classes for the button
 * @return void
 */
function gfwcg_display_delete_button($generator_id, $button_class = '') {
    ?>
    <button type="button" 
            class="button button-small delete-generator <?php echo esc_attr($button_class); ?>" 
            data-id="<?php echo esc_attr($generator_id); ?>"
            data-nonce="<?php echo esc_attr(wp_create_nonce('gfwcg_delete_generator_' . $generator_id)); ?>"
            data-confirm-text="<?php echo esc_attr(__('Are you sure?', 'gravity-forms-woocommerce-coupon-generator')); ?>"
            data-delete-text="<?php echo esc_attr(__('Delete', 'gravity-forms-woocommerce-coupon-generator')); ?>">
        <?php _e('Delete', 'gravity-forms-woocommerce-coupon-generator'); ?>
    </button>
    <?php
}

/**
 * Handle the delete generator action
 *
 * @return void
 */
function gfwcg_handle_delete_generator() {
    if (!isset($_POST['generator_id']) || !isset($_POST['nonce'])) {
        wp_send_json_error(__('Invalid request', 'gravity-forms-woocommerce-coupon-generator'));
    }

    $generator_id = intval($_POST['generator_id']);
    $nonce = sanitize_text_field($_POST['nonce']);

    if (!wp_verify_nonce($nonce, 'gfwcg_delete_generator_' . $generator_id)) {
        wp_send_json_error(__('Invalid nonce', 'gravity-forms-woocommerce-coupon-generator'));
    }

    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(__('Permission denied', 'gravity-forms-woocommerce-coupon-generator'));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'gfwcg_generators';

    $result = $wpdb->delete(
        $table_name,
        array('id' => $generator_id),
        array('%d')
    );

    if ($result === false) {
        wp_send_json_error(__('Failed to delete generator', 'gravity-forms-woocommerce-coupon-generator'));
    }

    wp_send_json_success(__('Generator deleted successfully', 'gravity-forms-woocommerce-coupon-generator'));
}

// Hook into admin-ajax for delete action
add_action('wp_ajax_gfwcg_delete_generator', 'gfwcg_handle_delete_generator');

/**
 * Enqueue the delete generator script
 *
 * @return void
 */
function gfwcg_enqueue_delete_script() {
    wp_enqueue_script(
        'gfwcg-admin',
        plugins_url('assets/js/admin.js', dirname(__FILE__)),
        array('jquery'),
        GFWCG_VERSION,
        true
    );

    wp_localize_script('gfwcg-admin', 'gfwcgAdmin', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'deleteConfirm' => __('Are you sure you want to delete this generator?', 'gravity-forms-woocommerce-coupon-generator'),
    ));
}
add_action('admin_enqueue_scripts', 'gfwcg_enqueue_delete_script'); 