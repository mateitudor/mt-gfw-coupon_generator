<?php

class GFWCG_Generator {
	private $coupon;
	private static $instance = null;

	public static function get_instance() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->coupon = new GFWCG_Coupon();
		add_action('gform_after_submission', array($this, 'process_form_submission'), 10, 2);
		
		// PHP-based validation overrides
		add_filter('gform_field_validation_message', array($this, 'override_validation_message'), 1, 4);
		add_filter('gform_validation_message', array($this, 'override_validation_error_header'), 1, 2);
		add_filter('gform_field_validation', array($this, 'override_field_validation'), 1, 4);
		add_filter('gform_validation', array($this, 'override_form_validation'), 1, 2);
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

	/**
	 * Override validation messages - simple and direct
	 */
	public function override_validation_message($message, $field, $value, $form) {
		// Validate parameters
		if (!is_array($form) || !isset($form['id'])) {
			return $message;
		}

		// Get generator for this form
		$generator = $this->get_generator_by_form_id($form['id']);
		if (!$generator) {
			return $message;
		}

		// Only handle the email field
		if ($field->id != $generator->email_field_id) {
			return $message;
		}

		// Only replace actual validation messages, not field content
		if (empty($message) || strlen($message) > 200) {
			return $message;
		}

		// Replace messages based on content
		if (strpos($message, 'required') !== false && !empty($generator->validation_required_message)) {
			return $generator->validation_required_message;
		}
		
		if ((strpos($message, 'valid email') !== false || strpos($message, 'email address') !== false) && !empty($generator->validation_email_message)) {
			return $generator->validation_email_message;
		}
		
		if ((strpos($message, 'unique entry') !== false || strpos($message, 'already been used') !== false || strpos($message, 'already used') !== false) && !empty($generator->validation_duplicate_message)) {
			return $generator->validation_duplicate_message;
		}

		return $message;
	}

	/**
	 * Override field validation result
	 */
	public function override_field_validation($result, $value, $form, $field) {
		// Validate parameters
		if (!is_array($form) || !isset($form['id'])) {
			return $result;
		}

		// Get generator for this form
		$generator = $this->get_generator_by_form_id($form['id']);
		if (!$generator) {
			return $result;
		}

		// Only handle the email field
		if ($field->id != $generator->email_field_id) {
			return $result;
		}

		// If validation failed, replace the message
		if (!$result['is_valid']) {
			$message = $result['message'];
			
			if (strpos($message, 'required') !== false && !empty($generator->validation_required_message)) {
				$result['message'] = $generator->validation_required_message;
			} elseif ((strpos($message, 'valid email') !== false || strpos($message, 'email address') !== false) && !empty($generator->validation_email_message)) {
				$result['message'] = $generator->validation_email_message;
			} elseif ((strpos($message, 'unique entry') !== false || strpos($message, 'already been used') !== false || strpos($message, 'already used') !== false) && !empty($generator->validation_duplicate_message)) {
				$result['message'] = $generator->validation_duplicate_message;
			}
		}

		return $result;
	}

	/**
	 * Override form validation to handle validation summary
	 */
	public function override_form_validation($validation_result, $form) {
		// Validate parameters - $form might be a string in some cases
		if (!is_array($form) || !isset($form['id'])) {
			gfwcg_debug_log('Invalid form parameter in override_form_validation: ' . print_r($form, true));
			return $validation_result;
		}

		// Get generator for this form
		$generator = $this->get_generator_by_form_id($form['id']);
		if (!$generator) {
			return $validation_result;
		}

		// If validation failed, update the messages in the validation summary
		if (!$validation_result['is_valid'] && isset($validation_result['form']['fields'])) {
			foreach ($validation_result['form']['fields'] as &$field) {
				if (isset($field['id']) && $field['id'] == $generator->email_field_id) {
					// Update field validation message
					if (!empty($field['validation_message'])) {
						$message = $field['validation_message'];
						
						if (strpos($message, 'required') !== false && !empty($generator->validation_required_message)) {
							$field['validation_message'] = $generator->validation_required_message;
						} elseif ((strpos($message, 'valid email') !== false || strpos($message, 'email address') !== false) && !empty($generator->validation_email_message)) {
							$field['validation_message'] = $generator->validation_email_message;
						} elseif ((strpos($message, 'unique entry') !== false || strpos($message, 'already been used') !== false || strpos($message, 'already used') !== false) && !empty($generator->validation_duplicate_message)) {
							$field['validation_message'] = $generator->validation_duplicate_message;
						}
					}
				}
			}
		}

		return $validation_result;
	}

	/**
	 * Override validation error header
	 */
	public function override_validation_error_header($header, $form) {
		// Validate parameters
		if (!is_array($form) || !isset($form['id'])) {
			return $header;
		}

		// Get generator for this form
		$generator = $this->get_generator_by_form_id($form['id']);
		if (!$generator || empty($generator->validation_error_header)) {
			return $header;
		}

		return $generator->validation_error_header;
	}

	private function get_generator_by_form_id($form_id) {
		// Validate form_id parameter
		if (!is_numeric($form_id) || $form_id <= 0) {
			gfwcg_debug_log('Invalid form ID: ' . print_r($form_id, true));
			return null;
		}

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