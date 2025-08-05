<?php

class GFWCG_DB {
    private static $table_name = 'gfwcg_generators';

    public static function create_tables() {
        global $wpdb;
        
        // Ensure WordPress upgrade functions are loaded
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
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
            usage_limit_per_coupon int(11) DEFAULT 0,
            usage_limit_per_user int(11) DEFAULT 0,
            allow_free_shipping tinyint(1) DEFAULT 0,
            exclude_sale_items tinyint(1) DEFAULT 0,
            expiry_days int(11) DEFAULT 0,
            minimum_amount decimal(10,2) DEFAULT 0.00,
            maximum_amount decimal(10,2) DEFAULT 0.00,
            product_ids text DEFAULT NULL,
            exclude_products text DEFAULT NULL,
            product_categories text DEFAULT NULL,
            exclude_categories text DEFAULT NULL,
            allowed_emails text DEFAULT NULL,
            email_template text DEFAULT NULL,
            use_wc_email_template tinyint(1) DEFAULT 1 COMMENT '1 = use WooCommerce template, 0 = use custom template',
            send_email tinyint(1) DEFAULT 0,
            status varchar(20) DEFAULT 'active',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            email_subject varchar(255) DEFAULT NULL,
            description text DEFAULT NULL,
            is_debug tinyint(1) DEFAULT 0,
            email_message longtext DEFAULT NULL,
            email_from_name varchar(255) DEFAULT NULL,
            email_from_email varchar(255) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY form_id (form_id),
            KEY status (status)
        ) $charset_collate;";

        // WordPress upgrade functions are available
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

    public static function update_generator($id, $data) {
        global $wpdb;
        
        $data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            $wpdb->prefix . 'gfwcg_generators',
            $data,
            array('id' => $id)
        );
        
        return $result !== false;
    }

    public static function add_generator($data) {
        global $wpdb;
        
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');
        
        if (!isset($data['id'])) {
            $data['id'] = self::get_next_available_id();
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'gfwcg_generators',
            $data
        );
        
        return $result !== false ? $data['id'] : false;
    }

    public static function delete_generator($id) {
        global $wpdb;
        return $wpdb->delete(
            $wpdb->prefix . 'gfwcg_generators',
            array('id' => $id),
            array('%d')
        );
    }

    /**
     * Migrate existing database to remove duplicate columns
     */
    public static function migrate_database() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gfwcg_generators';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            return;
        }
        
        // Get current columns
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        $column_names = array();
        foreach ($columns as $column) {
            $column_names[] = $column->Field;
        }
        
        // Migration steps
        $migrations = array();
        
        // Step 1: Copy data from old columns to new columns if both exist
        if (in_array('minimum_spend', $column_names) && in_array('minimum_amount', $column_names)) {
            $migrations[] = "UPDATE $table_name SET minimum_amount = minimum_spend WHERE minimum_amount = 0 AND minimum_spend > 0";
        }
        
        if (in_array('maximum_spend', $column_names) && in_array('maximum_amount', $column_names)) {
            $migrations[] = "UPDATE $table_name SET maximum_amount = maximum_spend WHERE maximum_amount = 0 AND maximum_spend > 0";
        }
        
        if (in_array('products', $column_names) && in_array('product_ids', $column_names)) {
            $migrations[] = "UPDATE $table_name SET product_ids = products WHERE product_ids IS NULL AND products IS NOT NULL";
        }
        
        if (in_array('category_ids', $column_names) && in_array('product_categories', $column_names)) {
            $migrations[] = "UPDATE $table_name SET product_categories = category_ids WHERE product_categories IS NULL AND category_ids IS NOT NULL";
        }
        
        if (in_array('prefix', $column_names) && in_array('coupon_prefix', $column_names)) {
            $migrations[] = "UPDATE $table_name SET coupon_prefix = prefix WHERE coupon_prefix IS NULL AND prefix IS NOT NULL";
        }
        
        if (in_array('length', $column_names) && in_array('coupon_length', $column_names)) {
            $migrations[] = "UPDATE $table_name SET coupon_length = length WHERE coupon_length = 8 AND length != 8";
        }
        
        if (in_array('free_shipping', $column_names) && in_array('allow_free_shipping', $column_names)) {
            $migrations[] = "UPDATE $table_name SET allow_free_shipping = free_shipping WHERE allow_free_shipping = 0 AND free_shipping = 1";
        }
        
        if (in_array('usage_limit', $column_names) && in_array('usage_limit_per_coupon', $column_names)) {
            $migrations[] = "UPDATE $table_name SET usage_limit_per_coupon = usage_limit WHERE usage_limit_per_coupon = 0 AND usage_limit > 0";
        }
        
        // Step 2: Drop old duplicate columns
        $columns_to_drop = array(
            'minimum_spend',
            'maximum_spend', 
            'products',
            'category_ids',
            'prefix',
            'length',
            'free_shipping',
            'usage_limit',
            'expiry_date',
            'product_brands',
            'exclude_brands'
        );
        
        foreach ($columns_to_drop as $column) {
            if (in_array($column, $column_names)) {
                $migrations[] = "ALTER TABLE $table_name DROP COLUMN $column";
            }
        }
        
        // Execute migrations
        foreach ($migrations as $migration) {
            $wpdb->query($migration);
        }
    }

    public static function verify_table_structure() {
        global $wpdb;
        
        // Get the current table structure
        $table_name = $wpdb->prefix . 'gfwcg_generators';
        
        // Check if table exists first
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if (!$table_exists) {
            // Table doesn't exist, create it
            self::create_tables();
            return;
        }
        
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        
        // List of required columns and their definitions
        $required_columns = array(
            'title' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN title varchar(255) DEFAULT NULL',
            'form_id' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN form_id int(11) DEFAULT 0',
            'email_field_id' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN email_field_id int(11) DEFAULT 0',
            'name_field_id' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN name_field_id int(11) DEFAULT NULL',
            'coupon_type' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN coupon_type varchar(50) DEFAULT "random"',
            'coupon_field_id' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN coupon_field_id int(11) DEFAULT NULL',
            'coupon_prefix' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN coupon_prefix varchar(50) DEFAULT NULL',
            'coupon_suffix' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN coupon_suffix varchar(50) DEFAULT NULL',
            'coupon_separator' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN coupon_separator varchar(10) DEFAULT NULL',
            'coupon_length' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN coupon_length int(11) DEFAULT 8',
            'discount_type' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN discount_type varchar(50) DEFAULT "percentage"',
            'discount_amount' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN discount_amount decimal(10,2) DEFAULT 0.00',
            'individual_use' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN individual_use tinyint(1) DEFAULT 0',
            'usage_limit_per_coupon' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN usage_limit_per_coupon int(11) DEFAULT 0',
            'usage_limit_per_user' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN usage_limit_per_user int(11) DEFAULT 0',
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
            'updated_at' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'product_ids' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN product_ids text DEFAULT NULL',
            'exclude_products' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN exclude_products text DEFAULT NULL',
            'product_categories' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN product_categories text DEFAULT NULL',
            'exclude_categories' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN exclude_categories text DEFAULT NULL',
            'allowed_emails' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN allowed_emails text DEFAULT NULL',
            'email_template' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN email_template text DEFAULT NULL',
            'use_wc_email_template' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN use_wc_email_template tinyint(1) DEFAULT 1',
            'description' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN description text DEFAULT NULL',
            'is_debug' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN is_debug tinyint(1) DEFAULT 0'
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