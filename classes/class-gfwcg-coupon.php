<?php

class GFWCG_Coupon {
		public function generate_coupon_code($generator) {
				if ($generator->coupon_type === 'field' && $generator->coupon_field_id) {
						return rgar($entry, $generator->coupon_field_id);
				}

				$prefix = $generator->coupon_prefix ?: '';
				$suffix = $generator->coupon_suffix ?: '';
				$separator = $generator->coupon_separator ?: '';
				$length = $generator->coupon_length ?: 8;

				$random = wp_generate_password($length, false);
				return $prefix . $separator . $random . $separator . $suffix;
		}

		public function create_woocommerce_coupon($code, $generator) {
				$coupon = new WC_Coupon();
				$coupon->set_code($code);
				
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