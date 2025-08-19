<?php
/**
 * Email Class
 *
 * @package Gravity Forms WooCommerce Coupon Generator
 */

if (!defined('ABSPATH')) {
	exit;
}

// WooCommerce email classes are loaded by WooCommerce

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
			'{site_name}' => '',
			'{discount_amount}' => '',
			'{expiry_date}' => '',
			'{discount_type}' => '',
			'{individual_use}' => '',
			'{usage_limit_per_coupon}' => '',
			'{usage_limit_per_user}' => '',
			'{minimum_amount}' => '',
			'{maximum_amount}' => '',
			'{exclude_sale_items}' => '',
			'{allow_free_shipping}' => '',
			'{expiry_days}' => '',
			'{products}' => '',
			'{exclude_products}' => '',
			'{product_categories}' => '',
			'{exclude_categories}' => '',
			'{product_tags}' => '',
			'{exclude_product_tags}' => '',
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

		// Process placeholders with all coupon restrictions
		$placeholders = $this->prepare_placeholders($generator, $coupon_code);

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

	/**
	 * Prepare all placeholders with coupon restrictions
	 *
	 * @param object $generator The generator object
	 * @param string $coupon_code The generated coupon code
	 * @return array Array of placeholders
	 */
	private function prepare_placeholders($generator, $coupon_code) {
		$placeholders = array(
			'coupon_code' => $coupon_code,
			'site_name' => get_bloginfo('name'),
			'discount_amount' => $this->format_discount_amount($generator->discount_amount, $generator->discount_type),
			'discount_type' => $this->get_discount_type_label($generator->discount_type),
			'expiry_date' => $generator->expiry_days ? date_i18n(get_option('date_format'), strtotime('+' . $generator->expiry_days . ' days')) : __('No expiry', 'gravity-forms-woocommerce-coupon-generator'),
			'expiry_days' => $generator->expiry_days ? $generator->expiry_days : __('No expiry', 'gravity-forms-woocommerce-coupon-generator'),
			'individual_use' => $generator->individual_use ? __('Yes', 'gravity-forms-woocommerce-coupon-generator') : __('No', 'gravity-forms-woocommerce-coupon-generator'),
			'usage_limit_per_coupon' => $generator->usage_limit_per_coupon ? $generator->usage_limit_per_coupon : __('Unlimited', 'gravity-forms-woocommerce-coupon-generator'),
			'usage_limit_per_user' => $generator->usage_limit_per_user ? $generator->usage_limit_per_user : __('Unlimited', 'gravity-forms-woocommerce-coupon-generator'),
			'minimum_amount' => $generator->minimum_amount ? $this->format_currency($generator->minimum_amount) : __('No minimum', 'gravity-forms-woocommerce-coupon-generator'),
			'maximum_amount' => $generator->maximum_amount ? $this->format_currency($generator->maximum_amount) : __('No maximum', 'gravity-forms-woocommerce-coupon-generator'),
			'exclude_sale_items' => $generator->exclude_sale_items ? __('Yes', 'gravity-forms-woocommerce-coupon-generator') : __('No', 'gravity-forms-woocommerce-coupon-generator'),
			'allow_free_shipping' => $generator->allow_free_shipping ? __('Yes', 'gravity-forms-woocommerce-coupon-generator') : __('No', 'gravity-forms-woocommerce-coupon-generator'),
			'products' => $this->format_product_list($generator->product_ids),
			'exclude_products' => $this->format_product_list($generator->exclude_products),
			'product_categories' => $this->format_category_list($generator->product_categories),
			'exclude_categories' => $this->format_category_list($generator->exclude_categories),
			'product_tags' => $this->format_tag_list($generator->product_tags),
			'exclude_product_tags' => $this->format_tag_list($generator->exclude_product_tags),
		);

		return $placeholders;
	}

	/**
	 * Format discount amount with proper currency
	 *
	 * @param float $amount The discount amount
	 * @param string $type The discount type
	 * @return string Formatted discount amount
	 */
	private function format_discount_amount($amount, $type) {
		if ($type === 'percentage') {
			return number_format($amount, 2, '.', '') . '%';
		} else {
			return $this->format_currency($amount);
		}
	}

	/**
	 * Format currency with proper Romanian currency (Lei)
	 *
	 * @param float $amount The amount to format
	 * @return string Formatted currency
	 */
	private function format_currency($amount) {
		// Check if WooCommerce is active and has currency settings
		if (function_exists('wc_price')) {
			return wc_price($amount);
		}

		// Fallback formatting for Romanian currency
		$locale = get_locale();
		if (strpos($locale, 'ro') === 0) {
			// Romanian formatting
			return number_format($amount, 2, ',', '.') . ' Lei';
		}

		// Default formatting
		return number_format($amount, 2, '.', '') . ' ' . get_woocommerce_currency();
	}

	/**
	 * Get localized discount type label
	 *
	 * @param string $type The discount type
	 * @return string Localized discount type
	 */
	private function get_discount_type_label($type) {
		$labels = array(
			'percentage' => __('Percentage', 'gravity-forms-woocommerce-coupon-generator'),
			'fixed_cart' => __('Fixed cart discount', 'gravity-forms-woocommerce-coupon-generator'),
			'fixed_product' => __('Fixed product discount', 'gravity-forms-woocommerce-coupon-generator'),
		);

		return isset($labels[$type]) ? $labels[$type] : $type;
	}

	/**
	 * Format product list for display with links
	 *
	 * @param string $product_ids Serialized product IDs
	 * @return string Formatted product list with links
	 */
	private function format_product_list($product_ids) {
		if (empty($product_ids)) {
			return __('All products', 'gravity-forms-woocommerce-coupon-generator');
		}

		$ids = maybe_unserialize($product_ids);
		if (!is_array($ids) || empty($ids)) {
			return __('All products', 'gravity-forms-woocommerce-coupon-generator');
		}

		$product_links = array();
		foreach ($ids as $product_id) {
			$product = wc_get_product($product_id);
			if ($product) {
				$product_url = get_permalink($product->get_id());
				$product_name = $product->get_name();
				$product_links[] = sprintf('<a href="%s">%s</a>', esc_url($product_url), esc_html($product_name));
			}
		}

		return !empty($product_links) ? implode(', ', $product_links) : __('All products', 'gravity-forms-woocommerce-coupon-generator');
	}

	/**
	 * Format category list for display with links
	 *
	 * @param string $category_ids Serialized category IDs
	 * @return string Formatted category list with links
	 */
	private function format_category_list($category_ids) {
		if (empty($category_ids)) {
			return __('All categories', 'gravity-forms-woocommerce-coupon-generator');
		}

		$ids = maybe_unserialize($category_ids);
		if (!is_array($ids) || empty($ids)) {
			return __('All categories', 'gravity-forms-woocommerce-coupon-generator');
		}

		$category_links = array();
		foreach ($ids as $category_id) {
			$category = get_term($category_id, 'product_cat');
			if ($category && !is_wp_error($category)) {
				$category_url = get_term_link($category, 'product_cat');
				if (!is_wp_error($category_url)) {
					$category_links[] = sprintf('<a href="%s">%s</a>', esc_url($category_url), esc_html($category->name));
				} else {
					$category_links[] = esc_html($category->name);
				}
			}
		}

		return !empty($category_links) ? implode(', ', $category_links) : __('All categories', 'gravity-forms-woocommerce-coupon-generator');
	}

	/**
	 * Format tag list for display with links
	 *
	 * @param string $tag_ids Serialized tag IDs
	 * @return string Formatted tag list with links
	 */
	private function format_tag_list($tag_ids) {
		if (empty($tag_ids)) {
			return __('Any tags', 'gravity-forms-woocommerce-coupon-generator');
		}

		$ids = maybe_unserialize($tag_ids);
		if (!is_array($ids) || empty($ids)) {
			return __('Any tags', 'gravity-forms-woocommerce-coupon-generator');
		}

		$tag_links = array();
		foreach ($ids as $tag_id) {
			$tag = get_term($tag_id, 'product_tag');
			if ($tag && !is_wp_error($tag)) {
				$tag_url = get_term_link($tag, 'product_tag');
				if (!is_wp_error($tag_url)) {
					$tag_links[] = sprintf('<a href="%s">%s</a>', esc_url($tag_url), esc_html($tag->name));
				} else {
					$tag_links[] = esc_html($tag->name);
				}
			}
		}

		return !empty($tag_links) ? implode(', ', $tag_links) : __('Any tags', 'gravity-forms-woocommerce-coupon-generator');
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
					<p style="margin: 0 0 10px;"><strong>%s:</strong> {minimum_amount}</p>
					<p style="margin: 0 0 10px;"><strong>%s:</strong> {usage_limit_per_coupon}</p>
					<p style="margin: 0 0 10px;"><strong>%s:</strong> {products}</p>
				</div>
				<p style="text-align: center; margin-top: 20px;">%s</p>
				<p style="text-align: center;">%s</p>
			</div>',
			__('Your Coupon Details', 'gravity-forms-woocommerce-coupon-generator'),
			__('Coupon Code', 'gravity-forms-woocommerce-coupon-generator'),
			__('Discount', 'gravity-forms-woocommerce-coupon-generator'),
			__('Valid Until', 'gravity-forms-woocommerce-coupon-generator'),
			__('Minimum Spend', 'gravity-forms-woocommerce-coupon-generator'),
			__('Usage Limit', 'gravity-forms-woocommerce-coupon-generator'),
			__('Applicable Products', 'gravity-forms-woocommerce-coupon-generator'),
			__('Thank you for your submission!', 'gravity-forms-woocommerce-coupon-generator'),
			__('Happy shopping!', 'gravity-forms-woocommerce-coupon-generator')
		);
	}
}
