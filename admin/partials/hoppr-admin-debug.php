<?php

if (!defined('ABSPATH')) {
    exit;
}

// Get hoppr instance
$hoppr = hoppr_init();
$redirects = $hoppr->get_redirects();
$analytics = $hoppr->get_analytics();

global $wpdb;

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Hoppr Debug', 'hoppr'); ?></h1>
    <hr class="wp-header-end">

    <div class="hoppr-debug-content">
        
        <!-- System Information -->
        <div class="hoppr-widget">
            <div class="hoppr-widget-header">
                <h2><?php _e('System Information', 'hoppr'); ?></h2>
            </div>
            <div class="hoppr-widget-content">
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Setting', 'hoppr'); ?></th>
                            <th><?php _e('Value', 'hoppr'); ?></th>
                            <th><?php _e('Status', 'hoppr'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $debug_checks = array(
                            'WordPress Version' => get_bloginfo('version'),
                            'PHP Version' => PHP_VERSION,
                            'WP_DEBUG' => defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled',
                            'DISABLE_WP_CRON' => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON ? 'Yes (Cron Disabled)' : 'No',
                            'WordPress Cron' => wp_next_scheduled('wp_cron') ? 'Working' : 'Not Scheduled',
                            'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                            'Memory Limit' => ini_get('memory_limit'),
                            'Max Execution Time' => ini_get('max_execution_time') . 's'
                        );
                        
                        foreach ($debug_checks as $label => $value):
                            $status = 'good';
                            if ($label === 'WP_DEBUG' && $value === 'Disabled') $status = 'warning';
                            if ($label === 'DISABLE_WP_CRON' && strpos($value, 'Yes') !== false) $status = 'error';
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($label); ?></strong></td>
                                <td><?php echo esc_html($value); ?></td>
                                <td>
                                    <span class="hoppr-status-<?php echo esc_attr($status); ?>">
                                        <?php 
                                        if ($status === 'good') echo esc_html('✓ Good');
                                        elseif ($status === 'warning') echo esc_html('⚠ Warning');
                                        else echo esc_html('✗ Issue');
                                        ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Database Tables Check -->
        <div class="hoppr-widget">
            <div class="hoppr-widget-header">
                <h2><?php _e('Database Tables', 'hoppr'); ?></h2>
            </div>
            <div class="hoppr-widget-content">
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Table', 'hoppr'); ?></th>
                            <th><?php _e('Status', 'hoppr'); ?></th>
                            <th><?php _e('Row Count', 'hoppr'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tables = array(
                            'Redirects' => HOPPR_TABLE_REDIRECTS,
                            'Analytics' => HOPPR_TABLE_ANALYTICS,
                            'QR Codes' => HOPPR_TABLE_QR_CODES
                        );
                        
                        foreach ($tables as $name => $table):
                            $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table;
                            $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM `" . esc_sql($table) . "`") : 0;
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($name); ?></strong><br>
                                    <code><?php echo esc_html($table); ?></code></td>
                                <td>
                                    <span class="hoppr-status-<?php echo esc_attr($exists ? 'active' : 'inactive'); ?>">
                                        <?php echo esc_html($exists ? '✓ Exists' : '✗ Missing'); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($count); ?> records</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Analytics Data -->
        <div class="hoppr-widget">
            <div class="hoppr-widget-header">
                <h2><?php _e('Recent Analytics Data', 'hoppr'); ?></h2>
            </div>
            <div class="hoppr-widget-content">
                <?php
                $recent_analytics = $wpdb->get_results(
                    "SELECT a.*, r.source_url 
                     FROM `" . esc_sql(HOPPR_TABLE_ANALYTICS) . "` a 
                     LEFT JOIN `" . esc_sql(HOPPR_TABLE_REDIRECTS) . "` r ON a.redirect_id = r.id 
                     ORDER BY a.click_timestamp DESC 
                     LIMIT 10",
                    ARRAY_A
                );
                
                if (!empty($recent_analytics)): ?>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Timestamp', 'hoppr'); ?></th>
                                <th><?php _e('Source URL', 'hoppr'); ?></th>
                                <th><?php _e('Device', 'hoppr'); ?></th>
                                <th><?php _e('Country', 'hoppr'); ?></th>
                                <th><?php _e('Referrer', 'hoppr'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_analytics as $record): ?>
                                <tr>
                                    <td><?php echo esc_html($record['click_timestamp']); ?></td>
                                    <td><strong><?php echo esc_html($record['source_url']); ?></strong></td>
                                    <td><?php echo esc_html($record['device_type']); ?></td>
                                    <td><?php echo esc_html($record['country_code'] ?: 'Unknown'); ?></td>
                                    <td><?php echo esc_html(wp_trim_words($record['referrer'], 6, '...')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="hoppr-empty-state">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <h3><?php _e('No Analytics Data Found', 'hoppr'); ?></h3>
                        <p><?php _e('This could indicate that redirects are not being tracked properly.', 'hoppr'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Active Redirects -->
        <div class="hoppr-widget">
            <div class="hoppr-widget-header">
                <h2><?php _e('Active Redirects', 'hoppr'); ?></h2>
            </div>
            <div class="hoppr-widget-content">
                <?php
                $active_redirects = $redirects->get_redirects(array('status' => 'active', 'limit' => 0));
                
                if (!empty($active_redirects)): ?>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Source URL', 'hoppr'); ?></th>
                                <th><?php _e('Destination', 'hoppr'); ?></th>
                                <th><?php _e('Type', 'hoppr'); ?></th>
                                <th><?php _e('Test Link', 'hoppr'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_redirects as $redirect): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($redirect['source_url']); ?></strong></td>
                                    <td><?php echo esc_html(wp_trim_words($redirect['destination_url'], 6, '...')); ?></td>
                                    <td><?php echo esc_html($redirect['redirect_type']); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url(home_url('/' . $redirect['source_url'])); ?>" 
                                           target="_blank" class="button button-small">
                                            <?php _e('Test Redirect', 'hoppr'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="hoppr-empty-state">
                        <span class="dashicons dashicons-randomize"></span>
                        <h3><?php _e('No Active Redirects', 'hoppr'); ?></h3>
                        <p><?php _e('Create some redirects to test analytics tracking.', 'hoppr'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Debug Actions -->
        <div class="hoppr-widget">
            <div class="hoppr-widget-header">
                <h2><?php _e('Debug Actions', 'hoppr'); ?></h2>
            </div>
            <div class="hoppr-widget-content">
                <div class="hoppr-debug-actions">
                    <button type="button" class="button button-secondary" onclick="hopprTestAnalytics()">
                        <?php _e('Test Analytics Tracking', 'hoppr'); ?>
                    </button>
                    <button type="button" class="button button-secondary" onclick="hopprClearLogs()">
                        <?php _e('Clear Error Logs', 'hoppr'); ?>
                    </button>
                    <button type="button" class="button button-secondary" onclick="hopprRecreateTables()">
                        <?php _e('Recreate Tables', 'hoppr'); ?>
                    </button>
                </div>
                
                <div id="hoppr-debug-output" style="margin-top: 15px; display: none;">
                    <textarea readonly style="width: 100%; height: 200px; font-family: monospace;"></textarea>
                </div>
            </div>
        </div>

        <!-- WordPress Cron Status -->
        <div class="hoppr-widget">
            <div class="hoppr-widget-header">
                <h2><?php _e('WordPress Cron Status', 'hoppr'); ?></h2>
            </div>
            <div class="hoppr-widget-content">
                <?php
                $cron_jobs = _get_cron_array();
                $hoppr_jobs = array();
                
                foreach ($cron_jobs as $timestamp => $cron) {
                    foreach ($cron as $hook => $jobs) {
                        if (strpos($hook, 'hoppr_') === 0) {
                            foreach ($jobs as $key => $job) {
                                $hoppr_jobs[] = array(
                                    'hook' => $hook,
                                    'timestamp' => $timestamp,
                                    'args' => $job['args'],
                                    'interval' => $job['interval'] ?? 'single'
                                );
                            }
                        }
                    }
                }
                ?>
                
                <?php if (!empty($hoppr_jobs)): ?>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Hook', 'hoppr'); ?></th>
                                <th><?php _e('Next Run', 'hoppr'); ?></th>
                                <th><?php _e('Interval', 'hoppr'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hoppr_jobs as $job): ?>
                                <tr>
                                    <td><code><?php echo esc_html($job['hook']); ?></code></td>
                                    <td><?php echo esc_html(date('Y-m-d H:i:s', $job['timestamp'])); ?></td>
                                    <td><?php echo esc_html($job['interval']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e('No Hoppr cron jobs found.', 'hoppr'); ?></p>
                <?php endif; ?>
                
                <p><strong><?php _e('WordPress Cron Status:', 'hoppr'); ?></strong> 
                    <?php if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON): ?>
                        <span class="hoppr-status-inactive">❌ Disabled</span>
                        <br><em><?php _e('WordPress cron is disabled. This may prevent analytics from being tracked properly.', 'hoppr'); ?></em>
                    <?php else: ?>
                        <span class="hoppr-status-active">✅ Enabled</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    window.hopprTestAnalytics = function() {
        $('#hoppr-debug-output').show();
        const $textarea = $('#hoppr-debug-output textarea');
        $textarea.val('Testing analytics tracking...\n');
        
        // Test analytics tracking
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'hoppr_test_analytics',
                nonce: '<?php echo esc_js(wp_create_nonce('hoppr_nonce')); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $textarea.val($textarea.val() + 'Success: ' + response.data + '\n');
                } else {
                    $textarea.val($textarea.val() + 'Error: ' + (response.data || 'Unknown error') + '\n');
                }
            },
            error: function(xhr, status, error) {
                $textarea.val($textarea.val() + 'AJAX Error: ' + error + '\n');
            }
        });
    };
    
    window.hopprClearLogs = function() {
        if (confirm('Are you sure you want to clear error logs?')) {
            $('#hoppr-debug-output').show();
            $('#hoppr-debug-output textarea').val('Clearing logs...\n');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hoppr_clear_logs',
                    nonce: '<?php echo esc_js(wp_create_nonce('hoppr_nonce')); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#hoppr-debug-output textarea').val('Logs cleared successfully.\n');
                    } else {
                        $('#hoppr-debug-output textarea').val('Error clearing logs: ' + response.data + '\n');
                    }
                }
            });
        }
    };
    
    window.hopprRecreateTables = function() {
        if (confirm('Are you sure you want to recreate database tables? This will delete existing data!')) {
            $('#hoppr-debug-output').show();
            $('#hoppr-debug-output textarea').val('Recreating tables...\n');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hoppr_recreate_tables',
                    nonce: '<?php echo esc_js(wp_create_nonce('hoppr_nonce')); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#hoppr-debug-output textarea').val('Tables recreated successfully.\n');
                        window.location.reload();
                    } else {
                        $('#hoppr-debug-output textarea').val('Error recreating tables: ' + response.data + '\n');
                    }
                }
            });
        }
    };
});
</script>

<style>
.hoppr-debug-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.hoppr-status-good { color: #00a32a; font-weight: bold; }
.hoppr-status-warning { color: #dba617; font-weight: bold; }
.hoppr-status-error { color: #d63638; font-weight: bold; }
</style>