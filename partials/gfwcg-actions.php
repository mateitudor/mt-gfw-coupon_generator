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
            data-confirm-text="<?php echo esc_attr(__('Are you sure?', 'gravity-forms-woocommerce-coupon-generator')); ?>"
            data-delete-text="<?php echo esc_attr(__('Delete', 'gravity-forms-woocommerce-coupon-generator')); ?>">
        <?php _e('Delete', 'gravity-forms-woocommerce-coupon-generator'); ?>
    </button>
    <?php
}



 