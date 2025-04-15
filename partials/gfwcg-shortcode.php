<?php
/**
 * Shortcode Handler
 *
 * @package Gravity Forms WooCommerce Coupon Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent multiple includes
if (defined('GFWCG_SHORTCODE_LOADED')) {
    return;
}
define('GFWCG_SHORTCODE_LOADED', true);

// Include email class
require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-gfwcg-email.php';

// Debug hook to check if shortcode is being processed
add_action('init', function() {
    error_log('GFWCG: Plugin initialized');
});

// Debug hook for Gravity Forms shortcode
add_filter('shortcode_atts_gravityform', function($atts) {
    error_log('GFWCG: Gravity Forms shortcode processed');
    error_log('GFWCG: Shortcode attributes: ' . print_r($atts, true));
    return $atts;
}, 5, 1);

// Hook into Gravity Forms shortcode processing
add_filter('gform_shortcode_form', function($shortcode_string, $attributes) {
    error_log('GFWCG: Gravity Forms shortcode being processed');
    error_log('GFWCG: Shortcode string: ' . $shortcode_string);
    error_log('GFWCG: Attributes: ' . print_r($attributes, true));
    
    if (!isset($attributes['gen'])) {
        error_log('GFWCG: No generator ID provided');
        return $shortcode_string;
    }

    // Get the generator by ID
    $generator = GFWCG_DB::get_generator($attributes['gen']);
    if (!$generator) {
        error_log('GFWCG: Generator not found for ID: ' . $attributes['gen']);
        return $shortcode_string;
    }

    error_log('GFWCG: Found generator: ' . print_r($generator, true));

    // Add generator data as hidden fields
    add_filter('gform_pre_render_' . $attributes['id'], function($form) use ($generator) {
        error_log('GFWCG: Adding hidden field to form');
        // Add generator ID as hidden field
        $form['fields'][] = array(
            'id' => 'gfwcg_generator_id',
            'type' => 'hidden',
            'defaultValue' => $generator->id,
            'cssClass' => 'gfwcg-generator-id',
            'inputName' => 'gfwcg_generator_id',
            'isRequired' => true,
            'visibility' => 'visible'
        );

        return $form;
    }, 20);

    // Handle form submission with higher priority
    add_action('gform_after_submission', function($entry, $form) use ($generator) {
        error_log('GFWCG: Form submission handler called');
        error_log('GFWCG: Entry data: ' . print_r($entry, true));
        error_log('GFWCG: Form data: ' . print_r($form, true));
        
        // Only process if this is the correct form
        if ($form['id'] != $generator->form_id) {
            error_log('GFWCG: Not the correct form, skipping');
            return;
        }
        
        try {
            // Get email from the specified field
            $email = rgar($entry, $generator->email_field_id);
            error_log('GFWCG: Email field ID: ' . $generator->email_field_id);
            error_log('GFWCG: Extracted email: ' . $email);
            
            if (!$email) {
                error_log('GFWCG: No email found in field ' . $generator->email_field_id);
                return;
            }

            // Generate coupon code
            $coupon_code = gfwcg_generate_coupon_code($generator);
            error_log('GFWCG: Generated coupon code: ' . $coupon_code);

            // Create WooCommerce coupon
            $coupon = new WC_Coupon();
            $coupon->set_code($coupon_code);
            $coupon->set_discount_type($generator->discount_type);
            $coupon->set_amount($generator->discount_amount);
            $coupon->set_individual_use($generator->individual_use);
            $coupon->set_usage_limit($generator->usage_limit_per_coupon);
            $coupon->set_usage_limit_per_user($generator->usage_limit_per_user);
            $coupon->set_minimum_amount($generator->minimum_amount);
            $coupon->set_maximum_amount($generator->maximum_amount);
            $coupon->set_exclude_sale_items($generator->exclude_sale_items);
            
            if ($generator->expiry_date) {
                $coupon->set_date_expires($generator->expiry_date);
            }

            $coupon->save();
            error_log('GFWCG: Coupon saved with ID: ' . $coupon->get_id());

            // Prepare email
            $subject = $generator->email_subject ?: __('Your Coupon Code', 'gravity-forms-woocommerce-coupon-generator');
            $message = $generator->email_message ?: __('Your coupon code is: {coupon_code}', 'gravity-forms-woocommerce-coupon-generator');
            
            // Replace placeholders
            $message = str_replace(
                array(
                    '{coupon_code}',
                    '{site_name}',
                    '{discount_amount}',
                    '{expiry_date}'
                ),
                array(
                    $coupon_code,
                    get_bloginfo('name'),
                    $generator->discount_amount . ($generator->discount_type === 'percentage' ? '%' : ''),
                    $generator->expiry_date ? date_i18n(get_option('date_format'), strtotime($generator->expiry_date)) : __('No expiry', 'gravity-forms-woocommerce-coupon-generator')
                ),
                $message
            );

            // Set headers
            $headers = array();
            $from_name = $generator->email_from_name ?: get_bloginfo('name');
            $from_email = $generator->email_from_email ?: get_bloginfo('admin_email');
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
            $headers[] = 'Content-Type: text/html; charset=UTF-8';

            error_log('GFWCG: Email details:');
            error_log('GFWCG: To: ' . $email);
            error_log('GFWCG: From: ' . $from_name . ' <' . $from_email . '>');
            error_log('GFWCG: Subject: ' . $subject);
            error_log('GFWCG: Message: ' . $message);
            error_log('GFWCG: Headers: ' . print_r($headers, true));

            // Send email
            $mail_result = wp_mail($email, $subject, $message, $headers);
            error_log('GFWCG: wp_mail result: ' . ($mail_result ? 'true' : 'false'));
            
            if (!$mail_result) {
                $last_error = error_get_last();
                error_log('GFWCG: wp_mail failed. Last error: ' . print_r($last_error, true));
                
                // Try sending with different headers
                $alt_headers = array(
                    'From: ' . $from_name . ' <' . $from_email . '>',
                    'Content-Type: text/plain; charset=UTF-8'
                );
                error_log('GFWCG: Trying alternative headers: ' . print_r($alt_headers, true));
                $alt_result = wp_mail($email, $subject, strip_tags($message), $alt_headers);
                error_log('GFWCG: Alternative wp_mail result: ' . ($alt_result ? 'true' : 'false'));
            }

        } catch (Exception $e) {
            error_log('GFWCG: Exception caught: ' . $e->getMessage());
            error_log('GFWCG: Stack trace: ' . $e->getTraceAsString());
        }
    }, 20, 2);

    return $shortcode_string;
}, 10, 2);

/**
 * Generate a unique coupon code
 *
 * @param object $generator The generator object
 * @return string The generated coupon code
 */
function gfwcg_generate_coupon_code($generator) {
    $prefix = $generator->coupon_prefix ?: '';
    $suffix = $generator->coupon_suffix ?: '';
    $length = $generator->coupon_length ?: 8;

    // Generate random string
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[rand(0, strlen($characters) - 1)];
    }

    // Combine prefix, random string, and suffix
    $coupon_code = $prefix . $random_string . $suffix;

    // Check if coupon code already exists
    $coupon = new WC_Coupon($coupon_code);
    if ($coupon->get_id() > 0) {
        // If exists, generate a new one recursively
        return gfwcg_generate_coupon_code($generator);
    }

    return $coupon_code;
} 