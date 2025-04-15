<?php
/**
 * Admin Single Generator View
 *
 * @package Gravity Forms WooCommerce Coupon Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include required classes
require_once dirname(dirname(__FILE__)) . '/classes/class-gfwcg-db.php';

// Include the partials
require_once dirname(dirname(__FILE__)) . '/partials/gfwcg-actions.php';

/**
 * Display the generator form
 *
 * @param object|null $generator The generator object or null for new generator
 */
function gfwcg_display_generator_form($generator = null) {
    // Get all Gravity Forms
    $forms = GFAPI::get_forms();
    $form_fields = array();

    // If editing, get form fields
    if ($generator && $generator->form_id) {
        $form = GFAPI::get_form($generator->form_id);
        if ($form) {
            $form_fields = $form['fields'];
        }
    }

    // Get default email template
    $default_email_template = GFWCG_Email::get_default_template();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <?php echo $generator ? __('Edit Generator', 'gravity-forms-woocommerce-coupon-generator') : __('Add New Generator', 'gravity-forms-woocommerce-coupon-generator'); ?>
        </h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=gfwcg-generators')); ?>" class="page-title-action">
            <?php _e('Back to List', 'gravity-forms-woocommerce-coupon-generator'); ?>
        </a>
        <hr class="wp-header-end">
        <form method="post" action="" class="gfwcg-generator-form">
            <?php wp_nonce_field('gfwcg_admin_nonce', 'nonce'); ?>
            <input type="hidden" name="id" value="<?php echo $generator ? esc_attr($generator->id) : ''; ?>">

            <div class="gfwcg-form-section">
                <h2><?php _e('Basic Information', 'gravity-forms-woocommerce-coupon-generator'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="generator_id"><?php _e('Generator ID', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="generator_id" class="regular-text" value="<?php echo $generator ? esc_attr($generator->id) : esc_attr(GFWCG_DB::get_next_available_id()); ?>" readonly>
                            <p class="description"><?php _e('This is the unique identifier for this generator.', 'gravity-forms-woocommerce-coupon-generator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="title"><?php _e('Title', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="title" id="title" class="regular-text" 
                                   value="<?php echo $generator ? esc_attr($generator->title) : ''; ?>" required>
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
                                    <?php if ($field['type'] === 'email'): ?>
                                        <option value="<?php echo $field['id']; ?>"
                                                <?php selected($generator ? $generator->email_field_id : 0, $field['id']); ?>>
                                            <?php echo esc_html($field['label']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="gfwcg-form-section">
                <h2><?php _e('Coupon Settings', 'gravity-forms-woocommerce-coupon-generator'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="coupon_type"><?php _e('Coupon Type', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <select name="coupon_type" id="coupon_type" required>
                                <option value="random" <?php selected($generator ? $generator->coupon_type : '', 'random'); ?>>
                                    <?php _e('Random', 'gravity-forms-woocommerce-coupon-generator'); ?>
                                </option>
                                <option value="field" <?php selected($generator ? $generator->coupon_type : '', 'field'); ?>>
                                    <?php _e('From Form Field', 'gravity-forms-woocommerce-coupon-generator'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="discount_type"><?php _e('Discount Type', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <select name="discount_type" id="discount_type" required>
                                <option value="percentage" <?php selected($generator ? $generator->discount_type : '', 'percentage'); ?>>
                                    <?php _e('Percentage', 'gravity-forms-woocommerce-coupon-generator'); ?>
                                </option>
                                <option value="fixed_cart" <?php selected($generator ? $generator->discount_type : '', 'fixed_cart'); ?>>
                                    <?php _e('Fixed Cart', 'gravity-forms-woocommerce-coupon-generator'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="discount_amount"><?php _e('Discount Amount', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="discount_amount" id="discount_amount" 
                                   value="<?php echo $generator ? esc_attr($generator->discount_amount) : ''; ?>" 
                                   step="0.01" min="0" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="coupon_length"><?php _e('Coupon Length', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="coupon_length" id="coupon_length" 
                                   value="<?php echo $generator ? esc_attr($generator->coupon_length) : '8'; ?>" 
                                   min="4" max="32" required>
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
                            <label for="usage_limit_per_coupon"><?php _e('Usage Limit Per Coupon', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="usage_limit_per_coupon" id="usage_limit_per_coupon" 
                                   value="<?php echo $generator ? esc_attr($generator->usage_limit_per_coupon) : '1'; ?>" 
                                   min="0" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="usage_limit_per_user"><?php _e('Usage Limit Per User', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="usage_limit_per_user" id="usage_limit_per_user" 
                                   value="<?php echo $generator ? esc_attr($generator->usage_limit_per_user) : '1'; ?>" 
                                   min="0" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="minimum_amount"><?php _e('Minimum Amount', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="minimum_amount" id="minimum_amount" 
                                   value="<?php echo $generator ? esc_attr($generator->minimum_amount) : '0'; ?>" 
                                   step="0.01" min="0">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="maximum_amount"><?php _e('Maximum Amount', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="maximum_amount" id="maximum_amount" 
                                   value="<?php echo $generator ? esc_attr($generator->maximum_amount) : '0'; ?>" 
                                   step="0.01" min="0">
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
                            <label for="send_email"><?php _e('Send Email', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="send_email" id="send_email" value="1" 
                                   <?php checked($generator ? $generator->send_email : 0, 1); ?>>
                            <label for="send_email"><?php _e('Send email with coupon code to the user', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </td>
                    </tr>
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
                                echo $generator ? esc_textarea($generator->email_message) : $default_email_template;
                            ?></textarea>
                            <p class="description">
                                <?php _e('Available placeholders:', 'gravity-forms-woocommerce-coupon-generator'); ?>
                                <code>{coupon_code}</code>, <code>{site_name}</code>, <code>{discount_amount}</code>, <code>{expiry_date}</code>
                            </p>
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
                       value="<?php echo $generator ? __('Update Generator', 'gravity-forms-woocommerce-coupon-generator') : __('Add Generator', 'gravity-forms-woocommerce-coupon-generator'); ?>">
                <?php if ($generator) : ?>
                    <?php gfwcg_display_delete_button($generator->id, 'button-link-delete'); ?>
                <?php endif; ?>
            </p>
        </form>
    </div>
    <?php
} 