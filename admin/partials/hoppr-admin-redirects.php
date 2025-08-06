<?php

if (!defined('ABSPATH')) {
    exit;
}

// Get action parameter
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$redirect_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get hoppr instance
$hoppr = hoppr_init();
$redirects = $hoppr->get_redirects();

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Redirects', 'hoppr'); ?></h1>
    
    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=hoppr-redirects&action=add'); ?>" class="page-title-action"><?php _e('Add New', 'hoppr'); ?></a>
        <hr class="wp-header-end">

        <!-- Filters and Search -->
        <div class="hoppr-filters">
            <form method="get" class="hoppr-filter-form">
                <input type="hidden" name="page" value="hoppr-redirects">
                
                <div class="alignleft actions">
                    <select name="status" id="filter-by-status">
                        <option value=""><?php _e('All statuses', 'hoppr'); ?></option>
                        <option value="active" <?php selected(isset($_GET['status']) && $_GET['status'] === 'active'); ?>><?php _e('Active', 'hoppr'); ?></option>
                        <option value="inactive" <?php selected(isset($_GET['status']) && $_GET['status'] === 'inactive'); ?>><?php _e('Inactive', 'hoppr'); ?></option>
                    </select>
                    
                    <select name="type" id="filter-by-type">
                        <option value=""><?php _e('All types', 'hoppr'); ?></option>
                        <option value="301" <?php selected(isset($_GET['type']) && $_GET['type'] === '301'); ?>>301</option>
                        <option value="302" <?php selected(isset($_GET['type']) && $_GET['type'] === '302'); ?>>302</option>
                    </select>
                    
                    <input type="submit" class="button" value="<?php _e('Filter', 'hoppr'); ?>">
                </div>
                
                <div class="alignright">
                    <input type="search" name="search" id="hoppr-search" placeholder="<?php _e('Search redirects...', 'hoppr'); ?>" value="<?php echo esc_attr(isset($_GET['search']) ? $_GET['search'] : ''); ?>">
                    <input type="submit" class="button" value="<?php _e('Search', 'hoppr'); ?>">
                </div>
            </form>
        </div>

        <!-- Bulk Actions -->
        <form method="post" id="hoppr-redirects-form">
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <select name="action" id="hoppr-bulk-action-selector-top">
                        <option value="-1"><?php _e('Bulk Actions', 'hoppr'); ?></option>
                        <option value="activate"><?php _e('Activate', 'hoppr'); ?></option>
                        <option value="deactivate"><?php _e('Deactivate', 'hoppr'); ?></option>
                        <option value="delete"><?php _e('Delete', 'hoppr'); ?></option>
                    </select>
                    <input type="submit" id="doaction" class="button action" value="<?php _e('Apply', 'hoppr'); ?>">
                </div>
                
                <div class="alignright actions">
                    <a href="<?php echo admin_url('admin.php?page=hoppr-redirects&action=import'); ?>" class="button"><?php _e('Import', 'hoppr'); ?></a>
                    <a href="<?php echo admin_url('admin.php?page=hoppr-redirects&action=export'); ?>" class="button"><?php _e('Export', 'hoppr'); ?></a>
                </div>
            </div>

            <!-- Redirects Table -->
            <table class="wp-list-table widefat fixed striped hoppr-redirects-table">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th scope="col" class="manage-column column-source sortable">
                            <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'source_url', 'order' => 'asc'))); ?>">
                                <span><?php _e('Source URL', 'hoppr'); ?></span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-destination">
                            <?php _e('Destination', 'hoppr'); ?>
                        </th>
                        <th scope="col" class="manage-column column-type">
                            <?php _e('Type', 'hoppr'); ?>
                        </th>
                        <th scope="col" class="manage-column column-status">
                            <?php _e('Status', 'hoppr'); ?>
                        </th>
                        <th scope="col" class="manage-column column-clicks">
                            <?php _e('Clicks', 'hoppr'); ?>
                        </th>
                        <th scope="col" class="manage-column column-date sortable">
                            <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'created_date', 'order' => 'desc'))); ?>">
                                <span><?php _e('Created', 'hoppr'); ?></span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-qr">
                            <?php _e('QR Code', 'hoppr'); ?>
                        </th>
                        <th scope="col" class="manage-column column-actions">
                            <?php _e('Actions', 'hoppr'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php
                    // Get redirects with filters
                    $args = array(
                        'limit' => 20,
                        'offset' => 0
                    );
                    
                    if (isset($_GET['status']) && !empty($_GET['status'])) {
                        $args['status'] = sanitize_text_field($_GET['status']);
                    }
                    
                    if (isset($_GET['search']) && !empty($_GET['search'])) {
                        $args['search'] = sanitize_text_field($_GET['search']);
                    }
                    
                    $redirect_list = $redirects->get_redirects($args);
                    
                    if (empty($redirect_list)):
                    ?>
                        <tr class="no-items">
                            <td class="colspanchange" colspan="9">
                                <div class="hoppr-empty-state">
                                    <span class="dashicons dashicons-randomize"></span>
                                    <h3><?php _e('No redirects found', 'hoppr'); ?></h3>
                                    <p><?php _e('Create your first redirect to get started.', 'hoppr'); ?></p>
                                    <a href="<?php echo admin_url('admin.php?page=hoppr-redirects&action=add'); ?>" class="button button-primary">
                                        <?php _e('Add New Redirect', 'hoppr'); ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($redirect_list as $redirect): ?>
                            <tr data-id="<?php echo esc_attr($redirect['id']); ?>">
                                <th scope="row" class="check-column">
                                    <input id="cb-select-<?php echo esc_attr($redirect['id']); ?>" type="checkbox" name="redirect[]" value="<?php echo esc_attr($redirect['id']); ?>">
                                </th>
                                <td class="source-url column-source">
                                    <strong>
                                        <a href="<?php echo admin_url('admin.php?page=hoppr-redirects&action=edit&id=' . $redirect['id']); ?>">
                                            <?php echo esc_html($redirect['source_url']); ?>
                                        </a>
                                    </strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo admin_url('admin.php?page=hoppr-redirects&action=edit&id=' . $redirect['id']); ?>"><?php _e('Edit', 'hoppr'); ?></a> |
                                        </span>
                                        <span class="view">
                                            <a href="<?php echo esc_url(home_url('/' . $redirect['source_url'])); ?>" target="_blank" rel="noopener"><?php _e('Visit', 'hoppr'); ?></a> |
                                        </span>
                                        <span class="analytics">
                                            <a href="<?php echo admin_url('admin.php?page=hoppr-analytics&redirect_id=' . $redirect['id']); ?>"><?php _e('Analytics', 'hoppr'); ?></a> |
                                        </span>
                                        <span class="trash">
                                            <a href="#" class="hoppr-delete" data-id="<?php echo esc_attr($redirect['id']); ?>" data-confirm="<?php _e('Are you sure you want to delete this redirect?', 'hoppr'); ?>"><?php _e('Delete', 'hoppr'); ?></a>
                                        </span>
                                    </div>
                                </td>
                                <td class="destination-url column-destination">
                                    <a href="<?php echo esc_url($redirect['destination_url']); ?>" target="_blank" rel="noopener">
                                        <?php echo esc_html(wp_trim_words($redirect['destination_url'], 6, '...')); ?>
                                        <span class="dashicons dashicons-external"></span>
                                    </a>
                                </td>
                                <td class="column-type">
                                    <span class="hoppr-redirect-type hoppr-type-<?php echo esc_attr($redirect['redirect_type']); ?>">
                                        <?php echo esc_html($redirect['redirect_type']); ?>
                                    </span>
                                </td>
                                <td class="column-status">
                                    <span class="hoppr-status hoppr-status-<?php echo esc_attr($redirect['status']); ?>">
                                        <?php echo esc_html(ucfirst($redirect['status'])); ?>
                                    </span>
                                </td>
                                <td class="column-clicks">
                                    <?php
                                    $analytics = $hoppr->get_analytics();
                                    $click_count = $analytics->get_clicks_count(array('redirect_id' => $redirect['id']));
                                    ?>
                                    <strong><?php echo esc_html($click_count); ?></strong>
                                </td>
                                <td class="column-date">
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($redirect['created_date']))); ?>
                                </td>
                                <td class="column-qr">
                                    <?php
                                    $hoppr = hoppr_init();
                                    $qr_codes = $hoppr->get_qr_codes();
                                    $qr_data = $qr_codes->get_qr_codes_for_redirect($redirect['id']);
                                    
                                    if ($qr_data && $qr_data['png_url']): ?>
                                        <div class="hoppr-qr-actions-small">
                                            <a href="<?php echo esc_url($qr_data['png_url']); ?>" download class="button button-small"><?php _e('PNG', 'hoppr'); ?></a>
                                            <?php if ($qr_data['svg_url']): ?>
                                                <a href="<?php echo esc_url($qr_data['svg_url']); ?>" download class="button button-small"><?php _e('SVG', 'hoppr'); ?></a>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <button type="button" class="button button-small hoppr-generate-qr" data-id="<?php echo esc_attr($redirect['id']); ?>">
                                            <?php _e('Generate QR', 'hoppr'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td class="column-actions">
                                    <button type="button" class="button button-small hoppr-toggle-status" data-id="<?php echo esc_attr($redirect['id']); ?>">
                                        <?php echo $redirect['status'] === 'active' ? __('Deactivate', 'hoppr') : __('Activate', 'hoppr'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <select name="action2" id="hoppr-bulk-action-selector-bottom">
                        <option value="-1"><?php _e('Bulk Actions', 'hoppr'); ?></option>
                        <option value="activate"><?php _e('Activate', 'hoppr'); ?></option>
                        <option value="deactivate"><?php _e('Deactivate', 'hoppr'); ?></option>
                        <option value="delete"><?php _e('Delete', 'hoppr'); ?></option>
                    </select>
                    <input type="submit" id="doaction2" class="button action" value="<?php _e('Apply', 'hoppr'); ?>">
                </div>
            </div>
            
            <?php wp_nonce_field('hoppr_bulk_action', 'hoppr_nonce'); ?>
        </form>

    <?php elseif ($action === 'add' || $action === 'edit'): ?>
        
        <?php
        $redirect_data = array(
            'source_url' => '',
            'destination_url' => '',
            'redirect_type' => '301',
            'preserve_query_strings' => false,
            'status' => 'active'
        );
        
        if ($action === 'edit' && $redirect_id) {
            $existing_redirect = $redirects->get_redirect($redirect_id);
            if ($existing_redirect) {
                $redirect_data = $existing_redirect;
            }
        }
        ?>
        
        <hr class="wp-header-end">

        <form method="post" class="hoppr-form" data-action="<?php echo $action === 'add' ? 'hoppr_add_redirect' : 'hoppr_update_redirect'; ?>">
            <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($redirect_id); ?>">
            <?php endif; ?>
            
            <div class="hoppr-form-columns">
                <!-- Left Column: Form Fields -->
                <div class="hoppr-form-left">
                    <table class="form-table hoppr-form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="source_url"><?php _e('Source URL', 'hoppr'); ?> <span class="required">*</span></label>
                                </th>
                                <td>
                                    <input type="text" id="source_url" name="source_url" value="<?php echo esc_attr($redirect_data['source_url']); ?>" class="regular-text" required placeholder="contact-us">
                                    <p class="description"><?php _e('Just the path after your domain (e.g., "contact-us" or "old-page")', 'hoppr'); ?></p>
                                    <p class="description"><?php printf(__('This will redirect from: %s<strong>[your-path]</strong>', 'hoppr'), home_url('/') . '<strong>'); ?></strong></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="destination_url"><?php _e('Destination URL', 'hoppr'); ?> <span class="required">*</span></label>
                                </th>
                                <td>
                                    <input type="url" id="destination_url" name="destination_url" value="<?php echo esc_attr($redirect_data['destination_url']); ?>" class="regular-text" required>
                                    <p class="description"><?php _e('The URL to redirect to (include http:// or https://)', 'hoppr'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="redirect_type"><?php _e('Redirect Type', 'hoppr'); ?></label>
                                </th>
                                <td>
                                    <select id="redirect_type" name="redirect_type">
                                        <option value="301" <?php selected($redirect_data['redirect_type'], '301'); ?>>301 - <?php _e('Permanent', 'hoppr'); ?></option>
                                        <option value="302" <?php selected($redirect_data['redirect_type'], '302'); ?>>302 - <?php _e('Temporary', 'hoppr'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('301 for permanent redirects, 302 for temporary redirects', 'hoppr'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="preserve_query_strings"><?php _e('Query Strings', 'hoppr'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="preserve_query_strings" name="preserve_query_strings" value="1" <?php checked($redirect_data['preserve_query_strings']); ?>>
                                        <?php _e('Preserve query strings in destination URL', 'hoppr'); ?>
                                    </label>
                                    <p class="description"><?php _e('Pass existing query parameters to the destination URL', 'hoppr'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="status"><?php _e('Status', 'hoppr'); ?></label>
                                </th>
                                <td>
                                    <select id="status" name="status">
                                        <option value="active" <?php selected($redirect_data['status'], 'active'); ?>><?php _e('Active', 'hoppr'); ?></option>
                                        <option value="inactive" <?php selected($redirect_data['status'], 'inactive'); ?>><?php _e('Inactive', 'hoppr'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('Only active redirects will be processed', 'hoppr'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Right Column: QR Code Section -->
                <div class="hoppr-form-right">
                    <?php if ($action === 'edit' && $redirect_id): ?>
                        <div class="hoppr-qr-section">
                            <h3><?php _e('QR Code', 'hoppr'); ?></h3>
                            <?php
                            $hoppr = hoppr_init();
                            $qr_codes = $hoppr->get_qr_codes();
                            $qr_data = $qr_codes->get_qr_codes_for_redirect($redirect_id);
                            
                            if ($qr_data && $qr_data['png_url']): ?>
                                <div class="hoppr-qr-display">
                                    <div class="hoppr-qr-preview">
                                        <img src="<?php echo esc_url($qr_data['png_url']); ?>" 
                                             alt="<?php _e('QR Code', 'hoppr'); ?>" 
                                             width="200" height="200"
                                             style="border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                    <div class="hoppr-qr-info">
                                        <p><strong><?php _e('QR Code URL:', 'hoppr'); ?></strong><br>
                                        <?php echo esc_html(home_url('/' . $redirect_data['source_url'])); ?></p>
                                        <p class="description"><?php _e('This QR code redirects to your destination URL when scanned.', 'hoppr'); ?></p>
                                    </div>
                                    <div class="hoppr-qr-actions">
                                        <a href="<?php echo esc_url($qr_data['png_url']); ?>" download class="button button-secondary">
                                            <span class="dashicons dashicons-download"></span>
                                            <?php _e('PNG', 'hoppr'); ?>
                                        </a>
                                        <?php if ($qr_data['svg_url']): ?>
                                            <a href="<?php echo esc_url($qr_data['svg_url']); ?>" download class="button button-secondary">
                                                <span class="dashicons dashicons-download"></span>
                                                <?php _e('SVG', 'hoppr'); ?>
                                            </a>
                                        <?php endif; ?>
                                        <button type="button" class="button button-secondary hoppr-regenerate-qr" data-id="<?php echo esc_attr($redirect_id); ?>">
                                            <span class="dashicons dashicons-update"></span>
                                            <?php _e('Regenerate', 'hoppr'); ?>
                                        </button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="hoppr-qr-missing">
                                    <p><?php _e('No QR code generated yet.', 'hoppr'); ?></p>
                                    <button type="button" class="button button-secondary hoppr-generate-qr" data-id="<?php echo esc_attr($redirect_id); ?>">
                                        <span class="dashicons dashicons-plus-alt2"></span>
                                        <?php _e('Generate QR Code', 'hoppr'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="hoppr-qr-section hoppr-qr-placeholder">
                            <h3><?php _e('QR Code', 'hoppr'); ?></h3>
                            <div class="hoppr-qr-info">
                                <p class="description"><?php _e('QR code will be generated after saving the redirect.', 'hoppr'); ?></p>
                                <div class="hoppr-qr-preview-placeholder">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <p><?php _e('QR Code Preview', 'hoppr'); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="hoppr-actions">
                <input type="submit" class="button button-primary" value="<?php echo $action === 'add' ? __('Add Redirect', 'hoppr') : __('Update Redirect', 'hoppr'); ?>">
                <a href="<?php echo admin_url('admin.php?page=hoppr-redirects'); ?>" class="button"><?php _e('Cancel', 'hoppr'); ?></a>
            </div>
            
            <?php wp_nonce_field('hoppr_nonce', 'nonce'); ?>
        </form>
        
    <?php endif; ?>
</div>