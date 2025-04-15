<?php

class GFWCG_Coupon {
    public function __construct() {
        add_action('gform_after_submission', array($this, 'handle_form_submission'), 10, 2);
    }

    public function handle_form_submission($entry, $form) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gfwcg_generators';
        
        // Get active generator for this form
        $generator = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE form_id = %d",
            $form['id']
        ));

        if (!$generator) {
            return;
        }

        // Get form field values
        $email = rgar($entry, $generator->email_field_id);
        $name = $generator->name_field_id ? rgar($entry, $generator->name_field_id) : '';

        if (!$email) {
            return;
        }

        // Generate coupon code
        $coupon_code = $this->generate_coupon_code($generator);

        // Create WooCommerce coupon
        $coupon = new WC_Coupon();
        $coupon->set_code($coupon_code);
        $coupon->set_discount_type('fixed_cart');
        $coupon->set_amount(10); // Default amount, can be configured in generator
        $coupon->set_individual_use($generator->individual_use);
        $coupon->set_usage_limit($generator->usage_limit);
        $coupon->set_usage_limit_per_user($generator->usage_limit_per_user);
        $coupon->set_minimum_amount($generator->minimum_amount);
        $coupon->set_maximum_amount($generator->maximum_amount);
        $coupon->set_exclude_sale_items($generator->exclude_sale_items);
        $coupon->set_email_restrictions(array($email));
        $coupon->save();

        // Send email if configured
        if ($generator->email_subject && $generator->email_message) {
            $this->send_coupon_email($email, $name, $coupon_code, $generator);
        }
    }

    private function generate_coupon_code($generator) {
        $length = $generator->coupon_length ?: 8;
        $prefix = $generator->coupon_prefix ?: '';
        $suffix = $generator->coupon_suffix ?: '';

        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $prefix . $code . $suffix;
    }

    private function send_coupon_email($email, $name, $coupon_code, $generator) {
        $subject = $generator->email_subject;
        $message = $generator->email_message;

        // Replace placeholders
        $message = str_replace('{coupon_code}', $coupon_code, $message);
        $message = str_replace('{name}', $name, $message);

        $headers = array();
        if ($generator->email_from_name && $generator->email_from_email) {
            $headers[] = 'From: ' . $generator->email_from_name . ' <' . $generator->email_from_email . '>';
        }

        wp_mail($email, $subject, $message, $headers);
    }
} 