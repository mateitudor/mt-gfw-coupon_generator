<?php

class GFWCG_Coupon {
		public function generate_coupon_code($generator, $entry = null) {
				if ($generator->coupon_type === 'field' && $generator->coupon_field_id && $entry) {
						// Get the form and field to understand its structure
						$form = GFAPI::get_form($entry['form_id']);
						$field = GFAPI::get_field($form, $generator->coupon_field_id);

						// Get the field value from the entry
						$field_value = '';
						if ($field && isset($field->inputs)) {
								// For fields with inputs (like name fields), try each input
								foreach ($field->inputs as $input) {
										$input_id = $input['id'];
										$value = rgar($entry, (string)$input_id);
										if (!empty($value)) {
												$field_value = $value;
												break;
										}
								}
						} else {
								// For simple fields, just get the value directly
								$field_value = rgar($entry, (string)$generator->coupon_field_id);
						}

						gfwcg_debug_log('Processing field-based coupon generation');
						gfwcg_debug_log('Target field ID: ' . (string)$generator->coupon_field_id);
						gfwcg_debug_log('Field type: ' . ($field ? $field->type : 'unknown'));
						gfwcg_debug_log('Form entry data: ' . print_r($entry, true));
						gfwcg_debug_log('Extracted field value: ' . $field_value);

						if ($field_value) {
								gfwcg_debug_log('Using field value for coupon code generation: ' . $field_value);
								// Remove spaces from the field value
								$field_value = str_replace(' ', '', $field_value);
								// Apply prefix and suffix to the field value
								$prefix = $generator->coupon_prefix ?: '';
								$suffix = $generator->coupon_suffix ?: '';
								$separator = $generator->coupon_separator ?: '';
								return $prefix . $separator . $field_value . $separator . $suffix;
						}
						gfwcg_debug_log('No valid field value found, falling back to random generation');
				} elseif ($generator->coupon_type === 'email' && $entry) {
						// Get the email address from the specified email field
						$email_value = rgar($entry, (string)$generator->email_field_id);

						gfwcg_debug_log('Processing email-based coupon generation');
						gfwcg_debug_log('Email field ID: ' . (string)$generator->email_field_id);
						gfwcg_debug_log('Email value: ' . $email_value);

						if ($email_value) {
								gfwcg_debug_log('Using email value for coupon code generation: ' . $email_value);
								// Remove spaces from the email value
								$email_value = str_replace(' ', '', $email_value);

								// Split email into parts: username@domain.extension
								$email_parts = explode('@', $email_value);
								if (count($email_parts) === 2) {
										$username = $email_parts[0];
										$domain_with_extension = $email_parts[1];

										// Remove domain extension (everything after the first dot)
										$domain_parts = explode('.', $domain_with_extension);
										$domain = $domain_parts[0]; // Get just the domain name without extension

										// Apply prefix and suffix with separator
										$prefix = $generator->coupon_prefix ?: '';
										$suffix = $generator->coupon_suffix ?: '';
										$separator = $generator->coupon_separator ?: '-';

										// Format: prefix-username-domain-suffix
										$coupon_code = $prefix;
										if ($prefix) $coupon_code .= $separator;
										$coupon_code .= $username . $separator . $domain;
										if ($suffix) $coupon_code .= $separator . $suffix;

										gfwcg_debug_log('Generated email-based coupon code: ' . $coupon_code);
										return $coupon_code;
								}
						}
						gfwcg_debug_log('No valid email value found or invalid email format, falling back to random generation');
				} else {
						gfwcg_debug_log('Using random coupon code generation');
				}

				$prefix = $generator->coupon_prefix ?: '';
				$suffix = $generator->coupon_suffix ?: '';
				$separator = $generator->coupon_separator ?: '';
				$length = $generator->coupon_length ?: '';

				$random = strtolower(wp_generate_password($length, false));
				$code = $prefix . $separator . $random . $separator . $suffix;

				gfwcg_debug_log('Random coupon code generated successfully: ' . $code);
				return $code;
		}

		public function create_woocommerce_coupon($code, $generator) {
				$coupon = new WC_Coupon();
				$coupon->set_code(strtolower($code));

				$discount_type = $generator->discount_type;
				if ($discount_type === 'percentage') {
						$discount_type = 'percent';
				} elseif ($discount_type === 'fixed_cart') {
						$discount_type = 'fixed_cart';
				} else {
						$discount_type = 'fixed_cart';
				}

				$coupon->set_discount_type($discount_type);
				$coupon->set_amount($generator->discount_amount);
				$coupon->set_individual_use($generator->individual_use);
				$coupon->set_usage_limit($generator->usage_limit_per_coupon);
				$coupon->set_usage_limit_per_user($generator->usage_limit_per_user);
				$coupon->set_minimum_amount($generator->minimum_amount);
				$coupon->set_maximum_amount($generator->maximum_amount);
				$coupon->set_exclude_sale_items($generator->exclude_sale_items);

				// Set coupon description if provided
				if (!empty($generator->description)) {
						$coupon->set_description($generator->description);
				}

				if ($generator->expiry_days) {
						$expiry_date = date('Y-m-d', strtotime('+' . $generator->expiry_days . ' days'));
						$coupon->set_date_expires($expiry_date);
				}

				if ($generator->allow_free_shipping) {
						$coupon->set_free_shipping(true);
				}

				// Set product restrictions
				if (!empty($generator->product_ids)) {
					$product_ids = maybe_unserialize($generator->product_ids);
					if (is_array($product_ids) && !empty($product_ids)) {
						$coupon->set_product_ids($product_ids);
					}
				}

				// Set exclude product restrictions
				if (!empty($generator->exclude_products)) {
					$exclude_product_ids = maybe_unserialize($generator->exclude_products);
					if (is_array($exclude_product_ids) && !empty($exclude_product_ids)) {
						$coupon->set_excluded_product_ids($exclude_product_ids);
					}
				}

				// Set product category restrictions
				if (!empty($generator->product_categories)) {
					$product_categories = maybe_unserialize($generator->product_categories);
					if (is_array($product_categories) && !empty($product_categories)) {
						$coupon->set_product_categories($product_categories);
					}
				}

				// Set exclude product category restrictions
				if (!empty($generator->exclude_categories)) {
					$exclude_categories = maybe_unserialize($generator->exclude_categories);
					if (is_array($exclude_categories) && !empty($exclude_categories)) {
						$coupon->set_excluded_product_categories($exclude_categories);
					}
				}

				return $coupon->save();
		}
}
