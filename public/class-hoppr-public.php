<?php

if (!defined('ABSPATH')) {
    exit;
}

class Hoppr_Public {
    
    private $redirects;
    private $analytics;
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'handle_redirects'), 1);
        add_action('wp', array($this, 'handle_wp_redirects'));
        add_action('template_redirect', array($this, 'handle_template_redirects'));
    }
    
    public function handle_redirects() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // Get current URL
        $current_url = $this->get_current_url();
        $redirect = $this->find_redirect($current_url);
        
        if ($redirect) {
            $this->execute_redirect($redirect);
        }
    }
    
    public function handle_wp_redirects() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // Secondary check after WordPress is fully loaded
        $current_url = $this->get_current_url();
        $redirect = $this->find_redirect($current_url);
        
        if ($redirect) {
            $this->execute_redirect($redirect);
        }
    }
    
    public function handle_template_redirects() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // Final check before template loads
        $current_url = $this->get_current_url();
        $redirect = $this->find_redirect($current_url);
        
        if ($redirect) {
            $this->execute_redirect($redirect);
        }
    }
    
    private function get_current_url() {
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // Remove leading slash and normalize for matching
        $normalized_url = ltrim($request_uri, '/');
        $normalized_url = rtrim($normalized_url, '/');
        $normalized_url = strtolower($normalized_url);
        
        return $normalized_url;
    }
    
    private function find_redirect($url) {
        // Get redirects instance
        if (!$this->redirects) {
            $hoppr = hoppr_init();
            $this->redirects = $hoppr->get_redirects();
        }
        
        // First try exact match
        $redirect = $this->redirects->get_redirect_by_source_url($url);
        
        if ($redirect) {
            return $redirect;
        }
        
        // Try without query string if no exact match
        $url_parts = parse_url('http://' . $url);
        if (isset($url_parts['query'])) {
            $url_without_query = str_replace('?' . $url_parts['query'], '', $url);
            $redirect = $this->redirects->get_redirect_by_source_url($url_without_query);
            
            if ($redirect) {
                return $redirect;
            }
        }
        
        return null;
    }
    
    private function execute_redirect($redirect) {
        // Track the click first
        $this->track_redirect_click($redirect);
        
        // Prepare destination URL
        $destination = $redirect['destination_url'];
        
        // Handle query string preservation
        if ($redirect['preserve_query_strings'] && !empty($_SERVER['QUERY_STRING'])) {
            $separator = strpos($destination, '?') !== false ? '&' : '?';
            $destination .= $separator . $_SERVER['QUERY_STRING'];
        }
        
        // Ensure destination URL has protocol
        if (!preg_match('#^https?://#i', $destination)) {
            $destination = 'http://' . $destination;
        }
        
        // Security check - prevent open redirects to malicious sites
        if (!$this->is_safe_redirect($destination)) {
            return;
        }
        
        // Perform the redirect
        $redirect_type = intval($redirect['redirect_type']);
        
        // Set appropriate headers
        if ($redirect_type === 301) {
            status_header(301);
            header('Location: ' . $destination, true, 301);
        } else {
            status_header(302);
            header('Location: ' . $destination, true, 302);
        }
        
        // Add cache headers for 301 redirects
        if ($redirect_type === 301) {
            header('Cache-Control: public, max-age=31536000'); // 1 year
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        } else {
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        }
        
        exit;
    }
    
    private function track_redirect_click($redirect) {
        // Get analytics instance
        if (!$this->analytics) {
            $hoppr = hoppr_init();
            $this->analytics = $hoppr->get_analytics();
        }
        
        // Collect analytics data
        $analytics_data = array(
            'redirect_id' => $redirect['id'],
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $this->get_user_agent(),
            'referrer' => $this->get_referrer(),
            'country_code' => $this->get_country_code(),
            'device_type' => $this->get_device_type()
        );
        
        // Track synchronously for reliability in production environments
        // WordPress cron often fails with caching plugins and server configurations
        try {
            $this->analytics->track_click($analytics_data);
        } catch (Exception $e) {
            // Log error but don't break the redirect
            error_log('Hoppr Analytics Error: ' . $e->getMessage());
        }
        
        // Also schedule async as backup (will work if cron is functioning)
        if (function_exists('wp_schedule_single_event')) {
            wp_schedule_single_event(time() + 1, 'hoppr_track_click', array($analytics_data));
        }
    }
    
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    private function get_user_agent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    private function get_referrer() {
        return $_SERVER['HTTP_REFERER'] ?? '';
    }
    
    private function get_country_code() {
        // Try to get country from Cloudflare
        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            return strtoupper($_SERVER['HTTP_CF_IPCOUNTRY']);
        }
        
        // Try to get from other headers
        if (!empty($_SERVER['HTTP_X_COUNTRY_CODE'])) {
            return strtoupper($_SERVER['HTTP_X_COUNTRY_CODE']);
        }
        
        // Could integrate with GeoIP service here
        return null;
    }
    
    private function get_device_type() {
        $user_agent = $this->get_user_agent();
        
        if (empty($user_agent)) {
            return 'Unknown';
        }
        
        // Mobile detection
        $mobile_keywords = array(
            'Mobile', 'Android', 'iPhone', 'iPod', 'BlackBerry', 
            'Windows Phone', 'Opera Mini', 'IEMobile'
        );
        
        foreach ($mobile_keywords as $keyword) {
            if (stripos($user_agent, $keyword) !== false) {
                return 'Mobile';
            }
        }
        
        // Tablet detection
        $tablet_keywords = array(
            'iPad', 'Android.*Tablet', 'Kindle', 'Silk', 'PlayBook'
        );
        
        foreach ($tablet_keywords as $keyword) {
            if (preg_match('/' . $keyword . '/i', $user_agent)) {
                return 'Tablet';
            }
        }
        
        return 'Desktop';
    }
    
    private function is_safe_redirect($url) {
        // Parse URL components
        $parsed = parse_url($url);
        
        if (!$parsed || empty($parsed['host'])) {
            return false;
        }
        
        // Block dangerous protocols
        $dangerous_schemes = array('javascript', 'data', 'vbscript', 'file', 'ftp');
        if (isset($parsed['scheme']) && in_array(strtolower($parsed['scheme']), $dangerous_schemes)) {
            return false;
        }
        
        // Block local/private IPs
        $ip = gethostbyname($parsed['host']);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }
        
        // Additional security checks can be added here
        // e.g., whitelist/blacklist domains
        
        return true;
    }
    
    public function get_redirect_url($source_url) {
        $redirect = $this->find_redirect($source_url);
        return $redirect ? $redirect['destination_url'] : null;
    }
    
    public function is_redirect_active($source_url) {
        $redirect = $this->find_redirect($source_url);
        return $redirect && $redirect['status'] === 'active';
    }
}