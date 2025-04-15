<?php
/**
 * Email Class
 *
 * @package Gravity Forms WooCommerce Coupon Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Email Class
 */
class GFWCG_Email {
    private $to;
    private $subject;
    private $message;
    private $from_name;
    private $from_email;
    private $headers;
    private $is_ajax;

    public function __construct($to, $subject, $message, $from_name = '', $from_email = '') {
        $this->to = $to;
        $this->subject = $subject;
        $this->message = $message;
        $this->from_name = $from_name ?: get_bloginfo('name');
        $this->from_email = $from_email ?: get_bloginfo('admin_email');
        $this->is_ajax = defined('DOING_AJAX') && DOING_AJAX;
        $this->set_headers();
    }

    private function set_headers() {
        $this->headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->from_name . ' <' . $this->from_email . '>',
            'Reply-To: ' . $this->from_name . ' <' . $this->from_email . '>'
        );
    }

    public function send() {
        // Don't log during AJAX requests to avoid polluting the response
        if (!$this->is_ajax) {
            error_log('GFWCG: Starting email sending process');
            error_log('GFWCG: Email configuration:');
            error_log('GFWCG: - To: ' . $this->to);
            error_log('GFWCG: - From: ' . $this->from_name . ' <' . $this->from_email . '>');
            error_log('GFWCG: - Subject: ' . $this->subject);
            error_log('GFWCG: - Message: ' . $this->message);
            error_log('GFWCG: - Headers: ' . print_r($this->headers, true));
        }

        try {
            // Try WooCommerce email first
            if (function_exists('WC') && class_exists('WC_Email')) {
                if (!$this->is_ajax) {
                    error_log('GFWCG: Attempting to use WooCommerce email system');
                }
                
                // Initialize WooCommerce
                WC();
                
                if (!class_exists('WC_Email')) {
                    if (!$this->is_ajax) {
                        error_log('GFWCG: WC_Email class not found - Loading WooCommerce includes');
                    }
                    require_once(WC()->plugin_path() . '/includes/emails/class-wc-email.php');
                }

                $mailer = WC()->mailer();
                if (!$mailer) {
                    if (!$this->is_ajax) {
                        error_log('GFWCG: WC mailer not initialized');
                    }
                    throw new Exception('WC mailer not initialized');
                }

                if (!$this->is_ajax) {
                    error_log('GFWCG: WooCommerce mailer initialized successfully');
                }
                
                // Send the email using WooCommerce mailer
                $result = $mailer->send($this->to, $this->subject, $this->message, $this->headers);
                if (!$this->is_ajax) {
                    error_log('GFWCG: WC email send result: ' . ($result ? 'success' : 'failed'));
                }
                
                if ($result) {
                    return true;
                }
            }

            // Fallback to wp_mail
            if (!$this->is_ajax) {
                error_log('GFWCG: Falling back to wp_mail');
            }
            
            // Use wp_mail with error suppression during AJAX
            if ($this->is_ajax) {
                $wp_mail_result = @wp_mail($this->to, $this->subject, $this->message, $this->headers);
            } else {
                $wp_mail_result = wp_mail($this->to, $this->subject, $this->message, $this->headers);
                error_log('GFWCG: wp_mail result: ' . ($wp_mail_result ? 'success' : 'failed'));
            }
            
            return $wp_mail_result;
            
        } catch (Exception $e) {
            if (!$this->is_ajax) {
                error_log('GFWCG: Exception in email sending: ' . $e->getMessage());
                error_log('GFWCG: Stack trace: ' . $e->getTraceAsString());
                error_log('GFWCG: Final fallback to wp_mail');
            }
            
            // Final fallback to wp_mail with error suppression during AJAX
            if ($this->is_ajax) {
                return @wp_mail($this->to, $this->subject, $this->message, $this->headers);
            } else {
                return wp_mail($this->to, $this->subject, $this->message, $this->headers);
            }
        }
    }

    /**
     * Get email subject
     *
     * @param object $generator The generator object
     * @param string $coupon_code The generated coupon code
     * @return string The email subject
     */
    private static function get_email_subject($generator, $coupon_code) {
        $subject = $generator->email_subject ?: __('Your Coupon Code', 'gravity-forms-woocommerce-coupon-generator');
        
        // Replace placeholders
        $subject = str_replace(
            array('{coupon_code}', '{site_name}'),
            array($coupon_code, get_bloginfo('name')),
            $subject
        );

        return $subject;
    }

    /**
     * Get email message
     *
     * @param object $generator The generator object
     * @param string $coupon_code The generated coupon code
     * @return string The email message
     */
    private static function get_email_message($generator, $coupon_code) {
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

        // Add HTML formatting if needed
        if (strpos($message, '<') !== false) {
            $message = wpautop($message);
        }

        return $message;
    }

    /**
     * Get email headers
     *
     * @param object $generator The generator object
     * @return array The email headers
     */
    private static function get_email_headers($generator) {
        $headers = array();

        // Set From header
        $from_name = $generator->email_from_name ?: get_bloginfo('name');
        $from_email = $generator->email_from_email ?: get_bloginfo('admin_email');
        $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';

        // Set Content-Type header
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        return $headers;
    }

    /**
     * Get default email template
     *
     * @return string The default email template
     */
    public static function get_default_template() {
        return sprintf(
            '<p>%s</p>
            <p><strong>%s: {coupon_code}</strong></p>
            <p>%s: {discount_amount}</p>
            <p>%s: {expiry_date}</p>
            <p>%s</p>',
            __('Thank you for your submission!', 'gravity-forms-woocommerce-coupon-generator'),
            __('Your coupon code is', 'gravity-forms-woocommerce-coupon-generator'),
            __('Discount amount', 'gravity-forms-woocommerce-coupon-generator'),
            __('Expiry date', 'gravity-forms-woocommerce-coupon-generator'),
            __('Happy shopping!', 'gravity-forms-woocommerce-coupon-generator')
        );
    }
} 