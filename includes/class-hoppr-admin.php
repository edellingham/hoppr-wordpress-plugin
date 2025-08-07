<?php

if (!defined('ABSPATH')) {
    exit;
}

class Hoppr_Admin {
    
    private $plugin_name;
    private $version;
    
    public function __construct() {
        $this->plugin_name = 'hoppr';
        $this->version = HOPPR_VERSION;
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_filter('plugin_action_links_' . HOPPR_PLUGIN_BASENAME, array($this, 'add_plugin_action_links'));
        
        // Debug AJAX handlers
        add_action('wp_ajax_hoppr_test_analytics', array($this, 'ajax_test_analytics'));
        add_action('wp_ajax_hoppr_clear_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_hoppr_recreate_tables', array($this, 'ajax_recreate_tables'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Hoppr Redirects', 'hoppr'),
            __('Hoppr', 'hoppr'),
            'manage_options',
            'hoppr',
            array($this, 'display_dashboard_page'),
            'dashicons-randomize',
            30
        );
        
        add_submenu_page(
            'hoppr',
            __('Dashboard', 'hoppr'),
            __('Dashboard', 'hoppr'),
            'manage_options',
            'hoppr',
            array($this, 'display_dashboard_page')
        );
        
        add_submenu_page(
            'hoppr',
            __('Redirects', 'hoppr'),
            __('Redirects', 'hoppr'),
            'manage_options',
            'hoppr-redirects',
            array($this, 'display_redirects_page')
        );
        
        add_submenu_page(
            'hoppr',
            __('Analytics', 'hoppr'),
            __('Analytics', 'hoppr'),
            'manage_options',
            'hoppr-analytics',
            array($this, 'display_analytics_page')
        );
        
        add_submenu_page(
            'hoppr',
            __('Settings', 'hoppr'),
            __('Settings', 'hoppr'),
            'manage_options',
            'hoppr-settings',
            array($this, 'display_settings_page')
        );
        
        add_submenu_page(
            'hoppr',
            __('Debug', 'hoppr'),
            __('Debug', 'hoppr'),
            'manage_options',
            'hoppr-debug',
            array($this, 'display_debug_page')
        );
    }
    
    public function admin_init() {
        // Register settings
        register_setting('hoppr_settings', 'hoppr_analytics_retention');
        register_setting('hoppr_settings', 'hoppr_permissions');
        
        // Add settings sections
        add_settings_section(
            'hoppr_general_settings',
            __('General Settings', 'hoppr'),
            array($this, 'general_settings_callback'),
            'hoppr_settings'
        );
        
        add_settings_section(
            'hoppr_analytics_settings',
            __('Analytics Settings', 'hoppr'),
            array($this, 'analytics_settings_callback'),
            'hoppr_settings'
        );
        
        // Add settings fields
        add_settings_field(
            'hoppr_analytics_retention',
            __('Analytics Data Retention', 'hoppr'),
            array($this, 'analytics_retention_callback'),
            'hoppr_settings',
            'hoppr_analytics_settings'
        );
    }
    
    public function enqueue_styles($hook) {
        if (!$this->is_hoppr_admin_page($hook)) {
            return;
        }
        
        wp_enqueue_style(
            $this->plugin_name,
            HOPPR_PLUGIN_URL . 'admin/css/hoppr-admin.css',
            array(),
            $this->version,
            'all'
        );
    }
    
    public function enqueue_scripts($hook) {
        if (!$this->is_hoppr_admin_page($hook)) {
            return;
        }
        
        wp_enqueue_script(
            $this->plugin_name,
            HOPPR_PLUGIN_URL . 'admin/js/hoppr-admin.js',
            array('jquery'),
            $this->version,
            false
        );
        
        // Enqueue Chart.js for analytics page
        if ($hook === 'hoppr_page_hoppr-analytics') {
            wp_enqueue_script(
                'chart-js',
                HOPPR_PLUGIN_URL . 'admin/js/chart.min.js',
                array(),
                '3.9.1',
                false
            );
        }
        
        // Localize script for AJAX
        wp_localize_script(
            $this->plugin_name,
            'hoppr_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hoppr_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Are you sure you want to delete this redirect?', 'hoppr'),
                    'confirm_bulk_delete' => __('Are you sure you want to delete the selected redirects?', 'hoppr'),
                    'no_items_selected' => __('Please select at least one item', 'hoppr'),
                    'processing' => __('Processing...', 'hoppr'),
                    'error' => __('An error occurred. Please try again.', 'hoppr')
                )
            )
        );
    }
    
    public function admin_notices() {
        if (!$this->is_hoppr_admin_page()) {
            return;
        }
        
        // Check for success/error messages
        if (isset($_GET['message'])) {
            $message = sanitize_text_field($_GET['message']);
            $messages = array(
                'redirect_created' => __('Redirect created successfully.', 'hoppr'),
                'redirect_updated' => __('Redirect updated successfully.', 'hoppr'),
                'redirect_deleted' => __('Redirect deleted successfully.', 'hoppr'),
                'settings_saved' => __('Settings saved successfully.', 'hoppr')
            );
            
            if (isset($messages[$message])) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($messages[$message]) . '</p></div>';
            }
        }
        
        if (isset($_GET['error'])) {
            $error = sanitize_text_field($_GET['error']);
            $errors = array(
                'invalid_redirect' => __('Invalid redirect data provided.', 'hoppr'),
                'redirect_exists' => __('A redirect with this source URL already exists.', 'hoppr'),
                'database_error' => __('Database error occurred. Please try again.', 'hoppr')
            );
            
            if (isset($errors[$error])) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($errors[$error]) . '</p></div>';
            }
        }
    }
    
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=hoppr-settings') . '">' . __('Settings', 'hoppr') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    public function display_dashboard_page() {
        $this->render_admin_page('dashboard');
    }
    
    public function display_redirects_page() {
        $this->render_admin_page('redirects');
    }
    
    public function display_analytics_page() {
        $this->render_admin_page('analytics');
    }
    
    public function display_settings_page() {
        $this->render_admin_page('settings');
    }
    
    public function display_debug_page() {
        $this->render_admin_page('debug');
    }
    
    private function render_admin_page($page) {
        $template_path = HOPPR_PLUGIN_DIR . 'admin/partials/hoppr-admin-' . $page . '.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap"><h1>' . __('Page not found', 'hoppr') . '</h1></div>';
        }
    }
    
    private function is_hoppr_admin_page($hook = null) {
        if ($hook === null) {
            $hook = get_current_screen()->id ?? '';
        }
        
        $hoppr_pages = array(
            'toplevel_page_hoppr',
            'hoppr_page_hoppr-redirects',
            'hoppr_page_hoppr-analytics',
            'hoppr_page_hoppr-settings',
            'hoppr_page_hoppr-debug'
        );
        
        return in_array($hook, $hoppr_pages);
    }
    
    public function general_settings_callback() {
        echo '<p>' . __('General plugin settings.', 'hoppr') . '</p>';
    }
    
    public function analytics_settings_callback() {
        echo '<p>' . __('Configure analytics and data retention settings.', 'hoppr') . '</p>';
    }
    
    public function analytics_retention_callback() {
        $retention = get_option('hoppr_analytics_retention', '365');
        echo '<select name="hoppr_analytics_retention" id="hoppr_analytics_retention">';
        echo '<option value="30"' . selected($retention, '30', false) . '>' . __('30 days', 'hoppr') . '</option>';
        echo '<option value="90"' . selected($retention, '90', false) . '>' . __('90 days', 'hoppr') . '</option>';
        echo '<option value="365"' . selected($retention, '365', false) . '>' . __('1 year', 'hoppr') . '</option>';
        echo '<option value="0"' . selected($retention, '0', false) . '>' . __('Forever', 'hoppr') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('How long to keep analytics data before automatic cleanup.', 'hoppr') . '</p>';
    }
    
    public function get_dashboard_stats() {
        $hoppr = hoppr_init();
        $redirects = $hoppr->get_redirects();
        $analytics = $hoppr->get_analytics();
        
        return array(
            'total_redirects' => $redirects->get_redirects_count(),
            'active_redirects' => $redirects->get_redirects_count(array('status' => 'active')),
            'inactive_redirects' => $redirects->get_redirects_count(array('status' => 'inactive')),
            'total_clicks' => $analytics->get_total_clicks(),
            'clicks_today' => $analytics->get_clicks_count(array(
                'date_from' => date('Y-m-d'),
                'date_to' => date('Y-m-d')
            )),
            'clicks_this_month' => $analytics->get_clicks_count(array(
                'date_from' => date('Y-m-01'),
                'date_to' => date('Y-m-t')
            ))
        );
    }
    
    public function get_recent_redirects($limit = 5) {
        $hoppr = hoppr_init();
        $redirects = $hoppr->get_redirects();
        
        return $redirects->get_redirects(array(
            'orderby' => 'created_date',
            'order' => 'DESC',
            'limit' => $limit
        ));
    }
    
    public function get_top_redirects($limit = 5) {
        $hoppr = hoppr_init();
        $analytics = $hoppr->get_analytics();
        
        return $analytics->get_top_redirects(array(
            'limit' => $limit,
            'date_from' => date('Y-m-d', strtotime('-30 days')),
            'date_to' => date('Y-m-d')
        ));
    }
    
    /**
     * AJAX handler to test analytics tracking
     */
    public function ajax_test_analytics() {
        check_ajax_referer('hoppr_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Test analytics tracking
        $hoppr = hoppr_init();
        $analytics = $hoppr->get_analytics();
        
        $test_data = array(
            'redirect_id' => 999, // Test redirect ID
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Test User Agent',
            'referrer' => 'https://test-referrer.com',
            'country_code' => 'US',
            'device_type' => 'Desktop'
        );
        
        $result = $analytics->track_click($test_data);
        
        if ($result) {
            wp_send_json_success('Analytics tracking test successful. Check the Recent Analytics Data section.');
        } else {
            wp_send_json_error('Analytics tracking test failed. Check error logs.');
        }
    }
    
    /**
     * AJAX handler to clear error logs
     */
    public function ajax_clear_logs() {
        check_ajax_referer('hoppr_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Clear PHP error log if accessible
        $log_file = ini_get('error_log');
        if ($log_file && file_exists($log_file) && is_writable($log_file)) {
            file_put_contents($log_file, '');
            wp_send_json_success('Error logs cleared successfully.');
        } else {
            wp_send_json_error('Unable to clear error logs. Log file not accessible.');
        }
    }
    
    /**
     * AJAX handler to recreate database tables
     */
    public function ajax_recreate_tables() {
        check_ajax_referer('hoppr_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Drop existing tables
        $tables = array(
            HOPPR_TABLE_REDIRECTS,
            HOPPR_TABLE_ANALYTICS, 
            HOPPR_TABLE_QR_CODES
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
        
        // Recreate tables using the activation method
        try {
            Hoppr::activate();
            wp_send_json_success('Database tables recreated successfully.');
        } catch (Exception $e) {
            wp_send_json_error('Failed to recreate tables: ' . $e->getMessage());
        }
    }
}