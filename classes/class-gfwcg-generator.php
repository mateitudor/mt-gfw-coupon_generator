<?php

class GFWCG_Generator {
	private $coupon;

	public function __construct() {
		$this->coupon = new GFWCG_Coupon();
		add_action('gform_after_submission', array($this, 'process_form_submission'), 10, 2);
	}

	public function process_form_submission($entry, $form) {
		gfwcg_debug_log('Starting form submission processing');
		gfwcg_debug_log('Processing form ID: ' . $form['id']);
		gfwcg_debug_log('Form entry data: ' . print_r($entry, true));

		// Get the generator for this form
		$generator = $this->get_generator_by_form_id($form['id']);
		if (!$generator) {
					gfwcg_debug_log('No active generator found for form ID: ' . $form['id']);
		return;
	}

	gfwcg_debug_log('Generator found successfully: ' . print_r($generator, true));

		// Get email address from the specified field
		$to = rgar($entry, $generator->email_field_id);
		if (!$to) {
			gfwcg_debug_log('No valid email address found in field ID: ' . $generator->email_field_id);
			return;
		}

		// Generate and create coupon
		$coupon_code = $this->coupon->generate_coupon_code($generator, $entry);
		gfwcg_debug_log('Coupon code generated successfully: ' . $coupon_code);

		$coupon_id = $this->coupon->create_woocommerce_coupon($coupon_code, $generator);
		gfwcg_debug_log('WooCommerce coupon created with ID: ' . $coupon_id);

		// Send email using WooCommerce's mailer system
		if ($generator->send_email) {
			$emails = WC()->mailer()->get_emails();
			if (isset($emails['GFWCG_Email'])) {
				$emails['GFWCG_Email']->send_coupon_email($generator, $to, $coupon_code);
				gfwcg_debug_log('Email sent successfully to: ' . $to);
			} else {
				gfwcg_debug_log('Email class not found in WooCommerce mailer - email not sent');
			}
		}
	}

	private function get_generator_by_form_id($form_id) {
		global $wpdb;
		gfwcg_debug_log('Looking up generator for form ID: ' . $form_id);
		
		// Get the generator ID from the form submission
		$generator_id = isset($_POST['gfwcg_generator_id']) ? intval($_POST['gfwcg_generator_id']) : 0;
		gfwcg_debug_log('Generator ID from form submission: ' . $generator_id);
		
		if ($generator_id > 0) {
			// If we have a specific generator ID, use that
			$generator = $wpdb->get_row($wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}gfwcg_generators 
				WHERE id = %d AND form_id = %d AND status = 'active'",
				$generator_id,
				$form_id
			));
			
			if ($generator) {
				gfwcg_debug_log('Found specific generator by ID: ' . $generator_id);
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
			gfwcg_debug_log('No active generator found for form ID: ' . $form_id);
			return null;
		}
		
		gfwcg_debug_log('Using fallback generator ID: ' . $generator->id);
		gfwcg_debug_log('Generator configuration: ' . print_r($generator, true));
		
		return $generator;
	}
} 