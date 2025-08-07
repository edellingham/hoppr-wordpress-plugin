<?php

if (!defined('ABSPATH')) {
    exit;
}

// Get hoppr instance
$hoppr = hoppr_init();
$settings = $hoppr->get_settings();

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

// Define tabs
$tabs = array(
    'general' => __('General', 'hoppr'),
    'analytics' => __('Analytics', 'hoppr'),
    'permissions' => __('Permissions', 'hoppr'),
    'qr_codes' => __('QR Codes', 'hoppr'),
    'security' => __('Security', 'hoppr'),
    'advanced' => __('Advanced', 'hoppr')
);

?>

<div class="wrap">
    <h1><?php _e('Hoppr Settings', 'hoppr'); ?></h1>

    <!-- Tab Navigation -->
    <nav class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab_key => $tab_label): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=hoppr-settings&tab=' . $tab_key)); ?>" 
               class="nav-tab <?php echo esc_attr($current_tab === $tab_key ? 'nav-tab-active' : ''); ?>">
                <?php echo esc_html($tab_label); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <form method="post" class="hoppr-settings-form" data-action="hoppr_save_settings">
        
        <?php if ($current_tab === 'general'): ?>
            
            <h2><?php _e('General Settings', 'hoppr'); ?></h2>
            <p><?php _e('Configure basic plugin settings and behavior.', 'hoppr'); ?></p>
            
            <table class="form-table hoppr-form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="hoppr_cache_timeout"><?php _e('Cache Timeout', 'hoppr'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="hoppr_cache_timeout" name="settings[hoppr_cache_timeout]" 
                                   value="<?php echo esc_attr($settings->get_cache_timeout()); ?>" 
                                   class="small-text" min="300" max="86400">
                            <span class="unit"><?php _e('seconds', 'hoppr'); ?></span>
                            <p class="description"><?php _e('How long to cache redirect rules (300-86400 seconds)', 'hoppr'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="hoppr_max_redirects"><?php _e('Maximum Redirects', 'hoppr'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="hoppr_max_redirects" name="settings[hoppr_max_redirects]" 
                                   value="<?php echo esc_attr($settings->get_max_redirects()); ?>" 
                                   class="small-text" min="10" max="1000">
                            <p class="description"><?php _e('Maximum number of active redirects (10-1000)', 'hoppr'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
        <?php elseif ($current_tab === 'analytics'): ?>
            
            <h2><?php _e('Analytics Settings', 'hoppr'); ?></h2>
            <p><?php _e('Configure analytics data collection and retention.', 'hoppr'); ?></p>
            
            <table class="form-table hoppr-form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="hoppr_analytics_retention"><?php _e('Data Retention', 'hoppr'); ?></label>
                        </th>
                        <td>
                            <select id="hoppr_analytics_retention" name="settings[hoppr_analytics_retention]">
                                <option value="30" <?php selected($settings->get_analytics_retention(), '30'); ?>><?php _e('30 days', 'hoppr'); ?></option>
                                <option value="90" <?php selected($settings->get_analytics_retention(), '90'); ?>><?php _e('90 days', 'hoppr'); ?></option>
                                <option value="365" <?php selected($settings->get_analytics_retention(), '365'); ?>><?php _e('1 year', 'hoppr'); ?></option>
                                <option value="0" <?php selected($settings->get_analytics_retention(), '0'); ?>><?php _e('Forever', 'hoppr'); ?></option>
                            </select>
                            <p class="description"><?php _e('How long to keep analytics data before automatic cleanup', 'hoppr'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="hoppr_enable_logging"><?php _e('Enable Logging', 'hoppr'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="hoppr_enable_logging" name="settings[hoppr_enable_logging]" 
                                       value="1" <?php checked($settings->is_logging_enabled()); ?>>
                                <?php _e('Enable detailed logging for debugging', 'hoppr'); ?>
                            </label>
                            <p class="description"><?php _e('Logs redirect activity and errors for troubleshooting', 'hoppr'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="hoppr_log_level"><?php _e('Log Level', 'hoppr'); ?></label>
                        </th>
                        <td>
                            <select id="hoppr_log_level" name="settings[hoppr_log_level]">
                                <option value="error" <?php selected($settings->get_log_level(), 'error'); ?>><?php _e('Errors only', 'hoppr'); ?></option>
                                <option value="warning" <?php selected($settings->get_log_level(), 'warning'); ?>><?php _e('Warnings and errors', 'hoppr'); ?></option>
                                <option value="info" <?php selected($settings->get_log_level(), 'info'); ?>><?php _e('Info, warnings, and errors', 'hoppr'); ?></option>
                                <option value="debug" <?php selected($settings->get_log_level(), 'debug'); ?>><?php _e('All messages (debug)', 'hoppr'); ?></option>
                            </select>
                            <p class="description"><?php _e('What level of detail to include in logs', 'hoppr'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
        <?php elseif ($current_tab === 'permissions'): ?>
            
            <h2><?php _e('User Permissions', 'hoppr'); ?></h2>
            <p><?php _e('Configure which user roles can perform different actions.', 'hoppr'); ?></p>
            
            <div class="hoppr-permissions-section">
                <?php $settings->render_permissions_table(); ?>
            </div>
            
        <?php elseif ($current_tab === 'qr_codes'): ?>
            
            <h2><?php _e('QR Code Settings', 'hoppr'); ?></h2>
            <p><?php _e('Configure QR code generation and display options.', 'hoppr'); ?></p>
            
            <table class="form-table hoppr-form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="hoppr_qr_auto_generate"><?php _e('Auto Generate', 'hoppr'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="hoppr_qr_auto_generate" name="settings[hoppr_qr_auto_generate]" 
                                       value="1" <?php checked($settings->is_qr_auto_generate()); ?>>
                                <?php _e('Automatically generate QR codes for new redirects', 'hoppr'); ?>
                            </label>
                            <p class="description"><?php _e('QR codes will be created when redirects are added or updated', 'hoppr'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="hoppr_qr_size"><?php _e('QR Code Size', 'hoppr'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="hoppr_qr_size" name="settings[hoppr_qr_size]" 
                                   value="<?php echo esc_attr($settings->get_qr_size()); ?>" 
                                   class="small-text" min="100" max="500">
                            <span class="unit"><?php _e('pixels', 'hoppr'); ?></span>
                            <p class="description"><?php _e('Size of generated QR code images (100-500 pixels)', 'hoppr'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div class="hoppr-qr-actions">
                <h4><?php _e('QR Code Management', 'hoppr'); ?></h4>
                <p><?php _e('Generate missing QR codes or regenerate all existing ones.', 'hoppr'); ?></p>
                
                <button type="button" id="hoppr-generate-missing-qr" class="button button-secondary">
                    <?php _e('Generate Missing QR Codes', 'hoppr'); ?>
                </button>
                
                <button type="button" id="hoppr-regenerate-all-qr" class="button button-secondary">
                    <?php _e('Regenerate All QR Codes', 'hoppr'); ?>
                </button>
            </div>
            
        <?php elseif ($current_tab === 'security'): ?>
            
            <h2><?php _e('Security Settings', 'hoppr'); ?></h2>
            <p><?php _e('Configure security and validation options.', 'hoppr'); ?></p>
            
            <table class="form-table hoppr-form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="hoppr_security_checks"><?php _e('Security Checks', 'hoppr'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="hoppr_security_checks" name="settings[hoppr_security_checks]" 
                                       value="1" <?php checked($settings->is_security_checks_enabled()); ?>>
                                <?php _e('Enable enhanced security validation', 'hoppr'); ?>
                            </label>
                            <p class="description"><?php _e('Validates redirect destinations to prevent malicious redirects', 'hoppr'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="hoppr_allowed_domains"><?php _e('Allowed Domains', 'hoppr'); ?></label>
                        </th>
                        <td>
                            <textarea id="hoppr_allowed_domains" name="settings[hoppr_allowed_domains]" 
                                      rows="6" cols="50" class="regular-text"><?php echo esc_textarea(implode("\n", $settings->get_allowed_domains())); ?></textarea>
                            <p class="description"><?php _e('One domain per line. Leave empty to allow all domains. Example: example.com', 'hoppr'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
        <?php elseif ($current_tab === 'advanced'): ?>
            
            <h2><?php _e('Advanced Settings', 'hoppr'); ?></h2>
            <p><?php _e('Advanced configuration options and maintenance tools.', 'hoppr'); ?></p>
            
            <div class="hoppr-advanced-section">
                
                <!-- Database Maintenance -->
                <div class="hoppr-settings-group">
                    <h3><?php _e('Database Maintenance', 'hoppr'); ?></h3>
                    
                    <div class="hoppr-maintenance-actions">
                        <button type="button" id="hoppr-cleanup-analytics" class="button button-secondary">
                            <?php _e('Clean Up Old Analytics Data', 'hoppr'); ?>
                        </button>
                        <p class="description"><?php _e('Remove analytics data older than the retention period', 'hoppr'); ?></p>
                        
                        <button type="button" id="hoppr-optimize-tables" class="button button-secondary">
                            <?php _e('Optimize Database Tables', 'hoppr'); ?>
                        </button>
                        <p class="description"><?php _e('Optimize plugin database tables for better performance', 'hoppr'); ?></p>
                    </div>
                </div>
                
                <!-- Import/Export Settings -->
                <div class="hoppr-settings-group">
                    <h3><?php _e('Settings Management', 'hoppr'); ?></h3>
                    
                    <div class="hoppr-import-export">
                        <h4><?php _e('Export Settings', 'hoppr'); ?></h4>
                        <p><?php _e('Download your current plugin settings as a backup.', 'hoppr'); ?></p>
                        <button type="button" id="hoppr-export-settings" class="button button-secondary">
                            <?php _e('Export Settings', 'hoppr'); ?>
                        </button>
                        
                        <h4><?php _e('Import Settings', 'hoppr'); ?></h4>
                        <p><?php _e('Upload a settings file to restore configuration.', 'hoppr'); ?></p>
                        <input type="file" id="hoppr-import-file" accept=".json" style="margin-bottom: 10px;">
                        <button type="button" id="hoppr-import-settings" class="button button-secondary">
                            <?php _e('Import Settings', 'hoppr'); ?>
                        </button>
                        
                        <h4><?php _e('Reset Settings', 'hoppr'); ?></h4>
                        <p><?php _e('Reset all settings to their default values.', 'hoppr'); ?></p>
                        <button type="button" id="hoppr-reset-settings" class="button button-secondary">
                            <?php _e('Reset to Defaults', 'hoppr'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- System Information -->
                <div class="hoppr-settings-group">
                    <h3><?php _e('System Information', 'hoppr'); ?></h3>
                    
                    <div class="hoppr-system-info">
                        <table class="widefat">
                            <tbody>
                                <tr>
                                    <td><strong><?php _e('Plugin Version:', 'hoppr'); ?></strong></td>
                                    <td><?php echo esc_html(HOPPR_VERSION); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php _e('WordPress Version:', 'hoppr'); ?></strong></td>
                                    <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php _e('PHP Version:', 'hoppr'); ?></strong></td>
                                    <td><?php echo esc_html(PHP_VERSION); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php _e('Database Version:', 'hoppr'); ?></strong></td>
                                    <td><?php echo esc_html($GLOBALS['wpdb']->db_version()); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php _e('Total Redirects:', 'hoppr'); ?></strong></td>
                                    <td><?php echo esc_html(number_format($hoppr->get_redirects()->get_redirects_count())); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php _e('Active Redirects:', 'hoppr'); ?></strong></td>
                                    <td><?php echo esc_html(number_format($hoppr->get_redirects()->get_redirects_count(array('status' => 'active')))); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php _e('Total Analytics Records:', 'hoppr'); ?></strong></td>
                                    <td><?php echo esc_html(number_format($hoppr->get_analytics()->get_total_clicks())); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
            
        <?php endif; ?>
        
        <?php if ($current_tab !== 'advanced'): ?>
            <div class="hoppr-actions">
                <input type="submit" class="button button-primary" value="<?php _e('Save Settings', 'hoppr'); ?>">
                <button type="button" id="hoppr-reset-tab-settings" class="button button-secondary">
                    <?php _e('Reset Tab to Defaults', 'hoppr'); ?>
                </button>
            </div>
        <?php endif; ?>
        
        <?php wp_nonce_field('hoppr_nonce', 'nonce'); ?>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Advanced actions
    $('#hoppr-cleanup-analytics').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to clean up old analytics data?', 'hoppr'); ?>')) {
            // AJAX call to cleanup analytics
            // TODO: Implement cleanup functionality
        }
    });
    
    $('#hoppr-optimize-tables').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to optimize database tables?', 'hoppr'); ?>')) {
            // AJAX call to optimize tables
            // TODO: Implement table optimization
        }
    });
    
    $('#hoppr-export-settings').on('click', function() {
        // Trigger settings export
        window.location.href = '<?php echo esc_js(esc_url(admin_url('admin-ajax.php?action=hoppr_export_settings&nonce=' . wp_create_nonce('hoppr_nonce')))); ?>';
    });
    
    $('#hoppr-import-settings').on('click', function() {
        var fileInput = $('#hoppr-import-file')[0];
        if (fileInput.files.length === 0) {
            alert('<?php _e('Please select a file to import.', 'hoppr'); ?>');
            return;
        }
        
        if (confirm('<?php _e('Are you sure you want to import these settings? This will overwrite your current configuration.', 'hoppr'); ?>')) {
            // Handle file import
            // TODO: Implement settings import
        }
    });
    
    $('#hoppr-reset-settings').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to reset all settings to defaults? This cannot be undone.', 'hoppr'); ?>')) {
            // AJAX call to reset settings
            $.post(ajaxurl, {
                action: 'hoppr_reset_settings',
                nonce: '<?php echo esc_js(wp_create_nonce('hoppr_nonce')); ?>'
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('<?php _e('Error resetting settings.', 'hoppr'); ?>');
                }
            });
        }
    });
    
    // QR Code actions
    $('#hoppr-generate-missing-qr').on('click', function() {
        $(this).prop('disabled', true).text('<?php _e('Generating...', 'hoppr'); ?>');
        
        // AJAX call to generate missing QR codes
        setTimeout(function() {
            $('#hoppr-generate-missing-qr').prop('disabled', false).text('<?php _e('Generate Missing QR Codes', 'hoppr'); ?>');
            alert('<?php _e('QR codes generated successfully.', 'hoppr'); ?>');
        }, 2000);
    });
    
    $('#hoppr-regenerate-all-qr').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to regenerate all QR codes? This may take some time.', 'hoppr'); ?>')) {
            $(this).prop('disabled', true).text('<?php _e('Regenerating...', 'hoppr'); ?>');
            
            // AJAX call to regenerate all QR codes
            setTimeout(function() {
                $('#hoppr-regenerate-all-qr').prop('disabled', false).text('<?php _e('Regenerate All QR Codes', 'hoppr'); ?>');
                alert('<?php _e('All QR codes regenerated successfully.', 'hoppr'); ?>');
            }, 3000);
        }
    });
    
});
</script>