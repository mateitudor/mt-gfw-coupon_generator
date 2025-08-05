<?php
/**
 * Admin Single Generator View
 *
 * @package Gravity Forms WooCommerce Coupon Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

// Classes and partials are loaded by autoloader

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
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="generator_id"><?php _e('Generator ID', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="generator_id" class="regular-text" value="<?php echo $generator ? esc_attr($generator->id) : ''; ?>" readonly>
                                <p class="description"><?php _e('This is the unique identifier for this generator.', 'gravity-forms-woocommerce-coupon-generator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="title"><?php _e('Title', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="title" id="title" class="regular-text" value="<?php echo $generator ? esc_attr($generator->title) : ''; ?>" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="description"><?php _e('Coupon Description', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <textarea name="description" id="description" class="large-text" rows="3" placeholder="<?php esc_attr_e('Enter a description for the generated coupons...', 'gravity-forms-woocommerce-coupon-generator'); ?>"><?php 
                                    echo $generator ? esc_textarea($generator->description ?? '') : '';
                                ?></textarea>
                                <p class="description"><?php _e('This description will be saved to the WooCommerce coupon and can be used to identify the purpose or source of the coupon. It will be visible in the WooCommerce admin area.', 'gravity-forms-woocommerce-coupon-generator'); ?></p>
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
                                        <option value="<?php echo esc_attr($form['id']); ?>" <?php selected($generator ? $generator->form_id : '', $form['id']); ?>>
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
                                <label for="name_field_id"><?php _e('Name Field', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <select name="name_field_id" id="name_field_id">
                                    <option value=""><?php _e('Select name field', 'gravity-forms-woocommerce-coupon-generator'); ?></option>
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
                    <tr id="coupon_field_id_row" style="display: <?php echo ($generator && $generator->coupon_type === 'field') ? 'table-row' : 'none'; ?>;">
                        <th scope="row">
                            <label for="coupon_field_id"><?php _e('Coupon Field', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <select name="coupon_field_id" id="coupon_field_id" <?php echo ($generator && $generator->coupon_type === 'field') ? 'required' : ''; ?>>
                                <option value=""><?php _e('Select coupon field', 'gravity-forms-woocommerce-coupon-generator'); ?></option>
                                <?php foreach ($form_fields as $field): ?>
                                    <option value="<?php echo esc_attr($field['id']); ?>" <?php selected($generator ? $generator->coupon_field_id : '', $field['id']); ?>>
                                        <?php echo esc_html($field['label']); ?> (<?php echo esc_html($field['type']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Select any form field that will contain the coupon code.', 'gravity-forms-woocommerce-coupon-generator'); ?></p>
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
                                   value="<?php echo $generator ? esc_attr($generator->coupon_prefix ?? '') : ''; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="coupon_suffix"><?php _e('Coupon Suffix', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="coupon_suffix" id="coupon_suffix" class="regular-text" 
                                   value="<?php echo $generator ? esc_attr($generator->coupon_suffix ?? '') : ''; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="coupon_separator"><?php _e('Coupon Separator', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="coupon_separator" id="coupon_separator" class="small-text" 
                                   value="<?php echo $generator ? esc_attr($generator->coupon_separator ?? '') : ''; ?>">
                            <p class="description"><?php _e('Character to separate prefix/suffix from the coupon code (e.g., "-")', 'gravity-forms-woocommerce-coupon-generator'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="gfwcg-form-section">
                <h2><?php _e('Discount Settings', 'gravity-forms-woocommerce-coupon-generator'); ?></h2>
                <table class="form-table">
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
                                   value="<?php echo $generator && $generator->usage_limit_per_coupon > 0 ? esc_attr($generator->usage_limit_per_coupon) : ''; ?>" 
                                   min="0">
                            <p class="description"><?php _e('Leave empty for unlimited usage. Set to 0 for unlimited usage.', 'gravity-forms-woocommerce-coupon-generator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="usage_limit_per_user"><?php _e('Usage Limit Per User', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="usage_limit_per_user" id="usage_limit_per_user" 
                                   value="<?php echo $generator && $generator->usage_limit_per_user > 0 ? esc_attr($generator->usage_limit_per_user) : ''; ?>" 
                                   min="0">
                            <p class="description"><?php _e('Leave empty for unlimited usage per user. Set to 0 for unlimited usage per user.', 'gravity-forms-woocommerce-coupon-generator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="minimum_amount"><?php _e('Minimum Amount', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="minimum_amount" id="minimum_amount" 
                                   value="<?php echo $generator && $generator->minimum_amount > 0 ? esc_attr($generator->minimum_amount) : ''; ?>" 
                                   step="0.01" min="0">
                            <p class="description"><?php _e('Leave empty for no minimum amount. Set to 0 for no minimum amount.', 'gravity-forms-woocommerce-coupon-generator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="maximum_amount"><?php _e('Maximum Amount', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="maximum_amount" id="maximum_amount" 
                                   value="<?php echo $generator && $generator->maximum_amount > 0 ? esc_attr($generator->maximum_amount) : ''; ?>" 
                                   step="0.01" min="0">
                            <p class="description"><?php _e('Leave empty for no maximum amount. Set to 0 for no maximum amount.', 'gravity-forms-woocommerce-coupon-generator'); ?></p>
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
                    <tr>
                        <th scope="row">
                            <label for="allow_free_shipping"><?php _e('Allow Free Shipping', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="allow_free_shipping" id="allow_free_shipping" value="1" 
                                   <?php checked($generator ? $generator->allow_free_shipping : 0, 1); ?>>
                            <label for="allow_free_shipping"><?php _e('Check this box if the coupon grants free shipping.', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="expiry_days"><?php _e('Expiry Days', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="expiry_days" id="expiry_days" 
                                   value="<?php echo $generator ? esc_attr($generator->expiry_days) : '0'; ?>" 
                                   min="0">
                            <p class="description"><?php _e('Number of days until the coupon expires. Set to 0 for no expiry.', 'gravity-forms-woocommerce-coupon-generator'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="product_ids"><?php _e('Products', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <select class="wc-product-search" multiple="" style="width: 100%;" name="product_ids[]" data-placeholder="<?php esc_attr_e('Search for a product…', 'gravity-forms-woocommerce-coupon-generator'); ?>" data-action="woocommerce_json_search_products_and_variations">
                                <?php
                                if ($generator && !empty($generator->product_ids)) {
                                    $product_ids = maybe_unserialize($generator->product_ids);
                                    if (is_array($product_ids)) {
                                        foreach ($product_ids as $product_id) {
                                            $product = wc_get_product($product_id);
                                            if ($product) {
                                                echo '<option value="' . esc_attr($product_id) . '" selected>' . esc_html($product->get_name()) . '</option>';
                                            }
                                        }
                                    }
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Products that the coupon will be applied to, or that need to be in the cart in order for the "Fixed cart discount" to be applied.', 'gravity-forms-woocommerce-coupon-generator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="exclude_product_ids"><?php _e('Exclude Products', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <select class="wc-product-search" multiple="" style="width: 100%;" name="exclude_product_ids[]" data-placeholder="<?php esc_attr_e('Search for a product…', 'gravity-forms-woocommerce-coupon-generator'); ?>" data-action="woocommerce_json_search_products_and_variations">
                                <?php
                                if ($generator && !empty($generator->exclude_products)) {
                                    $exclude_product_ids = maybe_unserialize($generator->exclude_products);
                                    if (is_array($exclude_product_ids)) {
                                        foreach ($exclude_product_ids as $product_id) {
                                            $product = wc_get_product($product_id);
                                            if ($product) {
                                                echo '<option value="' . esc_attr($product_id) . '" selected>' . esc_html($product->get_name()) . '</option>';
                                            }
                                        }
                                    }
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Products that the coupon will not be applied to, or that cannot be in the cart in order for the "Fixed cart discount" to be applied.', 'gravity-forms-woocommerce-coupon-generator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="product_categories"><?php _e('Product Categories', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <select id="product_categories" name="product_categories[]" style="width: 100%;" class="wc-enhanced-select" multiple="" data-placeholder="<?php esc_attr_e('Any category', 'gravity-forms-woocommerce-coupon-generator'); ?>">
                                <?php
                                $product_categories = get_terms(array(
                                    'taxonomy' => 'product_cat',
                                    'hide_empty' => false,
                                    'hierarchical' => true,
                                    'parent' => 0
                                ));
                                
                                $selected_categories = array();
                                if ($generator && !empty($generator->product_categories)) {
                                    $selected_categories = maybe_unserialize($generator->product_categories);
                                    if (!is_array($selected_categories)) {
                                        $selected_categories = array();
                                    }
                                }
                                
                                // Function to display categories hierarchically
                                function display_categories_hierarchically($categories, $selected_categories, $level = 0) {
                                    $output = '';
                                    foreach ($categories as $category) {
                                        $indent = str_repeat('— ', $level);
                                        $selected = in_array($category->term_id, $selected_categories) ? 'selected' : '';
                                        $output .= '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . $indent . esc_html($category->name) . '</option>';
                                        
                                        // Get child categories
                                        $child_categories = get_terms(array(
                                            'taxonomy' => 'product_cat',
                                            'hide_empty' => false,
                                            'hierarchical' => true,
                                            'parent' => $category->term_id
                                        ));
                                        
                                        if (!empty($child_categories)) {
                                            $output .= display_categories_hierarchically($child_categories, $selected_categories, $level + 1);
                                        }
                                    }
                                    return $output;
                                }
                                
                                echo display_categories_hierarchically($product_categories, $selected_categories);
                                ?>
                            </select>
                            <p class="description">Product categories that the coupon will be applied to, or that need to be in the cart in order for the "Fixed cart discount" to be applied.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="exclude_product_categories">Exclude Categories</label>
                        </th>
                        <td>
                            <select id="exclude_product_categories" name="exclude_product_categories[]" style="width: 100%;" class="wc-enhanced-select" multiple="" data-placeholder="No categories">
                                <?php
                                $exclude_selected_categories = array();
                                if ($generator && !empty($generator->exclude_categories)) {
                                    $exclude_selected_categories = maybe_unserialize($generator->exclude_categories);
                                    if (!is_array($exclude_selected_categories)) {
                                        $exclude_selected_categories = array();
                                    }
                                }
                                
                                echo display_categories_hierarchically($product_categories, $exclude_selected_categories);
                                ?>
                            </select>
                            <p class="description">Product categories that the coupon will not be applied to, or that cannot be in the cart in order for the "Fixed cart discount" to be applied.</p>
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
                                   value="<?php echo $generator ? esc_attr($generator->email_subject ?? '') : __('Your Coupon Code', 'gravity-forms-woocommerce-coupon-generator'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="email_message"><?php _e('Email Message', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <textarea name="email_message" id="email_message" class="large-text" rows="5"><?php 
                                echo $generator ? esc_textarea($generator->email_message ?? '') : $default_email_template;
                            ?></textarea>
                            <p class="description">
                                <?php _e('Available placeholders:', 'gravity-forms-woocommerce-coupon-generator'); ?>
                                <br><strong><?php _e('Basic:', 'gravity-forms-woocommerce-coupon-generator'); ?></strong>
                                <code>{coupon_code}</code>, <code>{site_name}</code>, <code>{discount_amount}</code>, <code>{expiry_date}</code>
                                <br><strong><?php _e('Discount Settings:', 'gravity-forms-woocommerce-coupon-generator'); ?></strong>
                                <code>{discount_type}</code>, <code>{expiry_days}</code>
                                <br><strong><?php _e('Usage Restrictions:', 'gravity-forms-woocommerce-coupon-generator'); ?></strong>
                                <code>{individual_use}</code>, <code>{usage_limit_per_coupon}</code>, <code>{usage_limit_per_user}</code>
                                <br><strong><?php _e('Amount Restrictions:', 'gravity-forms-woocommerce-coupon-generator'); ?></strong>
                                <code>{minimum_amount}</code>, <code>{maximum_amount}</code>
                                <br><strong><?php _e('Product Restrictions:', 'gravity-forms-woocommerce-coupon-generator'); ?></strong>
                                <code>{exclude_sale_items}</code>, <code>{allow_free_shipping}</code>, <code>{products}</code>, <code>{exclude_products}</code>
                                <br><strong><?php _e('Category Restrictions:', 'gravity-forms-woocommerce-coupon-generator'); ?></strong>
                                <code>{product_categories}</code>, <code>{exclude_categories}</code>
                            </p>
                            <div class="gfwcg-placeholder-help" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-left: 4px solid #0073aa;">
                                <h4 style="margin-top: 0;"><?php _e('Placeholder Examples:', 'gravity-forms-woocommerce-coupon-generator'); ?></h4>
                                <ul style="margin: 0; padding-left: 20px;">
                                    <li><code>{coupon_code}</code> → <em>WELCOME2024</em></li>
                                    <li><code>{discount_amount}</code> → <em>15.00%</em> or <em>10.00</em></li>
                                    <li><code>{minimum_amount}</code> → <em>$25.00</em> or <em>No minimum</em></li>
                                    <li><code>{usage_limit_per_coupon}</code> → <em>1</em> or <em>Unlimited</em></li>
                                    <li><code>{products}</code> → <em>Product A, Product B</em> or <em>All products</em></li>
                                    <li><code>{individual_use}</code> → <em>Yes</em> or <em>No</em></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="email_from_name"><?php _e('From Name', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="email_from_name" id="email_from_name" class="regular-text" 
                                   value="<?php echo $generator ? esc_attr($generator->email_from_name ?? '') : get_bloginfo('name'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="email_from_email"><?php _e('From Email', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="email" name="email_from_email" id="email_from_email" class="regular-text" 
                                   value="<?php echo $generator ? esc_attr($generator->email_from_email ?? '') : get_bloginfo('admin_email'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="use_wc_email_template"><?php _e('Use WooCommerce Email Template', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="use_wc_email_template" id="use_wc_email_template" value="1" 
                                   <?php checked($generator ? $generator->use_wc_email_template : 1, 1); ?>>
                            <label for="use_wc_email_template"><?php _e('Use WooCommerce email template for styling', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
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