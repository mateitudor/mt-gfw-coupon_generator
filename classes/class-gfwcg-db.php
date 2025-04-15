<?php

class GFWCG_DB {
    private static $table_name = 'gfwcg_generators';

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}gfwcg_generators (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            form_id int(11) NOT NULL,
            email_field_id int(11) NOT NULL,
            name_field_id int(11) DEFAULT NULL,
            coupon_type varchar(50) DEFAULT 'random',
            coupon_field_id int(11) DEFAULT NULL,
            coupon_prefix varchar(50) DEFAULT NULL,
            coupon_suffix varchar(50) DEFAULT NULL,
            coupon_separator varchar(10) DEFAULT NULL,
            coupon_length int(11) DEFAULT 8,
            discount_type varchar(50) DEFAULT 'percentage',
            discount_amount decimal(10,2) DEFAULT 0.00,
            individual_use tinyint(1) DEFAULT 0,
            usage_limit_per_coupon int(11) DEFAULT 1,
            usage_limit_per_user int(11) DEFAULT 1,
            allow_free_shipping tinyint(1) DEFAULT 0,
            exclude_sale_items tinyint(1) DEFAULT 0,
            expiry_date datetime DEFAULT NULL,
            expiry_days int(11) DEFAULT 0,
            minimum_spend decimal(10,2) DEFAULT 0.00,
            maximum_spend decimal(10,2) DEFAULT 0.00,
            products text DEFAULT NULL,
            exclude_products text DEFAULT NULL,
            product_categories text DEFAULT NULL,
            exclude_categories text DEFAULT NULL,
            product_brands text DEFAULT NULL,
            exclude_brands text DEFAULT NULL,
            allowed_emails text DEFAULT NULL,
            email_template text DEFAULT NULL,
            use_wc_email_template tinyint(1) DEFAULT 1 COMMENT '1 = use WooCommerce template, 0 = use custom template',
            send_email tinyint(1) DEFAULT 0,
            status varchar(20) DEFAULT 'active',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            email_subject varchar(255) DEFAULT NULL,
            free_shipping tinyint(1) DEFAULT 0,
            minimum_amount decimal(10,2) DEFAULT 0.00,
            maximum_amount decimal(10,2) DEFAULT 0.00,
            description text DEFAULT NULL,
            prefix varchar(50) DEFAULT NULL,
            length int(11) DEFAULT 8,
            product_ids text DEFAULT NULL,
            category_ids text DEFAULT NULL,
            usage_limit int(11) DEFAULT 1,
            is_debug tinyint(1) DEFAULT 0,
            email_message longtext DEFAULT NULL,
            email_from_name varchar(255) DEFAULT NULL,
            email_from_email varchar(255) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY form_id (form_id),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function get_generators($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'active',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "WHERE status = %s";
        $params = array($args['status']);
        
        $order = "ORDER BY {$args['orderby']} {$args['order']}";
        $limit = "LIMIT %d OFFSET %d";
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gfwcg_generators $where $order $limit",
            array_merge($params, array($args['limit'], $args['offset']))
        );
        
        return $wpdb->get_results($query);
    }

    public static function get_generator($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gfwcg_generators WHERE id = %d",
            $id
        ));
    }

    /**
     * Get the next available ID for a new generator
     *
     * @return int The next available ID
     */
    public static function get_next_available_id() {
        global $wpdb;
        $max_id = $wpdb->get_var("SELECT MAX(id) FROM {$wpdb->prefix}gfwcg_generators");
        return $max_id ? $max_id + 1 : 1;
    }

    public static function save_generator($data) {
        global $wpdb;
        
        $data['updated_at'] = current_time('mysql');
        
        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
            $wpdb->update(
                $wpdb->prefix . 'gfwcg_generators',
                $data,
                array('id' => $id)
            );
            return $id;
        } else {
            // Set the ID for new generators
            $data['id'] = self::get_next_available_id();
            $data['created_at'] = current_time('mysql');
            $wpdb->insert(
                $wpdb->prefix . 'gfwcg_generators',
                $data
            );
            return $data['id'];
        }
    }

    public static function delete_generator($id) {
        global $wpdb;
        return $wpdb->delete(
            $wpdb->prefix . 'gfwcg_generators',
            array('id' => $id),
            array('%d')
        );
    }

    public static function verify_table_structure() {
        global $wpdb;
        
        // Get the current table structure
        $table_name = $wpdb->prefix . 'gfwcg_generators';
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        
        if (!$columns) {
            // Table doesn't exist, create it
            self::create_tables();
            return;
        }
        
        // List of required columns and their definitions
        $required_columns = array(
            'title' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN title varchar(255) DEFAULT NULL',
            'form_id' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN form_id int(11) DEFAULT 0',
            'email_field_id' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN email_field_id int(11) DEFAULT 0',
            'coupon_type' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN coupon_type varchar(50) DEFAULT "random"',
            'coupon_field_id' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN coupon_field_id int(11) DEFAULT NULL',
            'coupon_prefix' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN coupon_prefix varchar(50) DEFAULT NULL',
            'coupon_suffix' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN coupon_suffix varchar(50) DEFAULT NULL',
            'coupon_separator' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN coupon_separator varchar(10) DEFAULT NULL',
            'coupon_length' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN coupon_length int(11) DEFAULT 8',
            'discount_type' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN discount_type varchar(50) DEFAULT "percentage"',
            'discount_amount' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN discount_amount decimal(10,2) DEFAULT 0.00',
            'individual_use' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN individual_use tinyint(1) DEFAULT 0',
            'usage_limit_per_coupon' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN usage_limit_per_coupon int(11) DEFAULT 1',
            'usage_limit_per_user' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN usage_limit_per_user int(11) DEFAULT 1',
            'minimum_amount' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN minimum_amount decimal(10,2) DEFAULT 0.00',
            'maximum_amount' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN maximum_amount decimal(10,2) DEFAULT 0.00',
            'exclude_sale_items' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN exclude_sale_items tinyint(1) DEFAULT 0',
            'allow_free_shipping' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN allow_free_shipping tinyint(1) DEFAULT 0',
            'expiry_days' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN expiry_days int(11) DEFAULT 0',
            'send_email' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN send_email tinyint(1) DEFAULT 0',
            'email_subject' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN email_subject varchar(255) DEFAULT NULL',
            'email_message' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN email_message longtext DEFAULT NULL',
            'email_from_name' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN email_from_name varchar(255) DEFAULT NULL',
            'email_from_email' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN email_from_email varchar(255) DEFAULT NULL',
            'status' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN status varchar(20) DEFAULT "active"',
            'created_at' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN created_at datetime DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        );
        
        // Check each required column
        foreach ($required_columns as $column => $sql) {
            $exists = false;
            foreach ($columns as $col) {
                if ($col->Field === $column) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                $wpdb->query($sql);
            }
        }
    }

    /**
     * Get a generator by slug
     *
     * @param string $slug The generator slug
     * @return object|null The generator object or null if not found
     */
    public static function get_generator_by_slug($slug) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gfwcg_generators';
        
        $generator = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE slug = %s AND status = 'active'",
            $slug
        ));
        
        return $generator;
    }
} 