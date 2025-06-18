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
                <h2>Basic Information</h2>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="generator_id">Generator ID</label>
                            </th>
                            <td>
                                <input type="text" id="generator_id" class="regular-text" value="<?php echo $generator ? esc_attr($generator->id) : ''; ?>" readonly>
                                <p class="description">This is the unique identifier for this generator.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="title">Title</label>
                            </th>
                            <td>
                                <input type="text" name="title" id="title" class="regular-text" value="<?php echo $generator ? esc_attr($generator->title) : ''; ?>" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="form_id">Gravity Form</label>
                            </th>
                            <td>
                                <select name="form_id" id="form_id" required>
                                    <option value="">Select a form</option>
                                    <?php foreach ($forms as $form): ?>
                                        <option value="<?php echo esc_attr($form['id']); ?>" <?php selected($generator ? $generator->form_id : '', $form['id']); ?>>
                                            <?php echo esc_html($form['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="email_field_id">Email Field</label>
                            </th>
                            <td>
                                <select name="email_field_id" id="email_field_id" required>
                                    <option value="">Select email field</option>
                                    <?php foreach ($form_fields as $field): ?>
                                        <?php if ($field['type'] === 'email'): ?>
                                            <option value="<?php echo esc_attr($field['id']); ?>" <?php selected($generator ? $generator->email_field_id : '', $field['id']); ?>>
                                                <?php echo esc_html($field['label']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="name_field_id">Name Field</label>
                            </th>
                            <td>
                                <select name="name_field_id" id="name_field_id">
                                    <option value="">Select name field</option>
                                    <?php foreach ($form_fields as $field): ?>
                                        <?php if ($field['type'] === 'name'): ?>
                                            <option value="<?php echo esc_attr($field['id']); ?>" <?php selected($generator ? $generator->name_field_id : '', $field['id']); ?>>
                                                <?php echo esc_html($field['label']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="gfwcg-form-section">
                <h2>Coupon Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="coupon_type">Coupon Type</label>
                        </th>
                        <td>
                            <select name="coupon_type" id="coupon_type" required>
                                <option value="random" <?php selected($generator ? $generator->coupon_type : '', 'random'); ?>>
                                    Random
                                </option>
                                <option value="field" <?php selected($generator ? $generator->coupon_type : '', 'field'); ?>>
                                    From Form Field
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr id="coupon_field_id_row" style="display: <?php echo ($generator && $generator->coupon_type === 'field') ? 'table-row' : 'none'; ?>;">
                        <th scope="row">
                            <label for="coupon_field_id">Coupon Field</label>
                        </th>
                        <td>
                            <select name="coupon_field_id" id="coupon_field_id" <?php echo ($generator && $generator->coupon_type === 'field') ? 'required' : ''; ?>>
                                <option value="">Select coupon field</option>
                                <?php foreach ($form_fields as $field): ?>
                                    <option value="<?php echo esc_attr($field['id']); ?>" <?php selected($generator ? $generator->coupon_field_id : '', $field['id']); ?>>
                                        <?php echo esc_html($field['label']); ?> (<?php echo esc_html($field['type']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Select any form field that will contain the coupon code.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="coupon_length">Coupon Length</label>
                        </th>
                        <td>
                            <input type="number" name="coupon_length" id="coupon_length" 
                                   value="<?php echo $generator ? esc_attr($generator->coupon_length) : '8'; ?>" 
                                   min="4" max="32" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="coupon_prefix">Coupon Prefix</label>
                        </th>
                        <td>
                            <input type="text" name="coupon_prefix" id="coupon_prefix" class="regular-text" 
                                   value="<?php echo $generator ? esc_attr($generator->coupon_prefix) : ''; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="coupon_suffix">Coupon Suffix</label>
                        </th>
                        <td>
                            <input type="text" name="coupon_suffix" id="coupon_suffix" class="regular-text" 
                                   value="<?php echo $generator ? esc_attr($generator->coupon_suffix) : ''; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="coupon_separator">Coupon Separator</label>
                        </th>
                        <td>
                            <input type="text" name="coupon_separator" id="coupon_separator" class="regular-text" 
                                   value="<?php echo $generator ? esc_attr($generator->coupon_separator) : ''; ?>">
                        </td>
                    </tr>
                </table>
            </div>

            <div class="gfwcg-form-section">
                <h2>Discount Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="discount_type">Discount Type</label>
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
                            <label for="discount_amount">Discount Amount</label>
                        </th>
                        <td>
                            <input type="number" name="discount_amount" id="discount_amount" 
                                   value="<?php echo $generator ? esc_attr($generator->discount_amount) : ''; ?>" 
                                   step="0.01" min="0" required>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="gfwcg-form-section">
                <h2>Usage Restrictions</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="individual_use">Individual Use</label>
                        </th>
                        <td>
                            <input type="checkbox" name="individual_use" id="individual_use" value="1" 
                                   <?php checked($generator ? $generator->individual_use : 0, 1); ?>>
                            <label for="individual_use">Check this box if the coupon cannot be used in conjunction with other coupons.</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="usage_limit_per_coupon">Usage Limit Per Coupon</label>
                        </th>
                        <td>
                            <input type="number" name="usage_limit_per_coupon" id="usage_limit_per_coupon" 
                                   value="<?php echo $generator && $generator->usage_limit_per_coupon > 0 ? esc_attr($generator->usage_limit_per_coupon) : ''; ?>" 
                                   min="0">
                            <p class="description">Leave empty for unlimited usage. Set to 0 for unlimited usage.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="usage_limit_per_user">Usage Limit Per User</label>
                        </th>
                        <td>
                            <input type="number" name="usage_limit_per_user" id="usage_limit_per_user" 
                                   value="<?php echo $generator && $generator->usage_limit_per_user > 0 ? esc_attr($generator->usage_limit_per_user) : ''; ?>" 
                                   min="0">
                            <p class="description">Leave empty for unlimited usage per user. Set to 0 for unlimited usage per user.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="minimum_amount">Minimum Amount</label>
                        </th>
                        <td>
                            <input type="number" name="minimum_amount" id="minimum_amount" 
                                   value="<?php echo $generator && $generator->minimum_amount > 0 ? esc_attr($generator->minimum_amount) : ''; ?>" 
                                   step="0.01" min="0">
                            <p class="description">Leave empty for no minimum amount. Set to 0 for no minimum amount.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="maximum_amount">Maximum Amount</label>
                        </th>
                        <td>
                            <input type="number" name="maximum_amount" id="maximum_amount" 
                                   value="<?php echo $generator && $generator->maximum_amount > 0 ? esc_attr($generator->maximum_amount) : ''; ?>" 
                                   step="0.01" min="0">
                            <p class="description">Leave empty for no maximum amount. Set to 0 for no maximum amount.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="exclude_sale_items">Exclude Sale Items</label>
                        </th>
                        <td>
                            <input type="checkbox" name="exclude_sale_items" id="exclude_sale_items" value="1" 
                                   <?php checked($generator ? $generator->exclude_sale_items : 0, 1); ?>>
                            <label for="exclude_sale_items">Check this box if the coupon should not apply to items on sale.</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="allow_free_shipping">Allow Free Shipping</label>
                        </th>
                        <td>
                            <input type="checkbox" name="allow_free_shipping" id="allow_free_shipping" value="1" 
                                   <?php checked($generator ? $generator->allow_free_shipping : 0, 1); ?>>
                            <label for="allow_free_shipping">Check this box if the coupon grants free shipping.</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="expiry_days">Expiry Days</label>
                        </th>
                        <td>
                            <input type="number" name="expiry_days" id="expiry_days" 
                                   value="<?php echo $generator ? esc_attr($generator->expiry_days) : '0'; ?>" 
                                   min="0">
                            <p class="description">Number of days until the coupon expires. Set to 0 for no expiry.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="gfwcg-form-section">
                <h2>Email Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="send_email">Send Email</label>
                        </th>
                        <td>
                            <input type="checkbox" name="send_email" id="send_email" value="1" 
                                   <?php checked($generator ? $generator->send_email : 0, 1); ?>>
                            <label for="send_email">Send email with coupon code to the user</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="email_subject">Email Subject</label>
                        </th>
                        <td>
                            <input type="text" name="email_subject" id="email_subject" class="regular-text" 
                                   value="<?php echo $generator ? esc_attr($generator->email_subject) : __('Your Coupon Code', 'gravity-forms-woocommerce-coupon-generator'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="email_message">Email Message</label>
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
                            <label for="email_from_name">From Name</label>
                        </th>
                        <td>
                            <input type="text" name="email_from_name" id="email_from_name" class="regular-text" 
                                   value="<?php echo $generator ? esc_attr($generator->email_from_name) : get_bloginfo('name'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="email_from_email">From Email</label>
                        </th>
                        <td>
                            <input type="email" name="email_from_email" id="email_from_email" class="regular-text" 
                                   value="<?php echo $generator ? esc_attr($generator->email_from_email) : get_bloginfo('admin_email'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="use_wc_email_template">Use WooCommerce Email Template</label>
                        </th>
                        <td>
                            <input type="checkbox" name="use_wc_email_template" id="use_wc_email_template" value="1" 
                                   <?php checked($generator ? $generator->use_wc_email_template : 1, 1); ?>>
                            <label for="use_wc_email_template">Use WooCommerce email template for styling</label>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="gfwcg-form-section">
                <h2>Advanced Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="is_debug">Debug Mode</label>
                        </th>
                        <td>
                            <input type="checkbox" name="is_debug" id="is_debug" value="1" 
                                   <?php checked($generator ? $generator->is_debug : 0, 1); ?>>
                            <label for="is_debug">Enable debug mode for troubleshooting</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="description">Description</label>
                        </th>
                        <td>
                            <textarea name="description" id="description" class="large-text" rows="3"><?php 
                                echo $generator ? esc_textarea($generator->description) : '';
                            ?></textarea>
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
    // Add JavaScript for toggling coupon field
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const couponType = document.getElementById('coupon_type');
        const couponFieldRow = document.getElementById('coupon_field_id_row');
        const couponField = document.getElementById('coupon_field_id');
        const form = document.querySelector('form');

        function toggleCouponField() {
            if (couponType.value === 'field') {
                couponFieldRow.style.display = 'table-row';
                couponField.required = true;
            } else {
                couponFieldRow.style.display = 'none';
                couponField.required = false;
                couponField.value = '';
            }
        }

        couponType.addEventListener('change', toggleCouponField);
        toggleCouponField();

        form.addEventListener('submit', function(e) {
            if (couponType.value === 'field' && !couponField.value) {
                e.preventDefault();
                alert('Please select a coupon field when using field type.');
                couponField.focus();
            }
        });
    });
    </script>
    <?php
} 