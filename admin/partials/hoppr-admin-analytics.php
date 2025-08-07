<?php

if (!defined('ABSPATH')) {
    exit;
}

// Get hoppr instance
$hoppr = hoppr_init();
$analytics = $hoppr->get_analytics();
$redirects = $hoppr->get_redirects();

// Get selected redirect and date range
$selected_redirect_id = isset($_GET['redirect_id']) ? intval($_GET['redirect_id']) : 0;
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Analytics', 'hoppr'); ?></h1>
    <hr class="wp-header-end">

    <!-- Filters -->
    <div class="hoppr-analytics-filters">
        <form method="get" class="hoppr-filter-form">
            <input type="hidden" name="page" value="hoppr-analytics">
            
            <div class="hoppr-filter-row">
                <div class="hoppr-filter-group">
                    <label for="redirect_id"><?php _e('Redirect:', 'hoppr'); ?></label>
                    <select name="redirect_id" id="redirect_id">
                        <option value="0"><?php _e('All Redirects', 'hoppr'); ?></option>
                        <?php
                        $all_redirects = $redirects->get_redirects(array('limit' => 0));
                        foreach ($all_redirects as $redirect):
                        ?>
                            <option value="<?php echo esc_attr($redirect['id']); ?>" <?php selected($selected_redirect_id, $redirect['id']); ?>>
                                <?php echo esc_html($redirect['source_url']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="hoppr-filter-group">
                    <label for="date_from"><?php _e('From:', 'hoppr'); ?></label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($date_from); ?>">
                </div>
                
                <div class="hoppr-filter-group">
                    <label for="date_to"><?php _e('To:', 'hoppr'); ?></label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($date_to); ?>">
                </div>
                
                <div class="hoppr-filter-group">
                    <input type="submit" class="button button-primary" value="<?php _e('Apply Filters', 'hoppr'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=hoppr-analytics'); ?>" class="button"><?php _e('Reset', 'hoppr'); ?></a>
                </div>
            </div>
        </form>
    </div>

    <div class="hoppr-analytics-dashboard">
        
        <!-- Summary Stats -->
        <div class="hoppr-stats-grid">
            <?php
            $args = array(
                'date_from' => $date_from,
                'date_to' => $date_to
            );
            
            if ($selected_redirect_id > 0) {
                $args['redirect_id'] = $selected_redirect_id;
            }
            
            $total_clicks = $analytics->get_clicks_count($args);
            $unique_clicks = $analytics->get_unique_clicks_count($args);
            $conversion_rate = $total_clicks > 0 ? round(($unique_clicks / $total_clicks) * 100, 1) : 0;
            ?>
            
            <div class="hoppr-stat-card">
                <div class="hoppr-stat-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div class="hoppr-stat-content">
                    <h3><?php echo number_format($total_clicks); ?></h3>
                    <p><?php _e('Total Clicks', 'hoppr'); ?></p>
                </div>
            </div>

            <div class="hoppr-stat-card">
                <div class="hoppr-stat-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="hoppr-stat-content">
                    <h3><?php echo number_format($unique_clicks); ?></h3>
                    <p><?php _e('Unique Visitors', 'hoppr'); ?></p>
                </div>
            </div>

            <div class="hoppr-stat-card">
                <div class="hoppr-stat-icon">
                    <span class="dashicons dashicons-performance"></span>
                </div>
                <div class="hoppr-stat-content">
                    <h3><?php echo $conversion_rate; ?>%</h3>
                    <p><?php _e('Unique Rate', 'hoppr'); ?></p>
                </div>
            </div>

            <div class="hoppr-stat-card">
                <div class="hoppr-stat-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="hoppr-stat-content">
                    <h3><?php echo ceil((strtotime($date_to) - strtotime($date_from)) / (60 * 60 * 24)) + 1; ?></h3>
                    <p><?php _e('Days Range', 'hoppr'); ?></p>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="hoppr-analytics-content">
            <div class="hoppr-analytics-left">
                
                <!-- Clicks Over Time Chart -->
                <div class="hoppr-widget">
                    <div class="hoppr-widget-header">
                        <h2><?php _e('Clicks Over Time', 'hoppr'); ?></h2>
                        <div class="hoppr-chart-controls">
                            <button type="button" class="button button-small" data-period="day"><?php _e('Daily', 'hoppr'); ?></button>
                            <button type="button" class="button button-small" data-period="week"><?php _e('Weekly', 'hoppr'); ?></button>
                            <button type="button" class="button button-small" data-period="month"><?php _e('Monthly', 'hoppr'); ?></button>
                        </div>
                    </div>
                    <div class="hoppr-widget-content">
                        <canvas id="hoppr-clicks-chart" width="400" height="200"></canvas>
                        <div id="hoppr-clicks-chart-loading" class="hoppr-chart-loading">
                            <span class="spinner is-active"></span>
                            <p><?php _e('Loading chart data...', 'hoppr'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Top Performing Redirects -->
                <div class="hoppr-widget">
                    <div class="hoppr-widget-header">
                        <h2><?php _e('Top Performing Redirects', 'hoppr'); ?></h2>
                        <a href="<?php echo admin_url('admin.php?page=hoppr-redirects'); ?>" class="button button-secondary"><?php _e('Manage', 'hoppr'); ?></a>
                    </div>
                    <div class="hoppr-widget-content">
                        <?php
                        $top_redirects = $analytics->get_top_redirects(array(
                            'date_from' => $date_from,
                            'date_to' => $date_to,
                            'limit' => 10
                        ));
                        ?>
                        
                        <?php if (!empty($top_redirects)): ?>
                            <table class="widefat">
                                <thead>
                                    <tr>
                                        <th><?php _e('Source URL', 'hoppr'); ?></th>
                                        <th><?php _e('Destination', 'hoppr'); ?></th>
                                        <th><?php _e('Clicks', 'hoppr'); ?></th>
                                        <th><?php _e('Unique', 'hoppr'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_redirects as $redirect): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html(wp_trim_words($redirect['source_url'], 4, '...')); ?></strong>
                                            </td>
                                            <td>
                                                <a href="<?php echo esc_url($redirect['destination_url']); ?>" target="_blank" rel="noopener">
                                                    <?php echo esc_html(wp_trim_words($redirect['destination_url'], 4, '...')); ?>
                                                    <span class="dashicons dashicons-external"></span>
                                                </a>
                                            </td>
                                            <td><strong><?php echo number_format($redirect['click_count']); ?></strong></td>
                                            <td><?php echo number_format($redirect['unique_clicks']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="hoppr-empty-state">
                                <span class="dashicons dashicons-chart-bar"></span>
                                <p><?php _e('No analytics data available for the selected period.', 'hoppr'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <div class="hoppr-analytics-right">
                
                <!-- Geographic Distribution -->
                <div class="hoppr-widget">
                    <div class="hoppr-widget-header">
                        <h2><?php _e('Geographic Distribution', 'hoppr'); ?></h2>
                    </div>
                    <div class="hoppr-widget-content">
                        <canvas id="hoppr-countries-chart" width="300" height="200"></canvas>
                        <div class="hoppr-countries-list">
                            <?php
                            $countries = $analytics->get_clicks_by_country(array(
                                'redirect_id' => $selected_redirect_id > 0 ? $selected_redirect_id : null,
                                'date_from' => $date_from,
                                'date_to' => $date_to,
                                'limit' => 10
                            ));
                            ?>
                            
                            <?php if (!empty($countries)): ?>
                                <?php foreach ($countries as $country): ?>
                                    <div class="hoppr-country-item">
                                        <span class="hoppr-country-code"><?php echo esc_html($country['country_code']); ?></span>
                                        <span class="hoppr-country-clicks"><?php echo number_format($country['clicks']); ?> <?php _e('clicks', 'hoppr'); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p><?php _e('No geographic data available.', 'hoppr'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Device Types -->
                <div class="hoppr-widget">
                    <div class="hoppr-widget-header">
                        <h2><?php _e('Device Types', 'hoppr'); ?></h2>
                    </div>
                    <div class="hoppr-widget-content">
                        <canvas id="hoppr-devices-chart" width="300" height="200"></canvas>
                        <div class="hoppr-devices-list">
                            <?php
                            $devices = $analytics->get_clicks_by_device(array(
                                'redirect_id' => $selected_redirect_id > 0 ? $selected_redirect_id : null,
                                'date_from' => $date_from,
                                'date_to' => $date_to
                            ));
                            ?>
                            
                            <?php if (!empty($devices)): ?>
                                <?php foreach ($devices as $device): ?>
                                    <div class="hoppr-device-item">
                                        <span class="hoppr-device-type">
                                            <span class="dashicons dashicons-<?php echo $device['device_type'] === 'Mobile' ? 'smartphone' : ($device['device_type'] === 'Tablet' ? 'tablet' : 'desktop'); ?>"></span>
                                            <?php echo esc_html($device['device_type']); ?>
                                        </span>
                                        <span class="hoppr-device-clicks"><?php echo number_format($device['clicks']); ?> <?php _e('clicks', 'hoppr'); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p><?php _e('No device data available.', 'hoppr'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Top Referrers -->
                <div class="hoppr-widget">
                    <div class="hoppr-widget-header">
                        <h2><?php _e('Top Referrers', 'hoppr'); ?></h2>
                    </div>
                    <div class="hoppr-widget-content">
                        <?php
                        $referrers = $analytics->get_top_referrers(array(
                            'redirect_id' => $selected_redirect_id > 0 ? $selected_redirect_id : null,
                            'date_from' => $date_from,
                            'date_to' => $date_to,
                            'limit' => 10
                        ));
                        ?>
                        
                        <?php if (!empty($referrers)): ?>
                            <div class="hoppr-referrers-list">
                                <?php foreach ($referrers as $referrer): ?>
                                    <div class="hoppr-referrer-item">
                                        <div class="hoppr-referrer-info">
                                            <strong><?php echo esc_html(wp_trim_words($referrer['referrer'], 6, '...')); ?></strong>
                                        </div>
                                        <div class="hoppr-referrer-clicks">
                                            <span><?php echo number_format($referrer['clicks']); ?></span>
                                            <small><?php _e('clicks', 'hoppr'); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="hoppr-empty-state">
                                <span class="dashicons dashicons-share"></span>
                                <p><?php _e('No referrer data available.', 'hoppr'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Export Options -->
                <div class="hoppr-widget">
                    <div class="hoppr-widget-header">
                        <h2><?php _e('Export Data', 'hoppr'); ?></h2>
                    </div>
                    <div class="hoppr-widget-content">
                        <div class="hoppr-export-options">
                            <p><?php _e('Download analytics data for the selected period:', 'hoppr'); ?></p>
                            
                            <div class="hoppr-export-buttons">
                                <?php
                                $export_args = array(
                                    'action' => 'hoppr_export_analytics',
                                    'date_from' => $date_from,
                                    'date_to' => $date_to,
                                    'nonce' => wp_create_nonce('hoppr_nonce')
                                );
                                
                                if ($selected_redirect_id > 0) {
                                    $export_args['redirect_id'] = $selected_redirect_id;
                                }
                                
                                $export_url = add_query_arg($export_args, admin_url('admin-ajax.php'));
                                ?>
                                
                                <a href="<?php echo esc_url($export_url); ?>" class="button button-secondary">
                                    <span class="dashicons dashicons-download"></span>
                                    <?php _e('Download CSV', 'hoppr'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Chart initialization (placeholder)
    // In production, you would use Chart.js to create these charts
    
    var clicksChart = $('#hoppr-clicks-chart');
    var countriesChart = $('#hoppr-countries-chart');
    var devicesChart = $('#hoppr-devices-chart');
    
    // Hide loading indicators
    $('#hoppr-clicks-chart-loading').hide();
    
    // Initialize Chart.js charts
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    } else {
        console.error('Chart.js library not loaded');
        clicksChart.after('<p style="text-align: center; padding: 20px; color: #666;">Chart.js library not loaded</p>');
    }
    
    function initializeCharts() {
        // Clicks Over Time Chart (Line Chart)
        <?php
        $clicks_data = $analytics->get_daily_clicks(array(
            'redirect_id' => $selected_redirect_id > 0 ? $selected_redirect_id : null,
            'date_from' => $date_from,
            'date_to' => $date_to
        ));
        ?>
        const clicksData = <?php echo json_encode($clicks_data); ?>;
        
        new Chart(document.getElementById('hoppr-clicks-chart'), {
            type: 'line',
            data: {
                labels: clicksData.map(item => item.date),
                datasets: [{
                    label: '<?php _e('Clicks', 'hoppr'); ?>',
                    data: clicksData.map(item => item.clicks),
                    borderColor: '#4F46E5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                aspectRatio: 2,
                interaction: {
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                onResize: function(chart, size) {
                    chart.canvas.style.height = '200px';
                }
            }
        });
        
        // Countries Chart (Doughnut Chart)
        <?php
        $countries_data = $analytics->get_clicks_by_country(array(
            'redirect_id' => $selected_redirect_id > 0 ? $selected_redirect_id : null,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'limit' => 8
        ));
        ?>
        const countriesData = <?php echo json_encode($countries_data); ?>;
        
        if (countriesData.length > 0) {
            new Chart(document.getElementById('hoppr-countries-chart'), {
                type: 'doughnut',
                data: {
                    labels: countriesData.map(item => item.country_code || 'Unknown'),
                    datasets: [{
                        data: countriesData.map(item => item.clicks),
                        backgroundColor: [
                            '#4F46E5', '#7C3AED', '#A855F7', '#C084FC',
                            '#E879F9', '#F0ABFC', '#F8BBD9', '#FDF2F8'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        // Devices Chart (Bar Chart)
        <?php
        $devices_data = $analytics->get_clicks_by_device(array(
            'redirect_id' => $selected_redirect_id > 0 ? $selected_redirect_id : null,
            'date_from' => $date_from,
            'date_to' => $date_to
        ));
        ?>
        const devicesData = <?php echo json_encode($devices_data); ?>;
        
        if (devicesData.length > 0) {
            new Chart(document.getElementById('hoppr-devices-chart'), {
                type: 'bar',
                data: {
                    labels: devicesData.map(item => item.device_type),
                    datasets: [{
                        label: '<?php _e('Clicks', 'hoppr'); ?>',
                        data: devicesData.map(item => item.clicks),
                        backgroundColor: ['#4F46E5', '#7C3AED', '#A855F7'],
                        borderColor: ['#4338CA', '#6D28D9', '#9333EA'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 1.5,
                    interaction: {
                        intersect: false
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    onResize: function(chart, size) {
                        chart.canvas.style.height = '250px';
                        chart.canvas.style.maxHeight = '250px';
                    }
                }
            });
        }
    }
    
    // Chart period controls
    $('.hoppr-chart-controls button').on('click', function() {
        $('.hoppr-chart-controls button').removeClass('button-primary').addClass('button-secondary');
        $(this).removeClass('button-secondary').addClass('button-primary');
        
        var period = $(this).data('period');
        console.log('Chart period changed to:', period);
        // Here you would reload the chart data with the new period
    });
});
</script>