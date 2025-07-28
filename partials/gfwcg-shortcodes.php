<?php
/**
 * Shortcodes Handler
 *
 * @package Gravity Forms WooCommerce Coupon Generator
 */

if (!defined('ABSPATH')) {
	exit;
}

// Prevent multiple includes
if (defined('GFWCG_SHORTCODES_LOADED')) {
	return;
}
define('GFWCG_SHORTCODES_LOADED', true);

// Include required classes
require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-gfwcg-db.php';

/**
 * Shortcode to display generator restrictions
 * Usage: [gfwcg_restrictions id="1"] or [gfwcg_restrictions slug="my-generator"]
 */
function gfwcg_restrictions_shortcode($atts) {
	
	$atts = shortcode_atts(array(
		'id' => 0,
		'slug' => '',
		'show_title' => 'true',
		'show_description' => 'true',
		'show_discount' => 'true',
		'show_usage' => 'true',
		'show_restrictions' => 'true',
		'show_expiry' => 'true',
		'css_class' => 'gfwcg-restrictions',
		'display_section_titles' => 'true',
		// Discount section controls
		'display_discount_labels' => 'true',
		'discount_type_value' => 'true',
		'discount_type_label' => 'true',
		'discount_amount_value' => 'true',
		'discount_amount_label' => 'true',
		'discount_free_shipping_value' => 'true',
		'discount_free_shipping_label' => 'true',
		// Usage section controls
		'display_usage_labels' => 'true',
		'usage_per_coupon_value' => 'true',
		'usage_per_coupon_label' => 'true',
		'usage_per_user_value' => 'true',
		'usage_per_user_label' => 'true',
		'usage_individual_value' => 'true',
		'usage_individual_label' => 'true',
		// Restrictions section controls
		'display_restrictions_labels' => 'true',
		'restrictions_minimum_value' => 'true',
		'restrictions_minimum_label' => 'true',
		'restrictions_maximum_value' => 'true',
		'restrictions_maximum_label' => 'true',
		'restrictions_exclude_sale_value' => 'true',
		'restrictions_exclude_sale_label' => 'true',
		'restrictions_products_value' => 'true',
		'restrictions_products_label' => 'true',
		'restrictions_exclude_products_value' => 'true',
		'restrictions_exclude_products_label' => 'true',
		'restrictions_categories_value' => 'true',
		'restrictions_categories_label' => 'true',
		'restrictions_exclude_categories_value' => 'true',
		'restrictions_exclude_categories_label' => 'true',
		// Expiry section controls
		'display_expiry_labels' => 'true',
		'expiry_days_value' => 'true',
		'expiry_days_label' => 'true'
	), $atts, 'gfwcg_restrictions');

	// Get generator by ID or slug
	$generator = null;
	if (!empty($atts['id'])) {
		$generator = GFWCG_DB::get_generator(intval($atts['id']));
	} elseif (!empty($atts['slug'])) {
		$generator = GFWCG_DB::get_generator_by_slug($atts['slug']);
	}

	if (!$generator || $generator->status !== 'active') {
		return '<p class="gfwcg-error">' . __('Generator not found or inactive.', 'gravity-forms-woocommerce-coupon-generator') . '</p>';
	}

	ob_start();
	?>
	<div class="<?php echo esc_attr($atts['css_class']); ?>">
		<?php if ($atts['show_title'] === 'true' && !empty($generator->title)): ?>
			<h3 class="gfwcg-title"><?php echo esc_html($generator->title); ?></h3>
		<?php endif; ?>

		<?php if ($atts['show_description'] === 'true' && !empty($generator->description)): ?>
			<div class="gfwcg-description"><?php echo wp_kses_post($generator->description); ?></div>
		<?php endif; ?>

		<div class="gfwcg-restrictions-content">
			<?php if ($atts['show_discount'] === 'true'): ?>
				<div class="gfwcg-discount-info">
					<?php if ($atts['display_section_titles'] === 'true'): ?>
						<h4><?php echo gfwcg_get_text('Discount Details'); ?></h4>
					<?php endif; ?>
					<ul>
						<?php if ($atts['discount_type_value'] === 'true'): ?>
							<li>
								<?php if ($atts['display_discount_labels'] === 'true' && $atts['discount_type_label'] === 'true'): ?>
									<strong><?php echo gfwcg_get_text('Type:'); ?></strong> 
								<?php endif; ?>
								<?php echo esc_html(gfwcg_get_text(ucfirst($generator->discount_type))); ?>
							</li>
						<?php endif; ?>
						<?php if ($atts['discount_amount_value'] === 'true'): ?>
							<li>
								<?php if ($atts['display_discount_labels'] === 'true' && $atts['discount_amount_label'] === 'true'): ?>
									<strong><?php echo gfwcg_get_text('Amount:'); ?></strong> 
								<?php endif; ?>
								<?php echo esc_html($generator->discount_amount); ?>
								<?php echo $generator->discount_type === 'percentage' ? '%' : get_woocommerce_currency_symbol(); ?>
							</li>
						<?php endif; ?>
						<?php if ($atts['discount_free_shipping_value'] === 'true' && $generator->allow_free_shipping): ?>
							<li>
								<?php if ($atts['display_discount_labels'] === 'true' && $atts['discount_free_shipping_label'] === 'true'): ?>
									<strong><?php echo gfwcg_get_text('Free Shipping:'); ?></strong> 
								<?php endif; ?>
								<?php echo gfwcg_get_text('Yes'); ?>
							</li>
						<?php endif; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ($atts['show_usage'] === 'true'): ?>
				<div class="gfwcg-usage-info">
					<?php if ($atts['display_section_titles'] === 'true'): ?>
						<h4><?php echo gfwcg_get_text('Usage Limits'); ?></h4>
					<?php endif; ?>
					<ul>
						<?php if ($atts['usage_per_coupon_value'] === 'true' && $generator->usage_limit_per_coupon > 0): ?>
							<li>
								<?php if ($atts['display_usage_labels'] === 'true' && $atts['usage_per_coupon_label'] === 'true'): ?>
									        <strong><?php echo gfwcg_get_text('Utilizări per cod de reducere:'); ?></strong> 
								<?php endif; ?>
								<?php echo esc_html($generator->usage_limit_per_coupon); ?>
							</li>
						<?php endif; ?>
						<?php if ($atts['usage_per_user_value'] === 'true' && $generator->usage_limit_per_user > 0): ?>
							<li>
								<?php if ($atts['display_usage_labels'] === 'true' && $atts['usage_per_user_label'] === 'true'): ?>
									<strong><?php echo gfwcg_get_text('Usage per user:'); ?></strong> 
								<?php endif; ?>
								<?php echo esc_html($generator->usage_limit_per_user); ?>
							</li>
						<?php endif; ?>
						<?php if ($atts['usage_individual_value'] === 'true' && $generator->individual_use): ?>
							<li>
								<?php if ($atts['display_usage_labels'] === 'true' && $atts['usage_individual_label'] === 'true'): ?>
									<strong><?php echo gfwcg_get_text('Individual use only:'); ?></strong> 
								<?php endif; ?>
								<?php echo gfwcg_get_text('Yes'); ?>
							</li>
						<?php endif; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ($atts['show_restrictions'] === 'true'): ?>
				<div class="gfwcg-restrictions-info">
					<?php if ($atts['display_section_titles'] === 'true'): ?>
						<h4><?php echo gfwcg_get_text('Restrictions'); ?></h4>
					<?php endif; ?>
					<ul>
						<?php if ($atts['restrictions_minimum_value'] === 'true' && $generator->minimum_amount > 0): ?>
							<li>
								<?php if ($atts['display_restrictions_labels'] === 'true' && $atts['restrictions_minimum_label'] === 'true'): ?>
									<strong><?php echo gfwcg_get_text('Minimum spend:'); ?></strong> 
								<?php endif; ?>
								<?php echo wc_price($generator->minimum_amount); ?>
							</li>
						<?php endif; ?>
						<?php if ($atts['restrictions_maximum_value'] === 'true' && $generator->maximum_amount > 0): ?>
							<li>
								<?php if ($atts['display_restrictions_labels'] === 'true' && $atts['restrictions_maximum_label'] === 'true'): ?>
									<strong><?php echo gfwcg_get_text('Maximum spend:'); ?></strong> 
								<?php endif; ?>
								<?php echo wc_price($generator->maximum_amount); ?>
							</li>
						<?php endif; ?>
						<?php if ($atts['restrictions_exclude_sale_value'] === 'true' && $generator->exclude_sale_items): ?>
							<li>
								<?php if ($atts['display_restrictions_labels'] === 'true' && $atts['restrictions_exclude_sale_label'] === 'true'): ?>
									<strong><?php echo gfwcg_get_text('Exclude sale items:'); ?></strong> 
								<?php endif; ?>
								<?php echo gfwcg_get_text('Yes'); ?>
							</li>
						<?php endif; ?>
					</ul>

					<?php
					// Product restrictions
					if ($atts['restrictions_products_value'] === 'true'):
						$product_ids = maybe_unserialize($generator->product_ids);
						if (!empty($product_ids) && is_array($product_ids)):
							$products = array();
							foreach ($product_ids as $product_id) {
								$product = wc_get_product($product_id);
								if ($product) {
									$products[] = array(
										'name' => $product->get_name(),
										'url' => get_permalink($product->get_id()),
										'id' => $product->get_id()
									);
								}
							}
							if (!empty($products)):
					?>
							<div class="gfwcg-product-restrictions">
								<?php if ($atts['display_restrictions_labels'] === 'true' && $atts['restrictions_products_label'] === 'true'): ?>
									<h5><?php echo gfwcg_get_text('Valid for products:'); ?></h5>
								<?php endif; ?>
								<ul>
									<?php foreach ($products as $product): ?>
										<li><a href="<?php echo esc_url($product['url']); ?>" class="gfwcg-product-link"><?php echo esc_html($product['name']); ?></a></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php 
							endif;
						endif;
					endif;

					// Exclude products
					if ($atts['restrictions_exclude_products_value'] === 'true'):
						$exclude_products = maybe_unserialize($generator->exclude_products);
						if (!empty($exclude_products) && is_array($exclude_products)):
							$excluded_products = array();
							foreach ($exclude_products as $product_id) {
								$product = wc_get_product($product_id);
								if ($product) {
									$excluded_products[] = array(
										'name' => $product->get_name(),
										'url' => get_permalink($product->get_id()),
										'id' => $product->get_id()
									);
								}
							}
							if (!empty($excluded_products)):
					?>
							<div class="gfwcg-exclude-products">
								<?php if ($atts['display_restrictions_labels'] === 'true' && $atts['restrictions_exclude_products_label'] === 'true'): ?>
									<h5><?php echo gfwcg_get_text('Excluded products:'); ?></h5>
								<?php endif; ?>
								<ul>
									<?php foreach ($excluded_products as $product): ?>
										<li><a href="<?php echo esc_url($product['url']); ?>" class="gfwcg-product-link"><?php echo esc_html($product['name']); ?></a></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php 
							endif;
						endif;
					endif;

					// Category restrictions
					if ($atts['restrictions_categories_value'] === 'true'):
						$product_categories = maybe_unserialize($generator->product_categories);
						if (!empty($product_categories) && is_array($product_categories)):
							$categories = array();
							foreach ($product_categories as $cat_id) {
								$cat = get_term($cat_id, 'product_cat');
								if ($cat && !is_wp_error($cat)) {
									$categories[] = array(
										'name' => $cat->name,
										'url' => get_term_link($cat),
										'id' => $cat->term_id
									);
								}
							}
							if (!empty($categories)):
					?>
							<div class="gfwcg-category-restrictions">
								<?php if ($atts['display_restrictions_labels'] === 'true' && $atts['restrictions_categories_label'] === 'true'): ?>
									<h5><?php echo gfwcg_get_text('Valid for categories:'); ?></h5>
								<?php endif; ?>
								<ul>
									<?php foreach ($categories as $category): ?>
										<li><a href="<?php echo esc_url($category['url']); ?>" class="gfwcg-category-link"><?php echo esc_html($category['name']); ?></a></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php 
							endif;
						endif;
					endif;

					// Exclude categories
					if ($atts['restrictions_exclude_categories_value'] === 'true'):
						$exclude_categories = maybe_unserialize($generator->exclude_categories);
						if (!empty($exclude_categories) && is_array($exclude_categories)):
							$excluded_categories = array();
							foreach ($exclude_categories as $cat_id) {
								$cat = get_term($cat_id, 'product_cat');
								if ($cat && !is_wp_error($cat)) {
									$excluded_categories[] = array(
										'name' => $cat->name,
										'url' => get_term_link($cat),
										'id' => $cat->term_id
									);
								}
							}
							if (!empty($excluded_categories)):
					?>
							<div class="gfwcg-exclude-categories">
								<?php if ($atts['display_restrictions_labels'] === 'true' && $atts['restrictions_exclude_categories_label'] === 'true'): ?>
									<h5><?php echo gfwcg_get_text('Excluded categories:'); ?></h5>
								<?php endif; ?>
								<ul>
									<?php foreach ($excluded_categories as $category): ?>
										<li><a href="<?php echo esc_url($category['url']); ?>" class="gfwcg-category-link"><?php echo esc_html($category['name']); ?></a></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php 
							endif;
						endif;
					endif;
					?>
				</div>
			<?php endif; ?>

			<?php if ($atts['show_expiry'] === 'true' && $atts['expiry_days_value'] === 'true' && $generator->expiry_days > 0): ?>
				<div class="gfwcg-expiry-info">
					<?php if ($atts['display_section_titles'] === 'true'): ?>
						<h4><?php echo gfwcg_get_text('Expiry'); ?></h4>
					<?php endif; ?>
					        <p><?php printf(gfwcg_get_text('Codul de reducere expiră după %d zile de la generare.'), $generator->expiry_days); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode('gfwcg_restrictions', 'gfwcg_restrictions_shortcode');

/**
 * Shortcode to display generator form with restrictions
 * Usage: [gfwcg_form id="1" show_restrictions="true"]
 */
function gfwcg_form_shortcode($atts) {
	
	$atts = shortcode_atts(array(
		'id' => 0,
		'slug' => '',
		'show_restrictions' => 'true',
		'css_class' => 'gfwcg-form'
	), $atts, 'gfwcg_form');

	// Get generator by ID or slug
	$generator = null;
	if (!empty($atts['id'])) {
		$generator = GFWCG_DB::get_generator(intval($atts['id']));
	} elseif (!empty($atts['slug'])) {
		$generator = GFWCG_DB::get_generator_by_slug($atts['slug']);
	}

	if (!$generator || $generator->status !== 'active') {
		return '<p class="gfwcg-error">' . __('Generator not found or inactive.', 'gravity-forms-woocommerce-coupon-generator') . '</p>';
	}

	ob_start();
	?>
	<div class="<?php echo esc_attr($atts['css_class']); ?>">
		<?php if ($atts['show_restrictions'] === 'true'): ?>
			<?php echo do_shortcode('[gfwcg_restrictions id="' . $generator->id . '" show_title="false"]'); ?>
		<?php endif; ?>
		
		<?php echo do_shortcode('[gravityform id="' . $generator->form_id . '" gen="' . $generator->id . '"]'); ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode('gfwcg_form', 'gfwcg_form_shortcode');

// Hook into Gravity Forms shortcode processing for generator ID
add_filter('gform_shortcode_form', function($shortcode_string, $attributes) {
	if (!isset($attributes['gen'])) {
		return $shortcode_string;
	}

	// Get the generator by ID
	$generator = GFWCG_DB::get_generator($attributes['gen']);
	if (!$generator) {
		return $shortcode_string;
	}

	// Add generator data as hidden fields
	add_filter('gform_pre_render_' . $attributes['id'], function($form) use ($generator) {
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

	return $shortcode_string;
}, 10, 2); 