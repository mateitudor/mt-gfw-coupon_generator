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
            $this->send_coupon_email($entry, $generator, $coupon_code);
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
        $coupon->set_discount_type($generator->discount_type);
        $coupon->set_amount($generator->discount_amount);
        $coupon->set_individual_use($generator->individual_use);
        $coupon->set_usage_limit($generator->usage_limit_per_coupon);
        $coupon->set_usage_limit_per_user($generator->usage_limit_per_user);
        $coupon->set_free_shipping($generator->allow_free_shipping);
        $coupon->set_exclude_sale_items($generator->exclude_sale_items);

        if ($generator->expiry_date) {
            $coupon->set_date_expires(strtotime($generator->expiry_date));
        } elseif ($generator->expiry_days) {
            $coupon->set_date_expires(strtotime('+' . $generator->expiry_days . ' days'));
        }

        if ($generator->minimum_spend) {
            $coupon->set_minimum_amount($generator->minimum_spend);
        }

        if ($generator->maximum_spend) {
            $coupon->set_maximum_amount($generator->maximum_spend);
        }

        if ($generator->products) {
            $product_ids = array_map('intval', explode(',', $generator->products));
            $coupon->set_product_ids($product_ids);
        }

        if ($generator->exclude_products) {
            $exclude_product_ids = array_map('intval', explode(',', $generator->exclude_products));
            $coupon->set_excluded_product_ids($exclude_product_ids);
        }

        if ($generator->product_categories) {
            $category_ids = array_map('intval', explode(',', $generator->product_categories));
            $coupon->set_product_categories($category_ids);
        }

        if ($generator->exclude_categories) {
            $exclude_category_ids = array_map('intval', explode(',', $generator->exclude_categories));
            $coupon->set_excluded_product_categories($exclude_category_ids);
        }

        if ($generator->allowed_emails) {
            $emails = array_map('trim', explode(',', $generator->allowed_emails));
            $coupon->set_email_restrictions($emails);
        }

        return $coupon->save();
    }

    private function send_coupon_email($entry, $generator, $coupon_code) {
        // Don't log during AJAX requests
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            error_log('GFWCG: Starting email sending process');
            error_log('GFWCG: Entry data: ' . print_r($entry, true));
            error_log('GFWCG: Generator data: ' . print_r($generator, true));
            error_log('GFWCG: Coupon code: ' . $coupon_code);
        }

        $to = rgar($entry, $generator->email_field_id);
        if (!$to) {
            if (!defined('DOING_AJAX') || !DOING_AJAX) {
                error_log('GFWCG: No email address found for recipient in field ' . $generator->email_field_id);
                error_log('GFWCG: Available entry fields: ' . print_r(array_keys($entry), true));
            }
            return;
        }

        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            error_log('GFWCG: Recipient email: ' . $to);
            error_log('GFWCG: Using generator ID: ' . $generator->id);
        }

        $subject = $generator->email_subject ?: __('Your Coupon Code', 'gf-wc-coupon-generator');
        
        // Build message with coupon information
        $message = '<div style="font-family: Arial, sans-serif;">';
        $message .= '<h2>' . __('Your Coupon Details', 'gf-wc-coupon-generator') . '</h2>';
        $message .= '<p><strong>' . __('Coupon Code:', 'gf-wc-coupon-generator') . '</strong> ' . $coupon_code . '</p>';
        $message .= '<p><strong>' . __('Discount:', 'gf-wc-coupon-generator') . '</strong> ' . $generator->discount_amount . ' ' . ($generator->discount_type === 'fixed_cart' ? __('EUR', 'gf-wc-coupon-generator') : '%') . '</p>';
        
        if ($generator->expiry_days) {
            $expiry_date = date('Y-m-d', strtotime('+' . $generator->expiry_days . ' days'));
            $message .= '<p><strong>' . __('Valid Until:', 'gf-wc-coupon-generator') . '</strong> ' . $expiry_date . '</p>';
        }
        
        if ($generator->minimum_spend > 0) {
            $message .= '<p><strong>' . __('Minimum Order Amount:', 'gf-wc-coupon-generator') . '</strong> ' . $generator->minimum_spend . ' EUR</p>';
        }
        
        if ($generator->allow_free_shipping) {
            $message .= '<p><strong>' . __('Free Shipping:', 'gf-wc-coupon-generator') . '</strong> ' . __('Yes', 'gf-wc-coupon-generator') . '</p>';
        }
        
        $message .= '</div>';

        $from_name = $generator->email_from_name ?: get_bloginfo('name');
        $from_email = $generator->email_from_email ?: get_bloginfo('admin_email');

        // If this is an AJAX request, schedule the email to be sent after the response
        if (defined('DOING_AJAX') && DOING_AJAX) {
            add_action('shutdown', function() use ($to, $subject, $message, $from_name, $from_email) {
                $email = new GFWCG_Email($to, $subject, $message, $from_name, $from_email);
                $email->send();
            });
            return true;
        }

        // For non-AJAX requests, send immediately
        $email = new GFWCG_Email($to, $subject, $message, $from_name, $from_email);
        $result = $email->send();
        
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            error_log('GFWCG: Email sending process completed with result: ' . ($result ? 'success' : 'failed'));
        }
        
        return $result;
    }
} 