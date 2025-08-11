<?php
/**
 * Admin List View
 *
 * @package Gravity Forms WooCommerce Coupon Generator
 */

if (!defined('ABSPATH')) {
	exit;
}

// Partial functions are available through autoloader

/**
 * Display the generators list view
 *
 * @param array $generators The generators to display
 */
function gfwcg_display_list_view($generators) {
	?>
	<div class="wrap">
		<?php
		// Display WordPress notifications above the header
		settings_errors();
		// Display the header
		gfwcg_display_admin_header(
			__('Coupon Generators', 'gravity-forms-woocommerce-coupon-generator'),
			'list',
			admin_url('admin.php?page=gfwcg-add-generator')
		);
		?>
		<hr class="wp-header-end">
		<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th scope="col"><?php _e('ID', 'gravity-forms-woocommerce-coupon-generator'); ?></th>
				<th scope="col"><?php _e('Title', 'gravity-forms-woocommerce-coupon-generator'); ?></th>
				<th scope="col"><?php _e('Form', 'gravity-forms-woocommerce-coupon-generator'); ?></th>
				<th scope="col"><?php _e('Type', 'gravity-forms-woocommerce-coupon-generator'); ?></th>
				<th scope="col"><?php _e('Discount', 'gravity-forms-woocommerce-coupon-generator'); ?></th>
				<th scope="col"><?php _e('Usage Limits', 'gravity-forms-woocommerce-coupon-generator'); ?></th>
				<th scope="col"><?php _e('Status', 'gravity-forms-woocommerce-coupon-generator'); ?></th>
				<th scope="col"><?php _e('Actions', 'gravity-forms-woocommerce-coupon-generator'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (empty($generators)) : ?>
				<tr>
					<td colspan="8"><?php _e('No generators found.', 'gravity-forms-woocommerce-coupon-generator'); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ($generators as $generator) : 
					$form = GFAPI::get_form($generator->form_id);
					$form_title = $form ? $form['title'] : __('Form not found', 'gravity-forms-woocommerce-coupon-generator');
					$discount_text = $generator->discount_type === 'percentage' ? 
						$generator->discount_amount . '%' : 
						wc_price($generator->discount_amount);
				?>
					<tr>
						<td><?php echo esc_html($generator->id); ?></td>
						<td>
							<strong>
								<a href="<?php echo esc_url(admin_url('admin.php?page=gfwcg-generators&view=edit&id=' . $generator->id)); ?>">
									<?php echo esc_html($generator->title); ?>
								</a>
							</strong>
						</td>
						<td><?php echo esc_html($form_title); ?></td>
						<td><?php echo esc_html(ucfirst($generator->coupon_type)); ?></td>
						<td><?php echo $discount_text; ?></td>
						<td>
							<?php 
								$limits = array();
								if ($generator->usage_limit_per_coupon > 0) {
									$limits[] = sprintf(__('Per coupon: %d', 'gravity-forms-woocommerce-coupon-generator'), $generator->usage_limit_per_coupon);
								} else {
									$limits[] = __('Per coupon: Unlimited', 'gravity-forms-woocommerce-coupon-generator');
								}
								if ($generator->usage_limit_per_user > 0) {
									$limits[] = sprintf(__('Per user: %d', 'gravity-forms-woocommerce-coupon-generator'), $generator->usage_limit_per_user);
								} else {
									$limits[] = __('Per user: Unlimited', 'gravity-forms-woocommerce-coupon-generator');
								}
								echo implode('<br>', $limits);
							?>
						</td>
						<td>
							<span class="status-<?php echo esc_attr($generator->status); ?>">
								<?php echo esc_html(ucfirst($generator->status)); ?>
							</span>
						</td>
						<td>
							<?php 
								gfwcg_display_edit_button($generator->id);
								gfwcg_display_delete_button($generator->id);
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
				</tbody>
	</table>
	</div>
	<?php
}
