<?php

if (!defined('ABSPATH')) {
    exit;
}

class Hoppr_Redirects {
    
    private $table_name;
    
    public function __construct() {
        $this->table_name = HOPPR_TABLE_REDIRECTS;
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('wp_ajax_hoppr_add_redirect', array($this, 'ajax_add_redirect'));
        add_action('wp_ajax_hoppr_update_redirect', array($this, 'ajax_update_redirect'));
        add_action('wp_ajax_hoppr_delete_redirect', array($this, 'ajax_delete_redirect'));
        add_action('wp_ajax_hoppr_toggle_redirect', array($this, 'ajax_toggle_redirect'));
        add_action('wp_ajax_hoppr_bulk_action', array($this, 'ajax_bulk_action'));
    }
    
    public function create_redirect($data) {
        global $wpdb;
        
        $data = $this->sanitize_redirect_data($data);
        if (is_wp_error($data)) {
            return $data;
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'source_url' => $data['source_url'],
                'destination_url' => $data['destination_url'],
                'redirect_type' => $data['redirect_type'],
                'preserve_query_strings' => $data['preserve_query_strings'],
                'status' => $data['status'],
                'created_by' => get_current_user_id(),
                'created_date' => current_time('mysql'),
                'modified_date' => current_time('mysql')
            ),
            array(
                '%s', '%s', '%d', '%d', '%s', '%d', '%s', '%s'
            )
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create redirect', 'hoppr'));
        }
        
        $redirect_id = $wpdb->insert_id;
        
        // Clear cache
        $this->clear_cache();
        
        // Generate QR codes
        do_action('hoppr_redirect_created', $redirect_id);
        
        return $redirect_id;
    }
    
    public function update_redirect($id, $data) {
        global $wpdb;
        
        $data = $this->sanitize_redirect_data($data);
        if (is_wp_error($data)) {
            return $data;
        }
        
        $result = $wpdb->update(
            $this->table_name,
            array(
                'source_url' => $data['source_url'],
                'destination_url' => $data['destination_url'],
                'redirect_type' => $data['redirect_type'],
                'preserve_query_strings' => $data['preserve_query_strings'],
                'status' => $data['status'],
                'modified_date' => current_time('mysql')
            ),
            array('id' => $id),
            array(
                '%s', '%s', '%d', '%d', '%s', '%s'
            ),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update redirect', 'hoppr'));
        }
        
        // Clear cache
        $this->clear_cache();
        
        // Regenerate QR codes if destination changed
        do_action('hoppr_redirect_updated', $id);
        
        return true;
    }
    
    public function delete_redirect($id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to delete redirect', 'hoppr'));
        }
        
        // Clear cache
        $this->clear_cache();
        
        // Clean up associated data
        do_action('hoppr_redirect_deleted', $id);
        
        return true;
    }
    
    public function get_redirect($id) {
        global $wpdb;
        
        $redirect = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
        
        return $redirect;
    }
    
    public function get_redirects($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => '',
            'orderby' => 'created_date',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0,
            'search' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "1=1";
        $where_values = array();
        
        if (!empty($args['status'])) {
            $where .= " AND status = %s";
            $where_values[] = $args['status'];
        }
        
        if (!empty($args['search'])) {
            $where .= " AND (source_url LIKE %s OR destination_url LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        if (!$orderby) {
            $orderby = 'created_date DESC';
        }
        
        $limit = '';
        if ($args['limit'] > 0) {
            $limit = $wpdb->prepare("LIMIT %d OFFSET %d", $args['limit'], $args['offset']);
        }
        
        $query = "SELECT * FROM {$this->table_name} WHERE {$where} ORDER BY {$orderby} {$limit}";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    public function get_redirects_count($args = array()) {
        global $wpdb;
        
        $where = "1=1";
        $where_values = array();
        
        if (!empty($args['status'])) {
            $where .= " AND status = %s";
            $where_values[] = $args['status'];
        }
        
        if (!empty($args['search'])) {
            $where .= " AND (source_url LIKE %s OR destination_url LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where}";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return $wpdb->get_var($query);
    }
    
    public function get_redirect_by_source_url($source_url) {
        global $wpdb;
        
        // Try to get from cache first
        $cache_key = 'hoppr_redirect_' . md5($source_url);
        $redirect = wp_cache_get($cache_key, 'hoppr_redirects');
        
        if ($redirect === false) {
            $redirect = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$this->table_name} WHERE source_url = %s AND status = 'active'",
                    $source_url
                ),
                ARRAY_A
            );
            
            // Cache for 1 hour
            wp_cache_set($cache_key, $redirect, 'hoppr_redirects', 3600);
        }
        
        return $redirect;
    }
    
    public function get_active_redirects() {
        $cache_key = 'hoppr_active_redirects';
        $redirects = wp_cache_get($cache_key, 'hoppr_redirects');
        
        if ($redirects === false) {
            $redirects = $this->get_redirects(array(
                'status' => 'active',
                'limit' => 0
            ));
            
            // Cache for 30 minutes
            wp_cache_set($cache_key, $redirects, 'hoppr_redirects', 1800);
        }
        
        return $redirects;
    }
    
    private function sanitize_redirect_data($data) {
        $sanitized = array();
        
        // Source URL validation
        if (empty($data['source_url'])) {
            return new WP_Error('missing_source', __('Source URL is required', 'hoppr'));
        }
        
        $source_url = $this->normalize_url($data['source_url']);
        if (!$this->is_valid_url($source_url)) {
            return new WP_Error('invalid_source', __('Invalid source URL', 'hoppr'));
        }
        
        // Destination URL validation
        if (empty($data['destination_url'])) {
            return new WP_Error('missing_destination', __('Destination URL is required', 'hoppr'));
        }
        
        $destination_url = esc_url_raw($data['destination_url']);
        if (!$this->is_valid_url($destination_url)) {
            return new WP_Error('invalid_destination', __('Invalid destination URL', 'hoppr'));
        }
        
        // Prevent self-redirects
        if ($source_url === $destination_url) {
            return new WP_Error('self_redirect', __('Source and destination URLs cannot be the same', 'hoppr'));
        }
        
        // Redirect type validation
        $redirect_type = intval($data['redirect_type']);
        if (!in_array($redirect_type, array(301, 302))) {
            $redirect_type = 301;
        }
        
        // Query string preservation
        $preserve_query_strings = !empty($data['preserve_query_strings']) ? 1 : 0;
        
        // Status validation
        $status = sanitize_text_field($data['status']);
        if (!in_array($status, array('active', 'inactive'))) {
            $status = 'active';
        }
        
        $sanitized['source_url'] = $source_url;
        $sanitized['destination_url'] = $destination_url;
        $sanitized['redirect_type'] = $redirect_type;
        $sanitized['preserve_query_strings'] = $preserve_query_strings;
        $sanitized['status'] = $status;
        
        return $sanitized;
    }
    
    private function normalize_url($url) {
        // Remove protocol and domain if present (user might paste full URL)
        $url = preg_replace('#^https?://(www\.)?[^/]+/?#i', '', $url);
        
        // Remove leading slash if present
        $url = ltrim($url, '/');
        
        // Remove trailing slash
        $url = rtrim($url, '/');
        
        // Convert to lowercase
        $url = strtolower($url);
        
        // If empty after normalization, return as-is
        if (empty($url)) {
            return $url;
        }
        
        return $url;
    }
    
    private function is_valid_url($url) {
        // Basic URL validation
        if (filter_var('http://' . $url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        
        // Check for malicious patterns
        $dangerous_patterns = array(
            'javascript:', 'data:', 'vbscript:', 'file:', 'ftp:'
        );
        
        foreach ($dangerous_patterns as $pattern) {
            if (stripos($url, $pattern) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    private function clear_cache() {
        wp_cache_delete('hoppr_active_redirects', 'hoppr_redirects');
        wp_cache_flush_group('hoppr_redirects');
    }
    
    public function ajax_add_redirect() {
        check_ajax_referer('hoppr_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'hoppr'));
        }
        
        $result = $this->create_redirect($_POST);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(array(
            'id' => $result,
            'message' => __('Redirect created successfully', 'hoppr'),
            'redirect' => admin_url('admin.php?page=hoppr-redirects&action=edit&id=' . $result)
        ));
    }
    
    public function ajax_update_redirect() {
        check_ajax_referer('hoppr_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'hoppr'));
        }
        
        $id = intval($_POST['id']);
        $result = $this->update_redirect($id, $_POST);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(array(
            'message' => __('Redirect updated successfully', 'hoppr')
        ));
    }
    
    public function ajax_delete_redirect() {
        check_ajax_referer('hoppr_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'hoppr'));
        }
        
        $id = intval($_POST['id']);
        $result = $this->delete_redirect($id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(array(
            'message' => __('Redirect deleted successfully', 'hoppr')
        ));
    }
    
    public function ajax_toggle_redirect() {
        check_ajax_referer('hoppr_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'hoppr'));
        }
        
        $id = intval($_POST['id']);
        $redirect = $this->get_redirect($id);
        
        if (!$redirect) {
            wp_send_json_error(__('Redirect not found', 'hoppr'));
        }
        
        $new_status = $redirect['status'] === 'active' ? 'inactive' : 'active';
        $result = $this->update_redirect($id, array('status' => $new_status));
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(array(
            'status' => $new_status,
            'message' => sprintf(__('Redirect %s successfully', 'hoppr'), $new_status)
        ));
    }
    
    public function ajax_bulk_action() {
        check_ajax_referer('hoppr_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'hoppr'));
        }
        
        $action = sanitize_text_field($_POST['action']);
        $ids = array_map('intval', $_POST['ids']);
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($ids as $id) {
            switch ($action) {
                case 'delete':
                    $result = $this->delete_redirect($id);
                    break;
                case 'activate':
                    $result = $this->update_redirect($id, array('status' => 'active'));
                    break;
                case 'deactivate':
                    $result = $this->update_redirect($id, array('status' => 'inactive'));
                    break;
                default:
                    $result = new WP_Error('invalid_action', __('Invalid action', 'hoppr'));
            }
            
            if (is_wp_error($result)) {
                $error_count++;
            } else {
                $success_count++;
            }
        }
        
        wp_send_json_success(array(
            'success_count' => $success_count,
            'error_count' => $error_count,
            'message' => sprintf(__('%d redirects processed successfully, %d errors', 'hoppr'), $success_count, $error_count)
        ));
    }
}