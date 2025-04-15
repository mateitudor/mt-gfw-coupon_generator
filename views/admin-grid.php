<?php
/**
 * Admin Grid View
 *
 * @package Gravity Forms WooCommerce Coupon Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include the partials
require_once dirname(dirname(__FILE__)) . '/partials/admin-header.php';

/**
 * Display the generators grid view
 *
 * @param array $generators The generators to display
 */
function gfwcg_display_grid_view($generators) {
    // Display the header
    gfwcg_display_admin_header(
        __('Coupon Generators', 'gravity-forms-woocommerce-coupon-generator'),
        'grid',
        admin_url('admin.php?page=gfwcg-add-generator')
    );
    ?>
    <div class="gfwcg-grid">
        <?php if (empty($generators)) : ?>
            <div class="gfwcg-grid-empty">
                <?php _e('No generators found.', 'gravity-forms-woocommerce-coupon-generator'); ?>
            </div>
        <?php else : ?>
            <?php foreach ($generators as $generator) : 
                $form = GFAPI::get_form($generator->form_id);
                $form_title = $form ? $form['title'] : __('Form not found', 'gravity-forms-woocommerce-coupon-generator');
                $discount_text = $generator->discount_type === 'percentage' ? 
                    $generator->discount_amount . '%' : 
                    wc_price($generator->discount_amount);
            ?>
                <div class="gfwcg-grid-item">
                    <div class="gfwcg-grid-item-header">
                        <h3>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=gfwcg-generators&view=edit&id=' . $generator->id)); ?>">
                                <?php echo esc_html($generator->title); ?>
                            </a>
                        </h3>
                        <span class="status-<?php echo esc_attr($generator->status); ?>">
                            <?php echo esc_html(ucfirst($generator->status)); ?>
                        </span>
                    </div>
                    
                    <div class="gfwcg-grid-item-content">
                        <div class="gfwcg-grid-item-row">
                            <span class="label"><?php _e('ID:', 'gravity-forms-woocommerce-coupon-generator'); ?></span>
                            <span class="value"><?php echo esc_html($generator->id); ?></span>
                        </div>
                        <div class="gfwcg-grid-item-row">
                            <span class="label"><?php _e('Form:', 'gravity-forms-woocommerce-coupon-generator'); ?></span>
                            <span class="value"><?php echo esc_html($form_title); ?></span>
                        </div>
                        
                        <div class="gfwcg-grid-item-row">
                            <span class="label"><?php _e('Type:', 'gravity-forms-woocommerce-coupon-generator'); ?></span>
                            <span class="value"><?php echo esc_html(ucfirst($generator->coupon_type)); ?></span>
                        </div>
                        
                        <div class="gfwcg-grid-item-row">
                            <span class="label"><?php _e('Discount:', 'gravity-forms-woocommerce-coupon-generator'); ?></span>
                            <span class="value"><?php echo $discount_text; ?></span>
                        </div>
                        
                        <div class="gfwcg-grid-item-row">
                            <span class="label"><?php _e('Usage:', 'gravity-forms-woocommerce-coupon-generator'); ?></span>
                            <span class="value">
                                <?php 
                                    $limits = array();
                                    if ($generator->usage_limit_per_coupon > 0) {
                                        $limits[] = sprintf(__('Per coupon: %d', 'gravity-forms-woocommerce-coupon-generator'), $generator->usage_limit_per_coupon);
                                    }
                                    if ($generator->usage_limit_per_user > 0) {
                                        $limits[] = sprintf(__('Per user: %d', 'gravity-forms-woocommerce-coupon-generator'), $generator->usage_limit_per_user);
                                    }
                                    echo implode('<br>', $limits);
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="gfwcg-grid-item-footer">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=gfwcg-generators&view=edit&id=' . $generator->id)); ?>" class="button button-small">
                            <?php _e('Edit', 'gravity-forms-woocommerce-coupon-generator'); ?>
                        </a>
                        <button type="button" class="button button-small delete-generator" data-id="<?php echo esc_attr($generator->id); ?>">
                            <?php _e('Delete', 'gravity-forms-woocommerce-coupon-generator'); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}
