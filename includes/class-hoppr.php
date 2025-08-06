<?php

if (!defined('ABSPATH')) {
    exit;
}

class Hoppr {
    
    private static $instance = null;
    
    private $redirects;
    private $analytics;
    private $qr_codes;
    private $admin;
    private $public;
    private $settings;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->define_constants();
        $this->init_hooks();
        $this->load_dependencies();
        $this->init_components();
    }
    
    private function define_constants() {
        if (!defined('HOPPR_TABLE_REDIRECTS')) {
            define('HOPPR_TABLE_REDIRECTS', $GLOBALS['wpdb']->prefix . 'hoppr_redirects');
        }
        if (!defined('HOPPR_TABLE_ANALYTICS')) {
            define('HOPPR_TABLE_ANALYTICS', $GLOBALS['wpdb']->prefix . 'hoppr_analytics');
        }
        if (!defined('HOPPR_TABLE_QR_CODES')) {
            define('HOPPR_TABLE_QR_CODES', $GLOBALS['wpdb']->prefix . 'hoppr_qr_codes');
        }
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('wp_loaded', array($this, 'wp_loaded'));
    }
    
    private function load_dependencies() {
        require_once HOPPR_PLUGIN_DIR . 'includes/class-hoppr-redirects.php';
        require_once HOPPR_PLUGIN_DIR . 'includes/class-hoppr-analytics.php';
        require_once HOPPR_PLUGIN_DIR . 'includes/class-hoppr-qr-codes.php';
        require_once HOPPR_PLUGIN_DIR . 'includes/class-hoppr-settings.php';
        
        if (is_admin()) {
            require_once HOPPR_PLUGIN_DIR . 'includes/class-hoppr-admin.php';
        }
        
        if (!is_admin()) {
            require_once HOPPR_PLUGIN_DIR . 'public/class-hoppr-public.php';
        }
    }
    
    private function init_components() {
        $this->redirects = new Hoppr_Redirects();
        $this->analytics = new Hoppr_Analytics();
        $this->qr_codes = new Hoppr_QR_Codes();
        $this->settings = new Hoppr_Settings();
        
        if (is_admin()) {
            $this->admin = new Hoppr_Admin();
        }
        
        if (!is_admin()) {
            $this->public = new Hoppr_Public();
        }
    }
    
    public function init() {
        load_plugin_textdomain('hoppr', false, dirname(HOPPR_PLUGIN_BASENAME) . '/languages/');
    }
    
    public function wp_loaded() {
        // Additional initialization after WordPress is fully loaded
    }
    
    public static function activate() {
        global $wpdb;
        
        // Define table names locally for activation
        $redirects_table = $wpdb->prefix . 'hoppr_redirects';
        $analytics_table = $wpdb->prefix . 'hoppr_analytics';
        $qr_codes_table = $wpdb->prefix . 'hoppr_qr_codes';
        
        // Use direct SQL queries instead of dbDelta for reliability
        
        // Create redirects table
        $redirects_sql = "CREATE TABLE IF NOT EXISTS $redirects_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            source_url varchar(255) NOT NULL,
            destination_url text NOT NULL,
            redirect_type int(3) NOT NULL DEFAULT 301,
            preserve_query_strings tinyint(1) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by bigint(20) unsigned NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY source_url (source_url),
            KEY status (status),
            KEY created_by (created_by),
            KEY redirect_type (redirect_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $wpdb->query($redirects_sql);
        
        // Create analytics table
        $analytics_sql = "CREATE TABLE IF NOT EXISTS $analytics_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            redirect_id bigint(20) unsigned NOT NULL,
            click_timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(64) NOT NULL,
            country_code varchar(2) DEFAULT NULL,
            device_type varchar(20) DEFAULT NULL,
            referrer text DEFAULT NULL,
            user_agent text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY redirect_id (redirect_id),
            KEY click_timestamp (click_timestamp),
            KEY country_code (country_code),
            KEY device_type (device_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $wpdb->query($analytics_sql);
        
        // Create QR codes table
        $qr_codes_sql = "CREATE TABLE IF NOT EXISTS $qr_codes_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            redirect_id bigint(20) unsigned NOT NULL,
            png_file_path varchar(255) DEFAULT NULL,
            svg_file_path varchar(255) DEFAULT NULL,
            generated_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY redirect_id (redirect_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $wpdb->query($qr_codes_sql);
        
        // Create uploads directory for QR codes
        $upload_dir = wp_upload_dir();
        $hoppr_dir = $upload_dir['basedir'] . '/hoppr/qr-codes';
        if (!file_exists($hoppr_dir)) {
            wp_mkdir_p($hoppr_dir);
            
            // Add .htaccess for security
            $htaccess_content = "# Hoppr QR Codes Directory\n";
            $htaccess_content .= "Options -Indexes\n";
            $htaccess_content .= "<Files *.php>\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</Files>\n";
            file_put_contents($hoppr_dir . '/.htaccess', $htaccess_content);
        }
        
        // Set default options
        add_option('hoppr_version', HOPPR_VERSION);
        add_option('hoppr_analytics_retention', '365'); // days
        add_option('hoppr_permissions', array(
            'administrator' => array('view', 'create', 'edit', 'delete', 'analytics', 'settings')
        ));
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public static function deactivate() {
        // Clear any cached data
        wp_cache_flush();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public static function uninstall() {
        global $wpdb;
        
        // Remove all plugin options
        delete_option('hoppr_version');
        delete_option('hoppr_analytics_retention');
        delete_option('hoppr_permissions');
        
        // Drop tables (define locally for uninstall)
        $redirects_table = $wpdb->prefix . 'hoppr_redirects';
        $analytics_table = $wpdb->prefix . 'hoppr_analytics';
        $qr_codes_table = $wpdb->prefix . 'hoppr_qr_codes';
        
        $wpdb->query("DROP TABLE IF EXISTS " . $qr_codes_table);
        $wpdb->query("DROP TABLE IF EXISTS " . $analytics_table);
        $wpdb->query("DROP TABLE IF EXISTS " . $redirects_table);
        
        // Remove upload directory
        $upload_dir = wp_upload_dir();
        $hoppr_dir = $upload_dir['basedir'] . '/hoppr';
        if (is_dir($hoppr_dir)) {
            self::remove_directory($hoppr_dir);
        }
        
        // Clear cache
        wp_cache_flush();
    }
    
    private static function remove_directory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                self::remove_directory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
    
    public function get_redirects() {
        return $this->redirects;
    }
    
    public function get_analytics() {
        return $this->analytics;
    }
    
    public function get_qr_codes() {
        return $this->qr_codes;
    }
    
    public function get_admin() {
        return $this->admin;
    }
    
    public function get_public() {
        return $this->public;
    }
    
    public function get_settings() {
        return $this->settings;
    }
}