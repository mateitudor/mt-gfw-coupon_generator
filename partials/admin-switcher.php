<?php
/**
 * Admin Switcher Template
 *
 * @package Gravity Forms WooCommerce Coupon Generator
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Display the view switcher
 *
 * @param string $current_view The current view
 */
function gfwcg_display_view_switcher($current_view = 'list') {
	$views = array(
		'list' => array(
			'label' => __('List View', 'gravity-forms-woocommerce-coupon-generator'),
			'icon' => 'dashicons-list-view'
		),
		'grid' => array(
			'label' => __('Grid View', 'gravity-forms-woocommerce-coupon-generator'),
			'icon' => 'dashicons-grid-view'
		)
	);
	?>
	<div class="gfwcg-view-switcher">
		<?php foreach ($views as $view => $data) : ?>
			<a href="<?php echo esc_url(add_query_arg('view', $view)); ?>" 
			   class="gfwcg-view-switcher-link <?php echo $current_view === $view ? 'current' : ''; ?>"
			   title="<?php echo esc_attr($data['label']); ?>">
				<span class="dashicons <?php echo esc_attr($data['icon']); ?>"></span>
				<span class="screen-reader-text"><?php echo esc_html($data['label']); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
}