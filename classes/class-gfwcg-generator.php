<?php

class GFWCG_Generator {
    public function __construct() {
        add_action('gform_after_submission', array($this, 'process_form_submission'), 10, 2);
    }

    public function process_form_submission($entry, $form) {
        error_log('GFWCG: Processing form submission');
        error_log('GFWCG: Form ID: ' . $form['id']);
        error_log('GFWCG: Entry data: ' . print_r($entry, true));

        // Get the generator for this form
        $generator = $this->get_generator_by_form_id($form['id']);
        if (!$generator) {
            error_log('GFWCG: No generator found for form ID: ' . $form['id']);
            return;
        }

        error_log('GFWCG: Found generator: ' . print_r($generator, true));

        // Generate coupon code
        $coupon_code = $this->generate_coupon_code($generator, $entry);
        error_log('GFWCG: Generated coupon code: ' . $coupon_code);

        // Create WooCommerce coupon
        $coupon_id = $this->create_woocommerce_coupon($coupon_code, $generator);
        error_log('GFWCG: Created WooCommerce coupon with ID: ' . $coupon_id);

        // Send email if configured
        if ($generator->send_email) {
            error_log('GFWCG: Email sending is enabled, attempting to send email');
            
            // Get email address from the specified field
            $to = rgar($entry, $generator->email_field_id);
            if (!$to) {
                error_log('GFWCG: No email address found for recipient in field ' . $generator->email_field_id);
                error_log('GFWCG: Available entry fields: ' . print_r(array_keys($entry), true));
                return;
            }

            // Prepare email content
            $subject = $generator->email_subject ?: __('Your Coupon Code', 'gf-wc-coupon-generator');
            
            // Get the message template
            $message = $generator->email_message ?: $this->get_default_email_template($coupon_code, $generator);
            
            // Replace placeholders in message
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
                    $generator->discount_amount . ($generator->discount_type === 'percentage' ? '%' : ' EUR'),
                    $generator->expiry_days ? date_i18n(get_option('date_format'), strtotime('+' . $generator->expiry_days . ' days')) : __('No expiry', 'gf-wc-coupon-generator')
                ),
                $message
            );

            // Ensure proper HTML formatting
            if (strpos($message, '<') !== false) {
                $message = wpautop($message);
            }

            // Set email headers
            $from_name = $generator->email_from_name ?: get_bloginfo('name');
            $from_email = $generator->email_from_email ?: get_bloginfo('admin_email');
            
            // Create and send email
            $email = new GFWCG_Email($to, $subject, $message, $from_name, $from_email);
            $result = $email->send();
            
            error_log('GFWCG: Email sending process completed with result: ' . ($result ? 'success' : 'failed'));
        } else {
            error_log('GFWCG: Email sending is disabled for this generator');
        }
    }

    private function get_generator_by_form_id($form_id) {
        global $wpdb;
        error_log('GFWCG: Getting generator for form ID: ' . $form_id);
        
        // Get the generator ID from the form submission
        $generator_id = isset($_POST['gfwcg_generator_id']) ? intval($_POST['gfwcg_generator_id']) : 0;
        error_log('GFWCG: Generator ID from form: ' . $generator_id);
        
        if ($generator_id > 0) {
            // If we have a specific generator ID, use that
            $generator = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}gfwcg_generators 
                WHERE id = %d AND form_id = %d AND status = 'active'",
                $generator_id,
                $form_id
            ));
            
            if ($generator) {
                error_log('GFWCG: Found generator by ID: ' . $generator_id);
                return $generator;
            }
        }
        
        // Fallback to getting the most recent generator
        $generator = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gfwcg_generators 
            WHERE form_id = %d AND status = 'active' 
            ORDER BY id DESC LIMIT 1",
            $form_id
        ));
        
        if (!$generator) {
            error_log('GFWCG: No active generator found for form ID: ' . $form_id);
            return null;
        }
        
        error_log('GFWCG: Found generator ID: ' . $generator->id);
        error_log('GFWCG: Generator details: ' . print_r($generator, true));
        
        return $generator;
    }

    private function generate_coupon_code($generator, $entry) {
        if ($generator->coupon_type === 'field' && $generator->coupon_field_id) {
            return rgar($entry, $generator->coupon_field_id);
        }

        $prefix = $generator->coupon_prefix ?: '';
        $suffix = $generator->coupon_suffix ?: '';
        $separator = $generator->coupon_separator ?: '';
        $length = $generator->coupon_length ?: 8;

        $random = wp_generate_password($length, false);
        return $prefix . $separator . $random . $separator . $suffix;
    }

    private function create_woocommerce_coupon($code, $generator) {
        $coupon = new WC_Coupon();
        $coupon->set_code($code);
        
        // Map our discount type to WooCommerce's expected values
        $discount_type = $generator->discount_type;
        if ($discount_type === 'percentage') {
            $discount_type = 'percent';
        } elseif ($discount_type === 'fixed_cart') {
            $discount_type = 'fixed_cart';
        } else {
            $discount_type = 'fixed_cart'; // Default to fixed cart if unknown
        }
        
        $coupon->set_discount_type($discount_type);
        $coupon->set_amount($generator->discount_amount);
        $coupon->set_individual_use($generator->individual_use);
        $coupon->set_usage_limit($generator->usage_limit_per_coupon);
        $coupon->set_usage_limit_per_user($generator->usage_limit_per_user);
        $coupon->set_minimum_amount($generator->minimum_amount);
        $coupon->set_maximum_amount($generator->maximum_amount);
        $coupon->set_exclude_sale_items($generator->exclude_sale_items);
        
        if ($generator->expiry_days) {
            $expiry_date = date('Y-m-d', strtotime('+' . $generator->expiry_days . ' days'));
            $coupon->set_date_expires($expiry_date);
        }
        
        if ($generator->allow_free_shipping) {
            $coupon->set_free_shipping(true);
        }
        
        return $coupon->save();
    }

    private function get_default_email_template($coupon_code, $generator) {
        $template = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">';
        $template .= '<h2 style="color: #333; margin-bottom: 20px;">' . __('Your Coupon Details', 'gf-wc-coupon-generator') . '</h2>';
        $template .= '<div style="background: #f8f8f8; padding: 20px; border-radius: 5px; margin-bottom: 20px;">';
        $template .= '<p style="margin: 0 0 10px;"><strong>' . __('Coupon Code:', 'gf-wc-coupon-generator') . '</strong> <span style="font-size: 18px; color: #204ce5;">' . $coupon_code . '</span></p>';
        $template .= '<p style="margin: 0 0 10px;"><strong>' . __('Discount:', 'gf-wc-coupon-generator') . '</strong> ' . $generator->discount_amount . ' ' . ($generator->discount_type === 'percentage' ? '%' : 'EUR') . '</p>';
        
        if ($generator->expiry_days) {
            $expiry_date = date_i18n(get_option('date_format'), strtotime('+' . $generator->expiry_days . ' days'));
            $template .= '<p style="margin: 0 0 10px;"><strong>' . __('Valid Until:', 'gf-wc-coupon-generator') . '</strong> ' . $expiry_date . '</p>';
        }
        
        if ($generator->minimum_amount > 0) {
            $template .= '<p style="margin: 0 0 10px;"><strong>' . __('Minimum Order Amount:', 'gf-wc-coupon-generator') . '</strong> ' . $generator->minimum_amount . ' EUR</p>';
        }
        
        if ($generator->allow_free_shipping) {
            $template .= '<p style="margin: 0 0 10px;"><strong>' . __('Free Shipping:', 'gf-wc-coupon-generator') . '</strong> ' . __('Yes', 'gf-wc-coupon-generator') . '</p>';
        }
        
        $template .= '</div>';
        $template .= '<p style="text-align: center; margin-top: 20px;">' . __('Thank you for your submission!', 'gf-wc-coupon-generator') . '</p>';
        $template .= '<p style="text-align: center;">' . __('Happy shopping!', 'gf-wc-coupon-generator') . '</p>';
        $template .= '</div>';
        
        return $template;
    }
} 