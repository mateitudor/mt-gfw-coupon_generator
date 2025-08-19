<?php
/**
 * Shared placeholders builder and renderer
 *
 * @package Gravity Forms WooCommerce Coupon Generator
 */

if (!defined('ABSPATH')) {
	exit;
}

class GFWCG_Placeholders {
	/**
	 * Build all available placeholders from a generator
	 *
	 * @param object $generator Generator row object
	 * @param string $coupon_code Optional coupon code
	 * @return array<string,string>
	 */
	public static function build($generator, $coupon_code = '') {
		return array(
			'coupon_code' => $coupon_code,
			'site_name' => get_bloginfo('name'),
			'discount_amount' => self::format_discount_amount($generator->discount_amount, $generator->discount_type),
			'discount_type' => self::get_discount_type_label($generator->discount_type),
			'expiry_date' => $generator->expiry_days ? date_i18n(get_option('date_format'), strtotime('+' . intval($generator->expiry_days) . ' days')) : __('No expiry', 'gravity-forms-woocommerce-coupon-generator'),
			'expiry_days' => $generator->expiry_days ? intval($generator->expiry_days) : __('No expiry', 'gravity-forms-woocommerce-coupon-generator'),
			'individual_use' => $generator->individual_use ? __('Yes', 'gravity-forms-woocommerce-coupon-generator') : __('No', 'gravity-forms-woocommerce-coupon-generator'),
			'usage_limit_per_coupon' => $generator->usage_limit_per_coupon ? intval($generator->usage_limit_per_coupon) : __('Unlimited', 'gravity-forms-woocommerce-coupon-generator'),
			'usage_limit_per_user' => $generator->usage_limit_per_user ? intval($generator->usage_limit_per_user) : __('Unlimited', 'gravity-forms-woocommerce-coupon-generator'),
			'minimum_amount' => $generator->minimum_amount ? self::format_currency($generator->minimum_amount) : __('No minimum', 'gravity-forms-woocommerce-coupon-generator'),
			'maximum_amount' => $generator->maximum_amount ? self::format_currency($generator->maximum_amount) : __('No maximum', 'gravity-forms-woocommerce-coupon-generator'),
			'exclude_sale_items' => $generator->exclude_sale_items ? __('Yes', 'gravity-forms-woocommerce-coupon-generator') : __('No', 'gravity-forms-woocommerce-coupon-generator'),
			'allow_free_shipping' => $generator->allow_free_shipping ? __('Yes', 'gravity-forms-woocommerce-coupon-generator') : __('No', 'gravity-forms-woocommerce-coupon-generator'),
			'products' => self::format_product_list($generator->product_ids),
			'exclude_products' => self::format_product_list($generator->exclude_products),
			'product_categories' => self::format_category_list($generator->product_categories),
			'exclude_categories' => self::format_category_list($generator->exclude_categories),
			'product_tags' => self::format_tag_list($generator->product_tags),
			'exclude_product_tags' => self::format_tag_list($generator->exclude_product_tags),
		);
	}

	/**
	 * Replace placeholders in a text
	 *
	 * @param string $text
	 * @param array<string,string> $placeholders
	 * @return string
	 */
	public static function replace($text, $placeholders) {
		if (!is_string($text) || empty($text)) {
			return '';
		}
		foreach ($placeholders as $key => $value) {
			$text = str_replace('{' . $key . '}', (string)$value, $text);
		}
		return $text;
	}

	/**
	 * Get default frontend template (restrictions overview)
	 *
	 * @return string
	 */
	public static function get_default_frontend_template() {
		return sprintf(
			'<h4>%s</h4>
			<ul>
				<li><strong>%s</strong> {discount_amount} ({discount_type})</li>
				<li><strong>%s</strong> {expiry_date}</li>
				<li><strong>%s</strong> {minimum_amount}</li>
				<li><strong>%s</strong> {maximum_amount}</li>
				<li><strong>%s</strong> {individual_use}</li>
				<li><strong>%s</strong> {usage_limit_per_coupon}</li>
				<li><strong>%s</strong> {usage_limit_per_user}</li>
				<li><strong>%s</strong> {exclude_sale_items}</li>
				<li><strong>%s</strong> {allow_free_shipping}</li>
			</ul>
			<div class="gfwcg-restrictions-groups">
				<p><strong>%s</strong> {products}</p>
				<p><strong>%s</strong> {exclude_products}</p>
				<p><strong>%s</strong> {product_categories}</p>
				<p><strong>%s</strong> {exclude_categories}</p>
				<p><strong>%s</strong> {product_tags}</p>
				<p><strong>%s</strong> {exclude_product_tags}</p>
			</div>',
			__('Coupon Details', 'gravity-forms-woocommerce-coupon-generator'),
			__('Discount', 'gravity-forms-woocommerce-coupon-generator'),
			__('Valid Until', 'gravity-forms-woocommerce-coupon-generator'),
			__('Minimum Spend', 'gravity-forms-woocommerce-coupon-generator'),
			__('Maximum Spend', 'gravity-forms-woocommerce-coupon-generator'),
			__('Individual Use', 'gravity-forms-woocommerce-coupon-generator'),
			__('Usage Limit (Coupon)', 'gravity-forms-woocommerce-coupon-generator'),
			__('Usage Limit (User)', 'gravity-forms-woocommerce-coupon-generator'),
			__('Exclude Sale Items', 'gravity-forms-woocommerce-coupon-generator'),
			__('Allow Free Shipping', 'gravity-forms-woocommerce-coupon-generator'),
			__('Valid for products:', 'gravity-forms-woocommerce-coupon-generator'),
			__('Excluded products:', 'gravity-forms-woocommerce-coupon-generator'),
			__('Valid for categories:', 'gravity-forms-woocommerce-coupon-generator'),
			__('Excluded categories:', 'gravity-forms-woocommerce-coupon-generator'),
			__('Valid for tags:', 'gravity-forms-woocommerce-coupon-generator'),
			__('Excluded tags:', 'gravity-forms-woocommerce-coupon-generator')
		);
	}

	/**
	 * Admin help HTML for placeholders and examples (shared for email and frontend boxes)
	 *
	 * @return string
	 */
	public static function get_admin_placeholders_help_html() {
		ob_start();
		?>
		<div class="gfwcg-placeholder-help">
			<h4 style="margin-top:0;"><?php _e('Available placeholders', 'gravity-forms-woocommerce-coupon-generator'); ?></h4>
			<ul>
				<li><strong><?php _e('Basic', 'gravity-forms-woocommerce-coupon-generator'); ?></strong>: <code>{coupon_code}</code>, <code>{site_name}</code>, <code>{discount_amount}</code>, <code>{expiry_date}</code></li>
				<li><strong><?php _e('Discount Settings', 'gravity-forms-woocommerce-coupon-generator'); ?></strong>: <code>{discount_type}</code>, <code>{expiry_days}</code></li>
				<li><strong><?php _e('Usage Restrictions', 'gravity-forms-woocommerce-coupon-generator'); ?></strong>: <code>{individual_use}</code>, <code>{usage_limit_per_coupon}</code>, <code>{usage_limit_per_user}</code></li>
				<li><strong><?php _e('Amount Restrictions', 'gravity-forms-woocommerce-coupon-generator'); ?></strong>: <code>{minimum_amount}</code>, <code>{maximum_amount}</code></li>
				<li><strong><?php _e('Product Restrictions', 'gravity-forms-woocommerce-coupon-generator'); ?></strong>: <code>{exclude_sale_items}</code>, <code>{allow_free_shipping}</code>, <code>{products}</code>, <code>{exclude_products}</code></li>
				<li><strong><?php _e('Category Restrictions', 'gravity-forms-woocommerce-coupon-generator'); ?></strong>: <code>{product_categories}</code>, <code>{exclude_categories}</code></li>
				<li><strong><?php _e('Tag Restrictions', 'gravity-forms-woocommerce-coupon-generator'); ?></strong>: <code>{product_tags}</code>, <code>{exclude_product_tags}</code></li>
			</ul>
			<h4><?php _e('Placeholder Examples', 'gravity-forms-woocommerce-coupon-generator'); ?></h4>
			<ul>
				<li><code>{coupon_code}</code> → <em>WELCOME2024</em></li>
				<li><code>{discount_amount}</code> → <em>15.00%</em> <?php _e('or', 'gravity-forms-woocommerce-coupon-generator'); ?> <em>10.00</em></li>
				<li><code>{minimum_amount}</code> → <em>$25.00</em> <?php _e('or', 'gravity-forms-woocommerce-coupon-generator'); ?> <em><?php _e('No minimum', 'gravity-forms-woocommerce-coupon-generator'); ?></em></li>
				<li><code>{usage_limit_per_coupon}</code> → <em>1</em> <?php _e('or', 'gravity-forms-woocommerce-coupon-generator'); ?> <em><?php _e('Unlimited', 'gravity-forms-woocommerce-coupon-generator'); ?></em></li>
				<li><code>{products}</code> → <em>Product A, Product B</em> <?php _e('or', 'gravity-forms-woocommerce-coupon-generator'); ?> <em><?php _e('All products', 'gravity-forms-woocommerce-coupon-generator'); ?></em></li>
				<li><code>{individual_use}</code> → <em><?php _e('Yes', 'gravity-forms-woocommerce-coupon-generator'); ?></em> <?php _e('or', 'gravity-forms-woocommerce-coupon-generator'); ?> <em><?php _e('No', 'gravity-forms-woocommerce-coupon-generator'); ?></em></li>
			</ul>
		</div>
		<?php
		return ob_get_clean();
	}

	private static function format_discount_amount($amount, $type) {
		if ($type === 'percentage') {
			return number_format((float)$amount, 2, '.', '') . '%';
		}
		return self::format_currency($amount);
	}

	public static function format_currency($amount) {
		if (function_exists('wc_price')) {
			return wc_price($amount);
		}
		$locale = get_locale();
		if (strpos($locale, 'ro') === 0) {
			return number_format((float)$amount, 2, ',', '.') . ' Lei';
		}
		return number_format((float)$amount, 2, '.', '') . ' ' . (function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '');
	}

	private static function get_discount_type_label($type) {
		$labels = array(
			'percentage' => __('Percentage', 'gravity-forms-woocommerce-coupon-generator'),
			'fixed_cart' => __('Fixed cart discount', 'gravity-forms-woocommerce-coupon-generator'),
			'fixed_product' => __('Fixed product discount', 'gravity-forms-woocommerce-coupon-generator'),
		);
		return isset($labels[$type]) ? $labels[$type] : $type;
	}

	private static function format_product_list($product_ids) {
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

	private static function format_category_list($category_ids) {
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

	private static function format_tag_list($tag_ids) {
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
}
