<?php

if (!defined('ABSPATH')) {
    exit;
}

class Hoppr_QR_Codes {
    
    private $table_name;
    private $upload_dir;
    
    public function __construct() {
        $this->table_name = HOPPR_TABLE_QR_CODES;
        $this->setup_upload_dir();
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('hoppr_redirect_created', array($this, 'generate_qr_codes_for_redirect'));
        add_action('hoppr_redirect_updated', array($this, 'regenerate_qr_codes_for_redirect'));
        add_action('hoppr_redirect_deleted', array($this, 'delete_qr_codes_for_redirect'));
        add_action('wp_ajax_hoppr_regenerate_qr', array($this, 'ajax_regenerate_qr'));
        add_action('wp_ajax_hoppr_download_qr', array($this, 'ajax_download_qr'));
    }
    
    private function setup_upload_dir() {
        $upload_dir = wp_upload_dir();
        $this->upload_dir = $upload_dir['basedir'] . '/hoppr/qr-codes';
        
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
            
            // Add .htaccess for security
            $htaccess_content = "# Hoppr QR Codes Directory\n";
            $htaccess_content .= "Options -Indexes\n";
            $htaccess_content .= "<Files *.php>\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</Files>\n";
            file_put_contents($this->upload_dir . '/.htaccess', $htaccess_content);
        }
    }
    
    /**
     * Generate QR codes for a redirect.
     * 
     * @param int $redirect_id The redirect ID to generate QR codes for.
     * @return bool True on success, false on failure.
     */
    public function generate_qr_codes_for_redirect($redirect_id) {
        global $wpdb;
        
        // Get redirect data
        $redirects_table = HOPPR_TABLE_REDIRECTS;
        $redirect = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `" . esc_sql($redirects_table) . "` WHERE id = %d",
                $redirect_id
            ),
            ARRAY_A
        );
        
        if (!$redirect) {
            return false;
        }
        
        // Generate QR code URL (this will be the short URL that redirects)
        $qr_url = $this->get_redirect_url($redirect['source_url']);
        
        // Generate PNG and SVG versions
        $png_path = $this->generate_png_qr($redirect_id, $qr_url);
        $svg_path = $this->generate_svg_qr($redirect_id, $qr_url);
        
        // Store paths in database
        $result = $wpdb->replace(
            $this->table_name,
            array(
                'redirect_id' => $redirect_id,
                'png_file_path' => $png_path,
                'svg_file_path' => $svg_path,
                'generated_date' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s')
        );
        
        return $result !== false;
    }
    
    public function regenerate_qr_codes_for_redirect($redirect_id) {
        // Delete existing QR codes first
        $this->delete_qr_codes_for_redirect($redirect_id);
        
        // Generate new ones
        return $this->generate_qr_codes_for_redirect($redirect_id);
    }
    
    /**
     * Delete QR codes for a redirect.
     * 
     * @param int $redirect_id The redirect ID.
     * @return bool True on success, false on failure.
     */
    public function delete_qr_codes_for_redirect($redirect_id) {
        global $wpdb;
        
        // Get existing QR code record
        $qr_record = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `" . esc_sql($this->table_name) . "` WHERE redirect_id = %d",
                $redirect_id
            ),
            ARRAY_A
        );
        
        if ($qr_record) {
            // Delete physical files
            if (!empty($qr_record['png_file_path']) && file_exists($qr_record['png_file_path'])) {
                unlink($qr_record['png_file_path']);
            }
            
            if (!empty($qr_record['svg_file_path']) && file_exists($qr_record['svg_file_path'])) {
                unlink($qr_record['svg_file_path']);
            }
            
            // Delete database record
            $wpdb->delete(
                $this->table_name,
                array('redirect_id' => $redirect_id),
                array('%d')
            );
        }
        
        return true;
    }
    
    /**
     * Get QR codes for a specific redirect.
     * 
     * @param int $redirect_id The redirect ID.
     * @return array Array of QR code data including file paths and URLs.
     */
    public function get_qr_codes_for_redirect($redirect_id) {
        global $wpdb;
        
        $qr_record = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `" . esc_sql($this->table_name) . "` WHERE redirect_id = %d",
                $redirect_id
            ),
            ARRAY_A
        );
        
        if (!$qr_record) {
            return null;
        }
        
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'] . '/hoppr/qr-codes';
        
        return array(
            'png_url' => $qr_record['png_file_path'] ? $base_url . '/' . basename($qr_record['png_file_path']) : null,
            'svg_url' => $qr_record['svg_file_path'] ? $base_url . '/' . basename($qr_record['svg_file_path']) : null,
            'png_path' => $qr_record['png_file_path'],
            'svg_path' => $qr_record['svg_file_path'],
            'generated_date' => $qr_record['generated_date']
        );
    }
    
    private function generate_png_qr($redirect_id, $url) {
        $filename = "qr-{$redirect_id}.png";
        $filepath = $this->upload_dir . '/' . $filename;
        
        // Try QR Server API first (free alternative since Google Charts discontinued)
        if ($this->generate_qr_via_api($url, $filepath)) {
            return $filepath;
        }
        
        // Fallback to basic QR generation if Google API fails
        $qr_data = $this->generate_simple_qr_data($url);
        if ($this->create_qr_image($qr_data, $filepath, 'png')) {
            return $filepath;
        }
        
        return null;
    }
    
    private function generate_svg_qr($redirect_id, $url) {
        $filename = "qr-{$redirect_id}.svg";
        $filepath = $this->upload_dir . '/' . $filename;
        
        // Try to convert PNG to SVG (simple approach)
        // Generate PNG first, then create SVG wrapper
        $png_filename = "qr-{$redirect_id}.png";
        $png_filepath = $this->upload_dir . '/' . $png_filename;
        
        if ($this->generate_qr_via_api($url, $png_filepath)) {
            // Create SVG wrapper around PNG (embedded base64)
            if ($this->create_svg_from_png($png_filepath, $filepath)) {
                return $filepath;
            }
        }
        
        // Fallback to basic SVG generation
        $qr_data = $this->generate_simple_qr_data($url);
        if ($this->create_qr_svg($qr_data, $filepath)) {
            return $filepath;
        }
        
        return null;
    }
    
    private function generate_simple_qr_data($url) {
        // This is a simplified QR code data generation
        // In production, use a proper QR code library like endroid/qr-code
        
        // For now, we'll create a simple matrix pattern
        // This is just a placeholder - you should replace with actual QR generation
        $size = 25; // 25x25 matrix
        $matrix = array();
        
        // Initialize matrix
        for ($i = 0; $i < $size; $i++) {
            $matrix[$i] = array_fill(0, $size, 0);
        }
        
        // Add finder patterns (corners)
        $this->add_finder_pattern($matrix, 0, 0);
        $this->add_finder_pattern($matrix, $size - 7, 0);
        $this->add_finder_pattern($matrix, 0, $size - 7);
        
        // Add data based on URL (simplified)
        $hash = md5($url);
        for ($i = 0; $i < strlen($hash); $i++) {
            $x = ($i % ($size - 14)) + 7;
            $y = (intval($i / ($size - 14)) % ($size - 14)) + 7;
            $matrix[$y][$x] = hexdec($hash[$i]) % 2;
        }
        
        return $matrix;
    }
    
    private function generate_qr_via_api($url, $filepath, $size = 300) {
        
        // Use QR Server API (free alternative to Google Charts)
        $api_url = 'https://api.qrserver.com/v1/create-qr-code/';
        $params = array(
            'size' => $size . 'x' . $size,  // Image size
            'data' => $url,                  // Data to encode
            'format' => 'png',               // Output format
            'ecc' => 'M',                    // Error correction level
            'margin' => 10                   // Margin size
        );
        
        $query_string = http_build_query($params);
        $full_url = $api_url . '?' . $query_string;
        
        // Get the QR code image
        $response = wp_remote_get($full_url, array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'WordPress/Hoppr Plugin'
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            return false;
        }
        
        $image_data = wp_remote_retrieve_body($response);
        
        if (empty($image_data)) {
            return false;
        }
        
        // Save the image
        $result = file_put_contents($filepath, $image_data);
        return $result !== false;
    }
    
    private function create_svg_from_png($png_filepath, $svg_filepath) {
        if (!file_exists($png_filepath)) {
            return false;
        }
        
        // Read PNG and convert to base64
        $png_data = file_get_contents($png_filepath);
        $base64_data = base64_encode($png_data);
        
        // Create SVG with embedded PNG
        $svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $svg .= '<svg width="300" height="300" viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">' . "\n";
        $svg .= '<image x="0" y="0" width="300" height="300" xlink:href="data:image/png;base64,' . $base64_data . '"/>' . "\n";
        $svg .= '</svg>';
        
        return file_put_contents($svg_filepath, $svg) !== false;
    }
    
    private function add_finder_pattern(&$matrix, $x, $y) {
        // Add 7x7 finder pattern
        for ($i = 0; $i < 7; $i++) {
            for ($j = 0; $j < 7; $j++) {
                if ($i == 0 || $i == 6 || $j == 0 || $j == 6 || 
                    ($i >= 2 && $i <= 4 && $j >= 2 && $j <= 4)) {
                    $matrix[$y + $i][$x + $j] = 1;
                }
            }
        }
    }
    
    private function create_qr_image($matrix, $filepath, $format = 'png') {
        $size = count($matrix);
        $pixel_size = 8; // Each matrix cell = 8x8 pixels
        $img_size = $size * $pixel_size;
        $border = 32; // 4 modules border * 8 pixels
        $total_size = $img_size + ($border * 2);
        
        // Create image
        $image = imagecreate($total_size, $total_size);
        
        if (!$image) {
            return false;
        }
        
        // Define colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        // Fill background white
        imagefill($image, 0, 0, $white);
        
        // Draw QR code
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($matrix[$y][$x] == 1) {
                    $px = $border + ($x * $pixel_size);
                    $py = $border + ($y * $pixel_size);
                    imagefilledrectangle(
                        $image, 
                        $px, $py, 
                        $px + $pixel_size - 1, 
                        $py + $pixel_size - 1, 
                        $black
                    );
                }
            }
        }
        
        // Save image
        $success = false;
        if ($format === 'png') {
            $success = imagepng($image, $filepath);
        } elseif ($format === 'jpg') {
            $success = imagejpeg($image, $filepath, 90);
        }
        
        imagedestroy($image);
        return $success;
    }
    
    private function create_qr_svg($matrix, $filepath) {
        $size = count($matrix);
        $module_size = 4; // Each module = 4 units
        $border = 4; // 4 modules border
        $total_size = ($size + ($border * 2)) * $module_size;
        
        $svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $svg .= '<svg width="' . $total_size . '" height="' . $total_size . '" ';
        $svg .= 'viewBox="0 0 ' . $total_size . ' ' . $total_size . '" ';
        $svg .= 'xmlns="http://www.w3.org/2000/svg">' . "\n";
        
        // White background
        $svg .= '<rect width="' . $total_size . '" height="' . $total_size . '" fill="white"/>' . "\n";
        
        // Draw QR modules
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($matrix[$y][$x] == 1) {
                    $px = ($border + $x) * $module_size;
                    $py = ($border + $y) * $module_size;
                    $svg .= '<rect x="' . $px . '" y="' . $py . '" ';
                    $svg .= 'width="' . $module_size . '" height="' . $module_size . '" fill="black"/>' . "\n";
                }
            }
        }
        
        $svg .= '</svg>';
        
        return file_put_contents($filepath, $svg) !== false;
    }
    
    private function get_redirect_url($source_url) {
        // Return the full URL for the redirect - this is what the QR code should point to
        // Use home_url() to match how redirects work in the public class
        return home_url('/' . ltrim($source_url, '/'));
    }
    
    public function ajax_regenerate_qr() {
        check_ajax_referer('hoppr_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'hoppr'));
        }
        
        $redirect_id = intval($_POST['redirect_id']);
        
        if ($this->regenerate_qr_codes_for_redirect($redirect_id)) {
            $qr_codes = $this->get_qr_codes_for_redirect($redirect_id);
            wp_send_json_success(array(
                'message' => __('QR codes regenerated successfully', 'hoppr'),
                'qr_codes' => $qr_codes
            ));
        } else {
            wp_send_json_error(__('Failed to regenerate QR codes', 'hoppr'));
        }
    }
    
    public function ajax_download_qr() {
        check_ajax_referer('hoppr_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'hoppr'));
        }
        
        $redirect_id = intval($_GET['redirect_id']);
        $format = sanitize_text_field($_GET['format']);
        
        if (!in_array($format, array('png', 'svg'))) {
            wp_die(__('Invalid format', 'hoppr'));
        }
        
        $qr_codes = $this->get_qr_codes_for_redirect($redirect_id);
        
        if (!$qr_codes) {
            wp_die(__('QR codes not found', 'hoppr'));
        }
        
        $file_path = $format === 'png' ? $qr_codes['png_path'] : $qr_codes['svg_path'];
        
        if (!$file_path || !file_exists($file_path)) {
            wp_die(__('QR code file not found', 'hoppr'));
        }
        
        // Set headers for download
        $filename = 'hoppr-qr-' . $redirect_id . '.' . $format;
        
        header('Content-Type: ' . ($format === 'png' ? 'image/png' : 'image/svg+xml'));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($file_path);
        exit;
    }
    
    public function get_qr_code_preview_html($redirect_id, $size = 150) {
        $qr_codes = $this->get_qr_codes_for_redirect($redirect_id);
        
        if (!$qr_codes || !$qr_codes['png_url']) {
            return '<div class="hoppr-qr-placeholder">' . __('QR code not available', 'hoppr') . '</div>';
        }
        
        $html = '<div class="hoppr-qr-preview">';
        $html .= '<img src="' . esc_url($qr_codes['png_url']) . '" ';
        $html .= 'alt="' . __('QR Code', 'hoppr') . '" ';
        $html .= 'width="' . intval($size) . '" height="' . intval($size) . '" />';
        $html .= '<div class="hoppr-qr-actions">';
        $html .= '<a href="' . esc_url($qr_codes['png_url']) . '" download class="button button-small">' . __('Download PNG', 'hoppr') . '</a> ';
        $html .= '<a href="' . esc_url($qr_codes['svg_url']) . '" download class="button button-small">' . __('Download SVG', 'hoppr') . '</a>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    public function bulk_generate_missing_qr_codes() {
        global $wpdb;
        
        // Find redirects without QR codes
        $redirects_table = HOPPR_TABLE_REDIRECTS;
        $query = "
            SELECT r.id 
            FROM `" . esc_sql($redirects_table) . "` r 
            LEFT JOIN `" . esc_sql($this->table_name) . "` q ON r.id = q.redirect_id 
            WHERE q.redirect_id IS NULL AND r.status = 'active'
        ";
        
        $missing_redirects = $wpdb->get_col($query);
        $generated_count = 0;
        
        foreach ($missing_redirects as $redirect_id) {
            if ($this->generate_qr_codes_for_redirect($redirect_id)) {
                $generated_count++;
            }
        }
        
        return $generated_count;
    }
}