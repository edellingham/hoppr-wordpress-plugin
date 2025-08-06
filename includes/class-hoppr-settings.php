<?php

if (!defined('ABSPATH')) {
    exit;
}

class Hoppr_Settings {
    
    private $option_group = 'hoppr_settings';
    private $default_permissions = array(
        'administrator' => array('view', 'create', 'edit', 'delete', 'analytics', 'settings')
    );
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_hoppr_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_hoppr_reset_settings', array($this, 'ajax_reset_settings'));
        add_action('wp_ajax_hoppr_test_permissions', array($this, 'ajax_test_permissions'));
    }
    
    public function register_settings() {
        // Analytics retention setting
        register_setting(
            $this->option_group,
            'hoppr_analytics_retention',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_retention_period'),
                'default' => '365'
            )
        );
        
        // Permissions setting
        register_setting(
            $this->option_group,
            'hoppr_permissions',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_permissions'),
                'default' => $this->default_permissions
            )
        );
        
        // Performance settings
        register_setting(
            $this->option_group,
            'hoppr_cache_timeout',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'intval',
                'default' => 3600
            )
        );
        
        register_setting(
            $this->option_group,
            'hoppr_max_redirects',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'intval',
                'default' => 100
            )
        );
        
        // QR Code settings
        register_setting(
            $this->option_group,
            'hoppr_qr_size',
            array(
                'type' => 'integer',
                'sanitize_callback' => array($this, 'sanitize_qr_size'),
                'default' => 200
            )
        );
        
        register_setting(
            $this->option_group,
            'hoppr_qr_auto_generate',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => true
            )
        );
        
        // Security settings
        register_setting(
            $this->option_group,
            'hoppr_security_checks',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => true
            )
        );
        
        register_setting(
            $this->option_group,
            'hoppr_allowed_domains',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_allowed_domains'),
                'default' => ''
            )
        );
        
        // Logging settings
        register_setting(
            $this->option_group,
            'hoppr_enable_logging',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => false
            )
        );
        
        register_setting(
            $this->option_group,
            'hoppr_log_level',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_log_level'),
                'default' => 'error'
            )
        );
    }
    
    // Sanitization callbacks
    public function sanitize_retention_period($value) {
        $allowed_values = array('30', '90', '365', '0');
        return in_array($value, $allowed_values) ? $value : '365';
    }
    
    public function sanitize_permissions($permissions) {
        if (!is_array($permissions)) {
            return $this->default_permissions;
        }
        
        $sanitized = array();
        $valid_permissions = array('view', 'create', 'edit', 'delete', 'analytics', 'settings');
        
        foreach ($permissions as $role => $perms) {
            $role = sanitize_key($role);
            if (empty($role) || !is_array($perms)) {
                continue;
            }
            
            $sanitized[$role] = array();
            foreach ($perms as $perm) {
                $perm = sanitize_key($perm);
                if (in_array($perm, $valid_permissions)) {
                    $sanitized[$role][] = $perm;
                }
            }
        }
        
        // Ensure admin always has all permissions
        $sanitized['administrator'] = $valid_permissions;
        
        return $sanitized;
    }
    
    public function sanitize_qr_size($value) {
        $value = intval($value);
        return ($value >= 100 && $value <= 500) ? $value : 200;
    }
    
    public function sanitize_allowed_domains($value) {
        if (empty($value)) {
            return '';
        }
        
        $domains = array_map('trim', explode("\n", $value));
        $sanitized_domains = array();
        
        foreach ($domains as $domain) {
            $domain = sanitize_text_field($domain);
            if (!empty($domain) && $this->is_valid_domain($domain)) {
                $sanitized_domains[] = $domain;
            }
        }
        
        return implode("\n", $sanitized_domains);
    }
    
    public function sanitize_log_level($value) {
        $allowed_levels = array('error', 'warning', 'info', 'debug');
        return in_array($value, $allowed_levels) ? $value : 'error';
    }
    
    // Helper methods
    private function is_valid_domain($domain) {
        // Remove protocol if present
        $domain = preg_replace('#^https?://#i', '', $domain);
        
        // Basic domain validation
        return filter_var('http://' . $domain, FILTER_VALIDATE_URL) !== false;
    }
    
    // Settings getters
    public function get_analytics_retention() {
        return get_option('hoppr_analytics_retention', '365');
    }
    
    public function get_permissions() {
        return get_option('hoppr_permissions', $this->default_permissions);
    }
    
    public function get_cache_timeout() {
        return intval(get_option('hoppr_cache_timeout', 3600));
    }
    
    public function get_max_redirects() {
        return intval(get_option('hoppr_max_redirects', 100));
    }
    
    public function get_qr_size() {
        return intval(get_option('hoppr_qr_size', 200));
    }
    
    public function is_qr_auto_generate() {
        return get_option('hoppr_qr_auto_generate', true);
    }
    
    public function is_security_checks_enabled() {
        return get_option('hoppr_security_checks', true);
    }
    
    public function get_allowed_domains() {
        $domains = get_option('hoppr_allowed_domains', '');
        return empty($domains) ? array() : array_map('trim', explode("\n", $domains));
    }
    
    public function is_logging_enabled() {
        return get_option('hoppr_enable_logging', false);
    }
    
    public function get_log_level() {
        return get_option('hoppr_log_level', 'error');
    }
    
    // Permission checking
    public function user_can($capability, $user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        $permissions = $this->get_permissions();
        
        foreach ($user->roles as $role) {
            if (isset($permissions[$role]) && in_array($capability, $permissions[$role])) {
                return true;
            }
        }
        
        return false;
    }
    
    public function get_user_capabilities($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return array();
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return array();
        }
        
        $permissions = $this->get_permissions();
        $user_capabilities = array();
        
        foreach ($user->roles as $role) {
            if (isset($permissions[$role])) {
                $user_capabilities = array_merge($user_capabilities, $permissions[$role]);
            }
        }
        
        return array_unique($user_capabilities);
    }
    
    // Settings validation
    public function validate_settings($settings) {
        $errors = array();
        
        // Validate analytics retention
        if (isset($settings['hoppr_analytics_retention'])) {
            $allowed_retention = array('30', '90', '365', '0');
            if (!in_array($settings['hoppr_analytics_retention'], $allowed_retention)) {
                $errors[] = __('Invalid analytics retention period', 'hoppr');
            }
        }
        
        // Validate cache timeout
        if (isset($settings['hoppr_cache_timeout'])) {
            $timeout = intval($settings['hoppr_cache_timeout']);
            if ($timeout < 300 || $timeout > 86400) {
                $errors[] = __('Cache timeout must be between 300 and 86400 seconds', 'hoppr');
            }
        }
        
        // Validate max redirects
        if (isset($settings['hoppr_max_redirects'])) {
            $max_redirects = intval($settings['hoppr_max_redirects']);
            if ($max_redirects < 10 || $max_redirects > 1000) {
                $errors[] = __('Maximum redirects must be between 10 and 1000', 'hoppr');
            }
        }
        
        // Validate QR size
        if (isset($settings['hoppr_qr_size'])) {
            $qr_size = intval($settings['hoppr_qr_size']);
            if ($qr_size < 100 || $qr_size > 500) {
                $errors[] = __('QR code size must be between 100 and 500 pixels', 'hoppr');
            }
        }
        
        return $errors;
    }
    
    // AJAX handlers
    public function ajax_save_settings() {
        check_ajax_referer('hoppr_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'hoppr'));
        }
        
        $settings = $_POST['settings'];
        $errors = $this->validate_settings($settings);
        
        if (!empty($errors)) {
            wp_send_json_error(array(
                'message' => __('Settings validation failed', 'hoppr'),
                'errors' => $errors
            ));
        }
        
        // Save each setting
        $saved_count = 0;
        foreach ($settings as $key => $value) {
            if (strpos($key, 'hoppr_') === 0) {
                update_option($key, $value);
                $saved_count++;
            }
        }
        
        // Clear cache after settings change
        wp_cache_flush();
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d settings saved successfully', 'hoppr'), $saved_count)
        ));
    }
    
    public function ajax_reset_settings() {
        check_ajax_referer('hoppr_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'hoppr'));
        }
        
        // Reset to defaults
        update_option('hoppr_analytics_retention', '365');
        update_option('hoppr_permissions', $this->default_permissions);
        update_option('hoppr_cache_timeout', 3600);
        update_option('hoppr_max_redirects', 100);
        update_option('hoppr_qr_size', 200);
        update_option('hoppr_qr_auto_generate', true);
        update_option('hoppr_security_checks', true);
        update_option('hoppr_allowed_domains', '');
        update_option('hoppr_enable_logging', false);
        update_option('hoppr_log_level', 'error');
        
        // Clear cache
        wp_cache_flush();
        
        wp_send_json_success(array(
            'message' => __('Settings reset to defaults', 'hoppr')
        ));
    }
    
    public function ajax_test_permissions() {
        check_ajax_referer('hoppr_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'hoppr'));
        }
        
        $user_id = intval($_POST['user_id']);
        $capability = sanitize_text_field($_POST['capability']);
        
        $result = $this->user_can($capability, $user_id);
        $capabilities = $this->get_user_capabilities($user_id);
        
        wp_send_json_success(array(
            'can_perform' => $result,
            'all_capabilities' => $capabilities
        ));
    }
    
    // Export/Import settings
    public function export_settings() {
        $settings = array(
            'hoppr_analytics_retention' => $this->get_analytics_retention(),
            'hoppr_permissions' => $this->get_permissions(),
            'hoppr_cache_timeout' => $this->get_cache_timeout(),
            'hoppr_max_redirects' => $this->get_max_redirects(),
            'hoppr_qr_size' => $this->get_qr_size(),
            'hoppr_qr_auto_generate' => $this->is_qr_auto_generate(),
            'hoppr_security_checks' => $this->is_security_checks_enabled(),
            'hoppr_allowed_domains' => implode("\n", $this->get_allowed_domains()),
            'hoppr_enable_logging' => $this->is_logging_enabled(),
            'hoppr_log_level' => $this->get_log_level(),
            'export_date' => current_time('mysql'),
            'plugin_version' => HOPPR_VERSION
        );
        
        return $settings;
    }
    
    public function import_settings($settings) {
        if (!is_array($settings)) {
            return new WP_Error('invalid_format', __('Invalid settings format', 'hoppr'));
        }
        
        $errors = $this->validate_settings($settings);
        if (!empty($errors)) {
            return new WP_Error('validation_failed', implode(', ', $errors));
        }
        
        $imported_count = 0;
        foreach ($settings as $key => $value) {
            if (strpos($key, 'hoppr_') === 0 && $key !== 'hoppr_version') {
                update_option($key, $value);
                $imported_count++;
            }
        }
        
        // Clear cache
        wp_cache_flush();
        
        return $imported_count;
    }
    
    // Settings form helpers
    public function get_available_roles() {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        return $wp_roles->get_names();
    }
    
    public function get_capability_labels() {
        return array(
            'view' => __('View Redirects', 'hoppr'),
            'create' => __('Create Redirects', 'hoppr'),
            'edit' => __('Edit Redirects', 'hoppr'),
            'delete' => __('Delete Redirects', 'hoppr'),
            'analytics' => __('View Analytics', 'hoppr'),
            'settings' => __('Manage Settings', 'hoppr')
        );
    }
    
    public function render_permissions_table() {
        $roles = $this->get_available_roles();
        $capabilities = $this->get_capability_labels();
        $permissions = $this->get_permissions();
        
        echo '<table class="hoppr-permissions-table widefat">';
        echo '<thead><tr><th>' . __('Role', 'hoppr') . '</th>';
        
        foreach ($capabilities as $cap => $label) {
            echo '<th>' . esc_html($label) . '</th>';
        }
        
        echo '</tr></thead><tbody>';
        
        foreach ($roles as $role => $role_name) {
            echo '<tr>';
            echo '<td><strong>' . esc_html($role_name) . '</strong></td>';
            
            foreach ($capabilities as $cap => $label) {
                $checked = isset($permissions[$role]) && in_array($cap, $permissions[$role]);
                $disabled = $role === 'administrator' ? 'disabled' : '';
                
                echo '<td>';
                echo '<input type="checkbox" ';
                echo 'name="hoppr_permissions[' . esc_attr($role) . '][]" ';
                echo 'value="' . esc_attr($cap) . '" ';
                echo checked($checked, true, false) . ' ';
                echo $disabled . ' />';
                echo '</td>';
            }
            
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        
        if (in_array('administrator', array_keys($roles))) {
            echo '<p class="description">' . __('Administrator role always has all permissions.', 'hoppr') . '</p>';
        }
    }
}