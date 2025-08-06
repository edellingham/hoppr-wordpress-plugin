<?php
/**
 * Temporary database setup script for Hoppr plugin
 * Run this once to manually create the database tables
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Allow execution if called directly for setup
    require_once('../../../wp-config.php');
}

global $wpdb;

echo "<h2>Hoppr Database Setup</h2>";

// Define table names
$redirects_table = $wpdb->prefix . 'hoppr_redirects';
$analytics_table = $wpdb->prefix . 'hoppr_analytics';
$qr_codes_table = $wpdb->prefix . 'hoppr_qr_codes';

echo "<p>Creating tables...</p>";

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

$result1 = $wpdb->query($redirects_sql);
if ($result1 !== false) {
    echo "<p>✅ Redirects table created successfully</p>";
} else {
    echo "<p>❌ Failed to create redirects table: " . $wpdb->last_error . "</p>";
}

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

$result2 = $wpdb->query($analytics_sql);
if ($result2 !== false) {
    echo "<p>✅ Analytics table created successfully</p>";
} else {
    echo "<p>❌ Failed to create analytics table: " . $wpdb->last_error . "</p>";
}

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

$result3 = $wpdb->query($qr_codes_sql);
if ($result3 !== false) {
    echo "<p>✅ QR codes table created successfully</p>";
} else {
    echo "<p>❌ Failed to create QR codes table: " . $wpdb->last_error . "</p>";
}

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
    
    echo "<p>✅ QR codes directory created</p>";
} else {
    echo "<p>✅ QR codes directory already exists</p>";
}

// Set default options
add_option('hoppr_version', '1.0.0');
add_option('hoppr_analytics_retention', '365');
add_option('hoppr_permissions', array(
    'administrator' => array('view', 'create', 'edit', 'delete', 'analytics', 'settings')
));

echo "<p>✅ Default options set</p>";

// Check if tables exist now
$tables_exist = true;
$tables = array($redirects_table, $analytics_table, $qr_codes_table);

foreach ($tables as $table) {
    $result = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($result != $table) {
        $tables_exist = false;
        echo "<p>❌ Table $table still doesn't exist</p>";
    } else {
        echo "<p>✅ Table $table exists</p>";
    }
}

if ($tables_exist) {
    echo "<h3>🎉 Database setup completed successfully!</h3>";
    echo "<p>You can now use the Hoppr plugin normally.</p>";
    echo "<p><strong>Important:</strong> Delete this setup file for security.</p>";
} else {
    echo "<h3>❌ Database setup failed</h3>";
    echo "<p>Please check your database permissions and try again.</p>";
}

// Show current database info
echo "<hr>";
echo "<h4>Database Information:</h4>";
echo "<p>Database: " . DB_NAME . "</p>";
echo "<p>User: " . DB_USER . "</p>";
echo "<p>Host: " . DB_HOST . "</p>";
echo "<p>Prefix: " . $wpdb->prefix . "</p>";
?>