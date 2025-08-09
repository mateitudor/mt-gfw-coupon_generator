<?php
/**
 * Admin Settings Page
 * 
 * @package Gravity Forms WooCommerce Coupon Generator
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}
?>

<?php settings_errors(); ?>
<div class="wrap">
	<h1><?php _e('Coupon Generator Settings', 'gravity-forms-woocommerce-coupon-generator'); ?></h1>
	
	<form method="post" action="options.php">
		<?php settings_fields('gfwcg_settings'); ?>
		<?php do_settings_sections('gfwcg_settings'); ?>
		
		<div class="gfwcg-settings-section">
			<h2><?php _e('Debug & Troubleshooting', 'gravity-forms-woocommerce-coupon-generator'); ?></h2>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="gfwcg_debug_mode"><?php _e('Debug Mode', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
					</th>
					<td>
						<input type="checkbox" name="gfwcg_debug_mode" id="gfwcg_debug_mode" value="1" 
							   <?php checked(get_option('gfwcg_debug_mode', 0), 1); ?>>
						<label for="gfwcg_debug_mode"><?php _e('Enable debug logging', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
						<p class="description">
							<?php _e('When enabled, detailed information about coupon generation, form processing, and email sending will be logged to help troubleshoot issues. Logs are written to the WordPress debug log when WP_DEBUG_LOG is enabled.', 'gravity-forms-woocommerce-coupon-generator'); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<div class="gfwcg-settings-section">
			<h2><?php _e('Uninstall Settings', 'gravity-forms-woocommerce-coupon-generator'); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="gfwcg_delete_options_on_uninstall"><?php _e('Delete Options on Uninstall', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
					</th>
					<td>
						<input type="checkbox" name="gfwcg_delete_options_on_uninstall" id="gfwcg_delete_options_on_uninstall" value="1" 
							   <?php checked(get_option('gfwcg_delete_options_on_uninstall', 0), 1); ?>>
						<label for="gfwcg_delete_options_on_uninstall"><?php _e('Remove plugin options (e.g., debug setting) when the plugin is uninstalled.', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="gfwcg_drop_tables_on_uninstall"><?php _e('Drop Database Tables on Uninstall', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
					</th>
					<td>
						<input type="checkbox" name="gfwcg_drop_tables_on_uninstall" id="gfwcg_drop_tables_on_uninstall" value="1" 
							   <?php checked(get_option('gfwcg_drop_tables_on_uninstall', 0), 1); ?>>
						<label for="gfwcg_drop_tables_on_uninstall"><?php _e('Permanently delete the plugin\'s database tables on uninstall (cannot be undone).', 'gravity-forms-woocommerce-coupon-generator'); ?></label>
						<p class="description"><?php _e('Warning: This will remove all generator configurations stored by this plugin.', 'gravity-forms-woocommerce-coupon-generator'); ?></p>
					</td>
				</tr>
			</table>
		</div>
		
		<?php submit_button(); ?>
	</form>
	
	<div class="gfwcg-settings-info">
		<h3><?php _e('Debug Information', 'gravity-forms-woocommerce-coupon-generator'); ?></h3>
		<p>
			<?php _e('Debug mode logs the following information:', 'gravity-forms-woocommerce-coupon-generator'); ?>
		</p>
		<ul>
			<li><?php _e('Form submission processing steps', 'gravity-forms-woocommerce-coupon-generator'); ?></li>
			<li><?php _e('Generator lookup and matching', 'gravity-forms-woocommerce-coupon-generator'); ?></li>
			<li><?php _e('Coupon code generation details', 'gravity-forms-woocommerce-coupon-generator'); ?></li>
			<li><?php _e('WooCommerce coupon creation process', 'gravity-forms-woocommerce-coupon-generator'); ?></li>
			<li><?php _e('Email sending status and errors', 'gravity-forms-woocommerce-coupon-generator'); ?></li>
		</ul>
		
		<p>
			<strong><?php _e('To view debug logs:', 'gravity-forms-woocommerce-coupon-generator'); ?></strong><br>
			<?php _e('Add these lines to your wp-config.php file:', 'gravity-forms-woocommerce-coupon-generator'); ?>
		</p>
		<code>
			define('WP_DEBUG', true);<br>
			define('WP_DEBUG_LOG', true);
		</code>
		<p>
			<?php _e('Logs will be written to:', 'gravity-forms-woocommerce-coupon-generator'); ?> <code>/wp-content/debug.log</code>
		</p>
	</div>
</div> 