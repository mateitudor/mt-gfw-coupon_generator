<?php
/**
 * Admin Header Template
 *
 * @package Gravity Forms WooCommerce Coupon Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include the view switcher
require_once dirname(dirname(__FILE__)) . '/partials/admin-switcher.php';

/**
 * Display the admin header
 *
 * @param string $title The page title
 * @param string $current_view The current view (list/grid)
 * @param string $add_new_url The URL for the add new page
 */
function gfwcg_display_admin_header($title, $current_view = 'list', $add_new_url = '') {
    ?>
    <div class="gfwcg-admin-header">
        <h1 class="wp-heading-inline"><?php echo esc_html($title); ?></h1>
        
        <?php if ($add_new_url) : ?>
            <a href="<?php echo esc_url($add_new_url); ?>" class="page-title-action">
                <?php _e('Add New', 'gravity-forms-woocommerce-coupon-generator'); ?>
            </a>
        <?php endif; ?>
        
        <?php gfwcg_display_view_switcher($current_view); ?>
        
        <hr class="wp-header-end">
    </div>
    <?php
} 