<?php

if (!defined('ABSPATH')) {
    exit;
}

class Hoppr_Analytics {
    
    private $table_name;
    
    public function __construct() {
        $this->table_name = HOPPR_TABLE_ANALYTICS;
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('hoppr_track_click', array($this, 'track_click'));
        add_action('hoppr_cleanup_analytics', array($this, 'cleanup_old_data'));
        add_action('wp_ajax_hoppr_get_analytics_data', array($this, 'ajax_get_analytics_data'));
        add_action('wp_ajax_hoppr_export_analytics', array($this, 'ajax_export_analytics'));
        
        // Schedule cleanup if not already scheduled
        if (!wp_next_scheduled('hoppr_cleanup_analytics')) {
            wp_schedule_event(time(), 'daily', 'hoppr_cleanup_analytics');
        }
    }
    
    public function track_click($data) {
        global $wpdb;
        
        // Hash IP address for privacy
        $hashed_ip = $this->hash_ip($data['ip_address']);
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'redirect_id' => intval($data['redirect_id']),
                'click_timestamp' => current_time('mysql'),
                'ip_address' => $hashed_ip,
                'country_code' => $this->sanitize_country_code($data['country_code']),
                'device_type' => $this->sanitize_device_type($data['device_type']),
                'referrer' => esc_url_raw($data['referrer']),
                'user_agent' => sanitize_text_field($data['user_agent'])
            ),
            array(
                '%d', '%s', '%s', '%s', '%s', '%s', '%s'
            )
        );
        
        if ($result === false) {
            error_log('Hoppr: Failed to track click for redirect ID ' . $data['redirect_id']);
        }
        
        return $result !== false;
    }
    
    public function get_redirect_analytics($redirect_id, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'date_from' => date('Y-m-d', strtotime('-30 days')),
            'date_to' => date('Y-m-d'),
            'group_by' => 'day'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = $wpdb->prepare("redirect_id = %d", $redirect_id);
        
        if (!empty($args['date_from'])) {
            $where .= $wpdb->prepare(" AND DATE(click_timestamp) >= %s", $args['date_from']);
        }
        
        if (!empty($args['date_to'])) {
            $where .= $wpdb->prepare(" AND DATE(click_timestamp) <= %s", $args['date_to']);
        }
        
        $group_by = $this->get_group_by_clause($args['group_by']);
        
        $query = "
            SELECT 
                {$group_by} as period,
                COUNT(*) as clicks,
                COUNT(DISTINCT ip_address) as unique_clicks
            FROM {$this->table_name} 
            WHERE {$where}
            GROUP BY period
            ORDER BY period ASC
        ";
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    public function get_clicks_count($args = array()) {
        global $wpdb;
        
        $where = "1=1";
        $where_values = array();
        
        if (!empty($args['redirect_id'])) {
            $where .= " AND redirect_id = %d";
            $where_values[] = $args['redirect_id'];
        }
        
        if (!empty($args['date_from'])) {
            $where .= " AND DATE(click_timestamp) >= %s";
            $where_values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where .= " AND DATE(click_timestamp) <= %s";
            $where_values[] = $args['date_to'];
        }
        
        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where}";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return intval($wpdb->get_var($query));
    }
    
    public function get_unique_clicks_count($args = array()) {
        global $wpdb;
        
        $where = "1=1";
        $where_values = array();
        
        if (!empty($args['redirect_id'])) {
            $where .= " AND redirect_id = %d";
            $where_values[] = $args['redirect_id'];
        }
        
        if (!empty($args['date_from'])) {
            $where .= " AND DATE(click_timestamp) >= %s";
            $where_values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where .= " AND DATE(click_timestamp) <= %s";
            $where_values[] = $args['date_to'];
        }
        
        $query = "SELECT COUNT(DISTINCT ip_address) FROM {$this->table_name} WHERE {$where}";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return intval($wpdb->get_var($query));
    }
    
    public function get_total_clicks() {
        global $wpdb;
        return intval($wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}"));
    }
    
    public function get_clicks_by_country($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'date_from' => date('Y-m-d', strtotime('-30 days')),
            'date_to' => date('Y-m-d'),
            'limit' => 10
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "country_code IS NOT NULL AND country_code != ''";
        $where_values = array();
        
        if (!empty($args['redirect_id'])) {
            $where .= " AND redirect_id = %d";
            $where_values[] = $args['redirect_id'];
        }
        
        if (!empty($args['date_from'])) {
            $where .= " AND DATE(click_timestamp) >= %s";
            $where_values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where .= " AND DATE(click_timestamp) <= %s";
            $where_values[] = $args['date_to'];
        }
        
        $limit = intval($args['limit']);
        
        $query = "
            SELECT 
                country_code,
                COUNT(*) as clicks,
                COUNT(DISTINCT ip_address) as unique_clicks
            FROM {$this->table_name} 
            WHERE {$where}
            GROUP BY country_code 
            ORDER BY clicks DESC 
            LIMIT {$limit}
        ";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    public function get_clicks_by_device($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'date_from' => date('Y-m-d', strtotime('-30 days')),
            'date_to' => date('Y-m-d')
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "device_type IS NOT NULL AND device_type != ''";
        $where_values = array();
        
        if (!empty($args['redirect_id'])) {
            $where .= " AND redirect_id = %d";
            $where_values[] = $args['redirect_id'];
        }
        
        if (!empty($args['date_from'])) {
            $where .= " AND DATE(click_timestamp) >= %s";
            $where_values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where .= " AND DATE(click_timestamp) <= %s";
            $where_values[] = $args['date_to'];
        }
        
        $query = "
            SELECT 
                device_type,
                COUNT(*) as clicks,
                COUNT(DISTINCT ip_address) as unique_clicks
            FROM {$this->table_name} 
            WHERE {$where}
            GROUP BY device_type 
            ORDER BY clicks DESC
        ";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    public function get_daily_clicks($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'date_from' => date('Y-m-d', strtotime('-30 days')),
            'date_to' => date('Y-m-d')
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "1=1";
        $where_values = array();
        
        if (!empty($args['redirect_id'])) {
            $where .= " AND redirect_id = %d";
            $where_values[] = $args['redirect_id'];
        }
        
        if (!empty($args['date_from'])) {
            $where .= " AND DATE(click_timestamp) >= %s";
            $where_values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where .= " AND DATE(click_timestamp) <= %s";
            $where_values[] = $args['date_to'];
        }
        
        $query = "
            SELECT 
                DATE(click_timestamp) as date,
                COUNT(*) as clicks
            FROM {$this->table_name} 
            WHERE {$where}
            GROUP BY DATE(click_timestamp) 
            ORDER BY date ASC
        ";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Fill in missing dates with 0 clicks
        $date_range = array();
        $start_date = new DateTime($args['date_from']);
        $end_date = new DateTime($args['date_to']);
        
        while ($start_date <= $end_date) {
            $date_range[$start_date->format('Y-m-d')] = 0;
            $start_date->modify('+1 day');
        }
        
        // Merge with actual data
        foreach ($results as $result) {
            $date_range[$result['date']] = intval($result['clicks']);
        }
        
        // Convert to array format expected by Chart.js
        $formatted_results = array();
        foreach ($date_range as $date => $clicks) {
            $formatted_results[] = array(
                'date' => $date,
                'clicks' => $clicks
            );
        }
        
        return $formatted_results;
    }
    
    public function get_top_referrers($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'date_from' => date('Y-m-d', strtotime('-30 days')),
            'date_to' => date('Y-m-d'),
            'limit' => 10
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "referrer IS NOT NULL AND referrer != ''";
        $where_values = array();
        
        if (!empty($args['redirect_id'])) {
            $where .= " AND redirect_id = %d";
            $where_values[] = $args['redirect_id'];
        }
        
        if (!empty($args['date_from'])) {
            $where .= " AND DATE(click_timestamp) >= %s";
            $where_values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where .= " AND DATE(click_timestamp) <= %s";
            $where_values[] = $args['date_to'];
        }
        
        $limit = intval($args['limit']);
        
        $query = "
            SELECT 
                referrer,
                COUNT(*) as clicks
            FROM {$this->table_name} 
            WHERE {$where}
            GROUP BY referrer 
            ORDER BY clicks DESC 
            LIMIT {$limit}
        ";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    public function get_top_redirects($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'date_from' => date('Y-m-d', strtotime('-30 days')),
            'date_to' => date('Y-m-d'),
            'limit' => 10
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "1=1";
        $where_values = array();
        
        if (!empty($args['date_from'])) {
            $where .= " AND DATE(a.click_timestamp) >= %s";
            $where_values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where .= " AND DATE(a.click_timestamp) <= %s";
            $where_values[] = $args['date_to'];
        }
        
        $limit = intval($args['limit']);
        $redirects_table = HOPPR_TABLE_REDIRECTS;
        
        $query = "
            SELECT 
                r.id,
                r.source_url,
                r.destination_url,
                COUNT(a.id) as click_count,
                COUNT(DISTINCT a.ip_address) as unique_clicks
            FROM {$redirects_table} r
            LEFT JOIN {$this->table_name} a ON r.id = a.redirect_id
            WHERE {$where} AND r.status = 'active'
            GROUP BY r.id 
            ORDER BY click_count DESC 
            LIMIT {$limit}
        ";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    public function cleanup_old_data() {
        global $wpdb;
        
        $retention_days = get_option('hoppr_analytics_retention', '365');
        
        // Don't cleanup if retention is set to forever (0)
        if ($retention_days == '0') {
            return;
        }
        
        $cutoff_date = date('Y-m-d', strtotime("-{$retention_days} days"));
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE DATE(click_timestamp) < %s",
                $cutoff_date
            )
        );
        
        if ($deleted !== false) {
            error_log("Hoppr: Cleaned up {$deleted} old analytics records");
        }
        
        return $deleted;
    }
    
    private function hash_ip($ip) {
        // Use a salt to make the hash more secure
        $salt = get_option('hoppr_ip_salt');
        if (!$salt) {
            $salt = wp_generate_password(32, true, true);
            add_option('hoppr_ip_salt', $salt);
        }
        
        return hash('sha256', $ip . $salt);
    }
    
    private function sanitize_country_code($country_code) {
        if (empty($country_code)) {
            return null;
        }
        
        $country_code = strtoupper(sanitize_text_field($country_code));
        
        // Validate it's a 2-letter country code
        if (strlen($country_code) !== 2 || !ctype_alpha($country_code)) {
            return null;
        }
        
        return $country_code;
    }
    
    private function sanitize_device_type($device_type) {
        if (empty($device_type)) {
            return 'Unknown';
        }
        
        $allowed_types = array('Desktop', 'Mobile', 'Tablet', 'Unknown');
        $device_type = sanitize_text_field($device_type);
        
        return in_array($device_type, $allowed_types) ? $device_type : 'Unknown';
    }
    
    private function get_group_by_clause($group_by) {
        switch ($group_by) {
            case 'hour':
                return "DATE_FORMAT(click_timestamp, '%Y-%m-%d %H:00:00')";
            case 'day':
                return "DATE(click_timestamp)";
            case 'week':
                return "DATE_FORMAT(click_timestamp, '%Y-%u')";
            case 'month':
                return "DATE_FORMAT(click_timestamp, '%Y-%m')";
            case 'year':
                return "YEAR(click_timestamp)";
            default:
                return "DATE(click_timestamp)";
        }
    }
    
    public function ajax_get_analytics_data() {
        check_ajax_referer('hoppr_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'hoppr'));
        }
        
        $redirect_id = intval($_POST['redirect_id']);
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        $chart_type = sanitize_text_field($_POST['chart_type']);
        
        $data = array();
        
        switch ($chart_type) {
            case 'clicks_over_time':
                $data = $this->get_redirect_analytics($redirect_id, array(
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                    'group_by' => 'day'
                ));
                break;
                
            case 'country_breakdown':
                $data = $this->get_clicks_by_country(array(
                    'redirect_id' => $redirect_id,
                    'date_from' => $date_from,
                    'date_to' => $date_to
                ));
                break;
                
            case 'device_breakdown':
                $data = $this->get_clicks_by_device(array(
                    'redirect_id' => $redirect_id,
                    'date_from' => $date_from,
                    'date_to' => $date_to
                ));
                break;
                
            case 'top_referrers':
                $data = $this->get_top_referrers(array(
                    'redirect_id' => $redirect_id,
                    'date_from' => $date_from,
                    'date_to' => $date_to
                ));
                break;
        }
        
        wp_send_json_success($data);
    }
    
    public function ajax_export_analytics() {
        check_ajax_referer('hoppr_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'hoppr'));
        }
        
        $redirect_id = intval($_GET['redirect_id']);
        $date_from = sanitize_text_field($_GET['date_from']);
        $date_to = sanitize_text_field($_GET['date_to']);
        
        $this->export_analytics_csv($redirect_id, $date_from, $date_to);
    }
    
    private function export_analytics_csv($redirect_id = 0, $date_from = '', $date_to = '') {
        global $wpdb;
        
        $filename = 'hoppr-analytics-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            'Redirect ID',
            'Source URL',
            'Destination URL',
            'Click Timestamp',
            'Country Code',
            'Device Type',
            'Referrer'
        ));
        
        // Build query
        $where = "1=1";
        $where_values = array();
        
        if ($redirect_id > 0) {
            $where .= " AND a.redirect_id = %d";
            $where_values[] = $redirect_id;
        }
        
        if (!empty($date_from)) {
            $where .= " AND DATE(a.click_timestamp) >= %s";
            $where_values[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $where .= " AND DATE(a.click_timestamp) <= %s";
            $where_values[] = $date_to;
        }
        
        $redirects_table = HOPPR_TABLE_REDIRECTS;
        
        $query = "
            SELECT 
                a.redirect_id,
                r.source_url,
                r.destination_url,
                a.click_timestamp,
                a.country_code,
                a.device_type,
                a.referrer
            FROM {$this->table_name} a
            LEFT JOIN {$redirects_table} r ON a.redirect_id = r.id
            WHERE {$where}
            ORDER BY a.click_timestamp DESC
        ";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        foreach ($results as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}