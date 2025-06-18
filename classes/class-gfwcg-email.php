<?php
/**
 * Email Class
 *
 * @package Gravity Forms WooCommerce Coupon Generator
 */

if (!defined('ABSPATH')) {
	exit;
}

// Include WooCommerce email classes
if (!class_exists('WC_Email')) {
	require_once(WC()->plugin_path() . '/includes/emails/class-wc-email.php');
}

/**
 * Email Class
 */
class GFWCG_Email extends WC_Email {
	public $placeholders = array();
	public $email_message;
	public $from_name;
	public $from_email;

	public function __construct() {
		// Set email identification and description
		$this->id = 'gfwcg_coupon';
		$this->title = __('Gravity Forms WooCommerce Coupon', 'gravity-forms-woocommerce-coupon-generator');
		$this->description = __('This email is sent when a coupon is generated through a Gravity Form.', 'gravity-forms-woocommerce-coupon-generator');
		$this->customer_email = true;
		$this->heading = __('Your Coupon Code', 'gravity-forms-woocommerce-coupon-generator');
		$this->subject = __('Your Coupon Code', 'gravity-forms-woocommerce-coupon-generator');

		// Let WooCommerce handle template loading
		$this->template_base = WC()->plugin_path() . '/templates/';
		$this->template_html = 'emails/customer-coupon.php';
		$this->template_plain = 'emails/plain/customer-coupon.php';

		$this->placeholders = array(
			'{coupon_code}' => '',
			'{discount_amount}' => '',
			'{expiry_date}' => '',
		);

		// Call parent constructor
		parent::__construct();
	}

	/**
	 * Send coupon email
	 *
	 * @param object $generator The generator object
	 * @param string $to The recipient email address
	 * @param string $coupon_code The generated coupon code
	 * @return bool Whether the email was sent successfully
	 */
	public function send_coupon_email($generator, $to, $coupon_code) {
		if (!$this->is_enabled() || !$to || !$generator->send_email) {
			return false;
		}

		// Prepare email content
		$email_subject = $generator->email_subject ?: __('Your Coupon Code', 'gravity-forms-woocommerce-coupon-generator');
		$email_message = $generator->email_message ?: __('Your coupon code is: {coupon_code}', 'gravity-forms-woocommerce-coupon-generator');
		
		// Process placeholders
		$placeholders = array(
			'coupon_code' => $coupon_code,
			'discount_amount' => number_format($generator->discount_amount, 2, '.', '') . ($generator->discount_type === 'percentage' ? '%' : ''),
			'expiry_date' => $generator->expiry_days ? date_i18n(get_option('date_format'), strtotime('+' . $generator->expiry_days . ' days')) : __('No expiry', 'gravity-forms-woocommerce-coupon-generator'),
			'site_name' => get_bloginfo('name')
		);

		// Send the email
		return $this->trigger(
			$to,
			$email_subject,
			$email_message,
			$generator->email_from_name,
			$generator->email_from_email,
			$placeholders,
			$generator->use_wc_email_template
		);
	}

	public function trigger($to, $subject, $message, $from_name = '', $from_email = '', $placeholders = array(), $use_wc_template = true) {
		if (!$this->is_enabled() || !$to) {
			return false;
		}

		$this->setup_locale();
		
		// Set placeholders
		$this->placeholders = $placeholders;
		
		// Process placeholders in subject and message
		$this->subject = $this->process_placeholders($subject, $placeholders);
		$this->email_message = $this->process_placeholders($message, $placeholders);
		
		// Set from name and email if provided
		if ($from_name && $from_email) {
			$this->from_name = $from_name;
			$this->from_email = $from_email;
		}

		// Set recipient
		$this->recipient = $to;

		// Send the email via WooCommerce's email system
		$result = $this->send($this->get_recipient(), $this->get_subject(), $this->get_content($use_wc_template), $this->get_headers(), array());
		
		$this->restore_locale();
		return $result;
	}

	public function get_content($use_wc_template = true) {
		if ($use_wc_template) {
			return $this->get_content_html();
		} else {
			return $this->email_message;
		}
	}

	public function get_content_html() {
		ob_start();
		wc_get_template('emails/email-header.php', array('email_heading' => $this->get_heading()));
		echo wpautop(wptexturize($this->email_message));
		wc_get_template('emails/email-footer.php');
		return ob_get_clean();
	}

	public function get_content_plain() {
		return strip_tags($this->email_message);
	}

	private function process_placeholders($text, $placeholders) {
		foreach ($placeholders as $key => $value) {
			$text = str_replace('{' . $key . '}', $value, $text);
		}
		return $text;
	}

	/**
	 * Get default email template
	 *
	 * @return string The default email template
	 */
	public static function get_default_template() {
		return sprintf(
			'<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
				<h2 style="color: #333; margin-bottom: 20px;">%s</h2>
				<div style="background: #f8f8f8; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
					<p style="margin: 0 0 10px;"><strong>%s:</strong> <span style="font-size: 18px; color: #204ce5;">{coupon_code}</span></p>
					<p style="margin: 0 0 10px;"><strong>%s:</strong> {discount_amount}</p>
					<p style="margin: 0 0 10px;"><strong>%s:</strong> {expiry_date}</p>
				</div>
				<p style="text-align: center; margin-top: 20px;">%s</p>
				<p style="text-align: center;">%s</p>
			</div>',
			__('Your Coupon Details', 'gravity-forms-woocommerce-coupon-generator'),
			__('Coupon Code', 'gravity-forms-woocommerce-coupon-generator'),
			__('Discount', 'gravity-forms-woocommerce-coupon-generator'),
			__('Valid Until', 'gravity-forms-woocommerce-coupon-generator'),
			__('Thank you for your submission!', 'gravity-forms-woocommerce-coupon-generator'),
			__('Happy shopping!', 'gravity-forms-woocommerce-coupon-generator')
		);
	}
} 