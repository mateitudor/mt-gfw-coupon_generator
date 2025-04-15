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
						
						error_log('GFWCG: Field ID: ' . (string)$generator->coupon_field_id);
						error_log('GFWCG: Field type: ' . ($field ? $field->type : 'unknown'));
						error_log('GFWCG: Entry data: ' . print_r($entry, true));
						error_log('GFWCG: Field value: ' . $field_value);
						
						if ($field_value) {
								error_log('GFWCG: Using field value for coupon code: ' . $field_value);
								// Remove spaces from the field value
								$field_value = str_replace(' ', '', $field_value);
								// Apply prefix and suffix to the field value
								$prefix = $generator->coupon_prefix ?: '';
								$suffix = $generator->coupon_suffix ?: '';
								$separator = $generator->coupon_separator ?: '';
								return $prefix . $separator . $field_value . $separator . $suffix;
						}
						error_log('GFWCG: No field value found for coupon code, falling back to random generation');
				} else {
						error_log('GFWCG: Using random generation for coupon code');
				}

				$prefix = $generator->coupon_prefix ?: '';
				$suffix = $generator->coupon_suffix ?: '';
				$separator = $generator->coupon_separator ?: '';
				$length = $generator->coupon_length ?: '';

				$random = strtolower(wp_generate_password($length, false));
				$code = $prefix . $separator . $random . $separator . $suffix;
				
				error_log('GFWCG: Generated random coupon code: ' . $code);
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
				
				if ($generator->expiry_days) {
						$expiry_date = date('Y-m-d', strtotime('+' . $generator->expiry_days . ' days'));
						$coupon->set_date_expires($expiry_date);
				}
				
				if ($generator->allow_free_shipping) {
						$coupon->set_free_shipping(true);
				}
				
				return $coupon->save();
		}
} 