<?php

class GFWCG_Generator {
	private $coupon;
	private $email;

	public function __construct() {
		$this->coupon = new GFWCG_Coupon();
		$this->email = new GFWCG_Email();
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

		// Get email address from the specified field
		$to = rgar($entry, $generator->email_field_id);
		if (!$to) {
			error_log('GFWCG: No email address found for recipient in field ' . $generator->email_field_id);
			return;
		}

		// Generate and create coupon
		$coupon_code = $this->coupon->generate_coupon_code($generator, $entry);
		error_log('GFWCG: Generated coupon code: ' . $coupon_code);

		$coupon_id = $this->coupon->create_woocommerce_coupon($coupon_code, $generator);
		error_log('GFWCG: Created WooCommerce coupon with ID: ' . $coupon_id);

		// Send email
		$email_result = $this->email->send(
			$to,
			$generator->email_subject,
			$generator->email_message,
			$generator->email_from_name,
			$generator->email_from_email,
			array(
				'coupon_code' => $coupon_code,
				'discount_amount' => number_format($generator->discount_amount, 2, '.', '') . ($generator->discount_type === 'percentage' ? '%' : ''),
				'expiry_date' => $generator->expiry_days ? date_i18n(get_option('date_format'), strtotime('+' . $generator->expiry_days . ' days')) : __('No expiry', 'gravity-forms-woocommerce-coupon-generator')
			)
		);
		
		error_log('GFWCG: Email sending process completed with result: ' . ($email_result ? 'success' : 'failed'));
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
} 