<?php

if (!defined('ABSPATH')) {
    exit;
}

$stats = $this->get_dashboard_stats();
$recent_redirects = $this->get_recent_redirects();
$top_redirects = $this->get_top_redirects();

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Hoppr Dashboard', 'hoppr'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=hoppr-redirects&action=add'); ?>" class="page-title-action"><?php _e('Add New Redirect', 'hoppr'); ?></a>
    <hr class="wp-header-end">

    <div class="hoppr-dashboard">
        <!-- Stats Cards -->
        <div class="hoppr-stats-grid">
            <div class="hoppr-stat-card">
                <div class="hoppr-stat-icon">
                    <span class="dashicons dashicons-randomize"></span>
                </div>
                <div class="hoppr-stat-content">
                    <h3><?php echo number_format($stats['total_redirects']); ?></h3>
                    <p><?php _e('Total Redirects', 'hoppr'); ?></p>
                </div>
            </div>

            <div class="hoppr-stat-card active">
                <div class="hoppr-stat-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="hoppr-stat-content">
                    <h3><?php echo number_format($stats['active_redirects']); ?></h3>
                    <p><?php _e('Active Redirects', 'hoppr'); ?></p>
                </div>
            </div>

            <div class="hoppr-stat-card">
                <div class="hoppr-stat-icon">
                    <span class="dashicons dashicons-chart-bar"></span>
                </div>
                <div class="hoppr-stat-content">
                    <h3><?php echo number_format($stats['total_clicks']); ?></h3>
                    <p><?php _e('Total Clicks', 'hoppr'); ?></p>
                </div>
            </div>

            <div class="hoppr-stat-card">
                <div class="hoppr-stat-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="hoppr-stat-content">
                    <h3><?php echo number_format($stats['clicks_today']); ?></h3>
                    <p><?php _e('Clicks Today', 'hoppr'); ?></p>
                </div>
            </div>
        </div>

        <div class="hoppr-dashboard-content">
            <div class="hoppr-dashboard-left">
                <!-- Recent Redirects -->
                <div class="hoppr-widget">
                    <div class="hoppr-widget-header">
                        <h2><?php _e('Recent Redirects', 'hoppr'); ?></h2>
                        <a href="<?php echo admin_url('admin.php?page=hoppr-redirects'); ?>" class="button button-secondary"><?php _e('View All', 'hoppr'); ?></a>
                    </div>
                    <div class="hoppr-widget-content">
                        <?php if (!empty($recent_redirects)) : ?>
                            <table class="widefat">
                                <thead>
                                    <tr>
                                        <th><?php _e('Source URL', 'hoppr'); ?></th>
                                        <th><?php _e('Destination', 'hoppr'); ?></th>
                                        <th><?php _e('Type', 'hoppr'); ?></th>
                                        <th><?php _e('Status', 'hoppr'); ?></th>
                                        <th><?php _e('Created', 'hoppr'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_redirects as $redirect) : ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html($redirect['source_url']); ?></strong>
                                            </td>
                                            <td>
                                                <a href="<?php echo esc_url($redirect['destination_url']); ?>" target="_blank" rel="noopener">
                                                    <?php echo esc_html(wp_trim_words($redirect['destination_url'], 8, '...')); ?>
                                                    <span class="dashicons dashicons-external"></span>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="hoppr-redirect-type hoppr-type-<?php echo esc_attr($redirect['redirect_type']); ?>">
                                                    <?php echo esc_html($redirect['redirect_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="hoppr-status hoppr-status-<?php echo esc_attr($redirect['status']); ?>">
                                                    <?php echo esc_html(ucfirst($redirect['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($redirect['created_date']))); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else : ?>
                            <div class="hoppr-empty-state">
                                <span class="dashicons dashicons-randomize"></span>
                                <h3><?php _e('No redirects yet', 'hoppr'); ?></h3>
                                <p><?php _e('Create your first redirect to get started.', 'hoppr'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=hoppr-redirects&action=add'); ?>" class="button button-primary">
                                    <?php _e('Add New Redirect', 'hoppr'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="hoppr-dashboard-right">
                <!-- Top Performing Redirects -->
                <div class="hoppr-widget">
                    <div class="hoppr-widget-header">
                        <h2><?php _e('Top Performing (30 days)', 'hoppr'); ?></h2>
                        <a href="<?php echo admin_url('admin.php?page=hoppr-analytics'); ?>" class="button button-secondary"><?php _e('View Analytics', 'hoppr'); ?></a>
                    </div>
                    <div class="hoppr-widget-content">
                        <?php if (!empty($top_redirects)) : ?>
                            <div class="hoppr-top-redirects">
                                <?php foreach ($top_redirects as $redirect) : ?>
                                    <div class="hoppr-top-redirect-item">
                                        <div class="hoppr-redirect-info">
                                            <strong><?php echo esc_html(wp_trim_words($redirect['source_url'], 5, '...')); ?></strong>
                                            <span class="hoppr-redirect-destination">
                                                → <?php echo esc_html(wp_trim_words($redirect['destination_url'], 5, '...')); ?>
                                            </span>
                                        </div>
                                        <div class="hoppr-click-count">
                                            <span class="hoppr-clicks"><?php echo number_format($redirect['click_count']); ?></span>
                                            <small><?php _e('clicks', 'hoppr'); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <div class="hoppr-empty-state">
                                <span class="dashicons dashicons-chart-bar"></span>
                                <p><?php _e('No analytics data available yet.', 'hoppr'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="hoppr-widget">
                    <div class="hoppr-widget-header">
                        <h2><?php _e('Quick Actions', 'hoppr'); ?></h2>
                    </div>
                    <div class="hoppr-widget-content">
                        <div class="hoppr-quick-actions">
                            <a href="<?php echo admin_url('admin.php?page=hoppr-redirects&action=add'); ?>" class="hoppr-quick-action">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                <?php _e('Add New Redirect', 'hoppr'); ?>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=hoppr-redirects&action=import'); ?>" class="hoppr-quick-action">
                                <span class="dashicons dashicons-upload"></span>
                                <?php _e('Import Redirects', 'hoppr'); ?>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=hoppr-redirects&action=export'); ?>" class="hoppr-quick-action">
                                <span class="dashicons dashicons-download"></span>
                                <?php _e('Export Redirects', 'hoppr'); ?>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=hoppr-settings'); ?>" class="hoppr-quick-action">
                                <span class="dashicons dashicons-admin-settings"></span>
                                <?php _e('Settings', 'hoppr'); ?>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- System Info -->
                <div class="hoppr-widget">
                    <div class="hoppr-widget-header">
                        <h2><?php _e('System Info', 'hoppr'); ?></h2>
                    </div>
                    <div class="hoppr-widget-content">
                        <div class="hoppr-system-info">
                            <div class="hoppr-info-item">
                                <strong><?php _e('Plugin Version:', 'hoppr'); ?></strong>
                                <span><?php echo esc_html(HOPPR_VERSION); ?></span>
                            </div>
                            <div class="hoppr-info-item">
                                <strong><?php _e('WordPress Version:', 'hoppr'); ?></strong>
                                <span><?php echo esc_html(get_bloginfo('version')); ?></span>
                            </div>
                            <div class="hoppr-info-item">
                                <strong><?php _e('PHP Version:', 'hoppr'); ?></strong>
                                <span><?php echo esc_html(PHP_VERSION); ?></span>
                            </div>
                            <div class="hoppr-info-item">
                                <strong><?php _e('Database:', 'hoppr'); ?></strong>
                                <span><?php echo esc_html($GLOBALS['wpdb']->db_version()); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>