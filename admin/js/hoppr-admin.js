/**
 * Hoppr Admin JavaScript
 */

(function($) {
    'use strict';

    // HTML Escaping utility function for XSS prevention
    const escapeHtml = function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    // Main Hoppr Admin Object
    const HopprAdmin = {
        
        init: function() {
            this.bindEvents();
            this.initComponents();
        },

        bindEvents: function() {
            // Form submissions
            $(document).on('submit', '.hoppr-form', this.handleFormSubmit);
            
            // Delete confirmations
            $(document).on('click', '.hoppr-delete', this.confirmDelete);
            
            // Bulk actions
            $(document).on('change', '#hoppr-bulk-action-selector-top, #hoppr-bulk-action-selector-bottom', this.handleBulkAction);
            $(document).on('click', '#doaction, #doaction2', this.handleBulkActionSubmit);
            
            // Toggle redirects
            $(document).on('change', '.hoppr-toggle-status', this.toggleRedirectStatus);
            
            // Quick edit
            $(document).on('click', '.hoppr-quick-edit', this.showQuickEdit);
            $(document).on('click', '.hoppr-cancel-quick-edit', this.hideQuickEdit);
            
            // Import/Export
            $(document).on('click', '.hoppr-export', this.exportRedirects);
            $(document).on('change', '#hoppr-import-file', this.validateImportFile);
            
            // Copy to clipboard
            $(document).on('click', '.hoppr-copy-url', this.copyToClipboard);
            
            // QR Code generation
            $(document).on('click', '.hoppr-generate-qr', this.generateQRCode);
            $(document).on('click', '.hoppr-regenerate-qr', this.regenerateQRCode);
            
            // Search functionality
            $(document).on('input', '#hoppr-search', this.debounce(this.performSearch, 500));
            
            // Filter changes
            $(document).on('change', '.hoppr-filter', this.applyFilters);
        },

        initComponents: function() {
            // Initialize tooltips
            this.initTooltips();
            
            // Initialize date pickers
            this.initDatePickers();
            
            // Initialize sortable tables
            this.initSortableTables();
        },

        // Form Handling
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('input[type="submit"], button[type="submit"]');
            const originalText = $submitBtn.val() || $submitBtn.text();
            
            // Show loading state
            $submitBtn.prop('disabled', true);
            $submitBtn.val(hoppr_ajax.strings.processing);
            $form.addClass('hoppr-loading');
            
            // Get form data
            const formData = new FormData(this);
            formData.append('action', $form.data('action'));
            formData.append('nonce', hoppr_ajax.nonce);
            
            // Submit via AJAX
            $.ajax({
                url: hoppr_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        HopprAdmin.showNotice(response.data.message, 'success');
                        
                        // Redirect or refresh as needed
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else if (response.data.refresh) {
                            window.location.reload();
                        }
                    } else {
                        HopprAdmin.showNotice(response.data || hoppr_ajax.strings.error, 'error');
                    }
                },
                error: function() {
                    HopprAdmin.showNotice(hoppr_ajax.strings.error, 'error');
                },
                complete: function() {
                    // Reset form state
                    $submitBtn.prop('disabled', false);
                    $submitBtn.val(originalText);
                    $form.removeClass('hoppr-loading');
                }
            });
        },

        // Delete Confirmation
        confirmDelete: function(e) {
            e.preventDefault();
            
            const $this = $(this);
            const message = $this.data('confirm') || hoppr_ajax.strings.confirm_delete;
            
            if (confirm(message)) {
                const redirectId = $this.data('id');
                HopprAdmin.deleteRedirect(redirectId);
            }
        },

        deleteRedirect: function(id) {
            $.ajax({
                url: hoppr_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hoppr_delete_redirect',
                    id: id,
                    nonce: hoppr_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $(`tr[data-id="${id}"]`).fadeOut(300, function() {
                            $(this).remove();
                        });
                        HopprAdmin.showNotice(response.data.message, 'success');
                    } else {
                        HopprAdmin.showNotice(response.data || hoppr_ajax.strings.error, 'error');
                    }
                },
                error: function() {
                    HopprAdmin.showNotice(hoppr_ajax.strings.error, 'error');
                }
            });
        },

        // Bulk Actions
        handleBulkAction: function() {
            const selectedAction = $(this).val();
            const $table = $(this).closest('.wp-list-table');
            
            if (selectedAction === 'delete') {
                $table.addClass('hoppr-bulk-delete-mode');
            } else {
                $table.removeClass('hoppr-bulk-delete-mode');
            }
        },

        handleBulkActionSubmit: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $form = $button.closest('form');
            const action = $button.attr('id') === 'doaction' ? 
                $('#hoppr-bulk-action-selector-top').val() : 
                $('#hoppr-bulk-action-selector-bottom').val();
            
            if (action === '-1') {
                return;
            }
            
            const selectedIds = [];
            $('input[name="redirect[]"]:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            if (selectedIds.length === 0) {
                alert(hoppr_ajax.strings.no_items_selected);
                return;
            }
            
            if (action === 'delete' && !confirm(hoppr_ajax.strings.confirm_bulk_delete)) {
                return;
            }
            
            // Perform bulk action
            $.ajax({
                url: hoppr_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hoppr_bulk_action',
                    bulk_action: action,
                    ids: selectedIds,
                    nonce: hoppr_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        HopprAdmin.showNotice(response.data.message, 'success');
                        window.location.reload();
                    } else {
                        HopprAdmin.showNotice(response.data || hoppr_ajax.strings.error, 'error');
                    }
                },
                error: function() {
                    HopprAdmin.showNotice(hoppr_ajax.strings.error, 'error');
                }
            });
        },

        // Toggle Redirect Status
        toggleRedirectStatus: function(e) {
            const $this = $(this);
            const redirectId = $this.data('id');
            const $row = $this.closest('tr');
            const isChecked = $this.is(':checked');
            
            // Temporarily disable the toggle to prevent double-clicking
            $this.prop('disabled', true);
            
            $.ajax({
                url: hoppr_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hoppr_toggle_redirect',
                    id: redirectId,
                    nonce: hoppr_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update status display
                        const $statusCell = $row.find('.hoppr-status');
                        $statusCell.removeClass('hoppr-status-active hoppr-status-inactive');
                        $statusCell.addClass('hoppr-status-' + response.data.status);
                        $statusCell.text(response.data.status.charAt(0).toUpperCase() + response.data.status.slice(1));
                        
                        // Ensure the toggle reflects the actual status
                        $this.prop('checked', response.data.status === 'active');
                        
                        HopprAdmin.showNotice(response.data.message, 'success');
                    } else {
                        // Revert the toggle if the request failed
                        $this.prop('checked', !isChecked);
                        HopprAdmin.showNotice(response.data || hoppr_ajax.strings.error, 'error');
                    }
                    
                    // Re-enable the toggle
                    $this.prop('disabled', false);
                },
                error: function() {
                    HopprAdmin.showNotice(hoppr_ajax.strings.error, 'error');
                }
            });
        },

        // Quick Edit
        showQuickEdit: function(e) {
            e.preventDefault();
            
            const $this = $(this);
            const $row = $this.closest('tr');
            const redirectId = $this.data('id');
            
            // Hide current row and show quick edit form
            $row.hide();
            
            // Create quick edit form (simplified version) - XSS protected
            const sourceUrl = escapeHtml($row.find('.source-url').text());
            const destinationUrl = escapeHtml($row.find('.destination-url').text());
            
            const $quickEditRow = $(`
                <tr class="hoppr-quick-edit-row">
                    <td colspan="6">
                        <form class="hoppr-quick-edit-form">
                            <div class="hoppr-quick-edit-fields">
                                <label>Source URL: <input type="text" name="source_url" value="${sourceUrl}"></label>
                                <label>Destination: <input type="text" name="destination_url" value="${destinationUrl}"></label>
                                <label>Type: 
                                    <select name="redirect_type">
                                        <option value="301">301</option>
                                        <option value="302">302</option>
                                    </select>
                                </label>
                            </div>
                            <div class="hoppr-quick-edit-actions">
                                <button type="submit" class="button button-primary">Update</button>
                                <button type="button" class="button hoppr-cancel-quick-edit">Cancel</button>
                            </div>
                        </form>
                    </td>
                </tr>
            `);
            
            $row.after($quickEditRow);
        },

        hideQuickEdit: function(e) {
            e.preventDefault();
            
            const $quickEditRow = $(this).closest('.hoppr-quick-edit-row');
            const $originalRow = $quickEditRow.prev('tr');
            
            $quickEditRow.remove();
            $originalRow.show();
        },

        // Search and Filters
        performSearch: function() {
            const searchTerm = $(this).val();
            const $table = $('.hoppr-redirects-table');
            
            if (searchTerm.length === 0) {
                $table.find('tbody tr').show();
                return;
            }
            
            $table.find('tbody tr').each(function() {
                const $row = $(this);
                const text = $row.text().toLowerCase();
                
                if (text.indexOf(searchTerm.toLowerCase()) === -1) {
                    $row.hide();
                } else {
                    $row.show();
                }
            });
        },

        applyFilters: function() {
            // Implement filter logic based on form values
            const $form = $(this).closest('form');
            const formData = $form.serialize();
            
            // Reload page with filter parameters
            window.location.href = window.location.pathname + '?' + formData;
        },

        // Copy to Clipboard
        copyToClipboard: function(e) {
            e.preventDefault();
            
            const textToCopy = $(this).data('copy') || $(this).text();
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(textToCopy).then(function() {
                    HopprAdmin.showNotice('Copied to clipboard!', 'success');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = textToCopy;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                HopprAdmin.showNotice('Copied to clipboard!', 'success');
            }
        },

        // Generate QR Code
        generateQRCode: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const redirectId = $button.data('id');
            const originalText = $button.text();
            const isEditScreen = $button.closest('.hoppr-form-table').length > 0;
            
            // Show loading state
            $button.prop('disabled', true).text(hoppr_ajax.strings.processing || 'Processing...');
            
            $.ajax({
                url: hoppr_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hoppr_regenerate_qr',
                    redirect_id: redirectId,
                    nonce: hoppr_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (isEditScreen) {
                            // Update the edit screen QR display
                            HopprAdmin.updateEditScreenQRDisplay(response.data.qr_codes, $button);
                        } else {
                            // Update table view QR display
                            HopprAdmin.updateTableQRDisplay(response.data.qr_codes, $button);
                        }
                        HopprAdmin.showNotice(response.data.message, 'success');
                    } else {
                        HopprAdmin.showNotice(response.data || hoppr_ajax.strings.error, 'error');
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    HopprAdmin.showNotice(hoppr_ajax.strings.error, 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        // Regenerate QR Code (for edit screen)
        regenerateQRCode: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const redirectId = $button.data('id');
            const originalText = $button.html();
            
            // Show loading state
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Regenerating...');
            
            $.ajax({
                url: hoppr_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hoppr_regenerate_qr',
                    redirect_id: redirectId,
                    nonce: hoppr_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        HopprAdmin.updateEditScreenQRDisplay(response.data.qr_codes, $button);
                        HopprAdmin.showNotice(response.data.message, 'success');
                    } else {
                        HopprAdmin.showNotice(response.data || hoppr_ajax.strings.error, 'error');
                        $button.prop('disabled', false).html(originalText);
                    }
                },
                error: function() {
                    HopprAdmin.showNotice(hoppr_ajax.strings.error, 'error');
                    $button.prop('disabled', false).html(originalText);
                }
            });
        },
        
        // Update table view QR display - XSS protected
        updateTableQRDisplay: function(qrCodes, $button) {
            const pngUrl = escapeHtml(qrCodes.png_url);
            const svgUrl = qrCodes.svg_url ? escapeHtml(qrCodes.svg_url) : '';
            
            const qrHtml = `
                <div class="hoppr-qr-actions-small">
                    <a href="${pngUrl}" download class="button button-small">PNG</a>
                    ${svgUrl ? `<a href="${svgUrl}" download class="button button-small">SVG</a>` : ''}
                </div>
            `;
            
            $button.closest('.column-qr').html(qrHtml);
        },
        
        // Update edit screen QR display - XSS protected
        updateEditScreenQRDisplay: function(qrCodes, $button) {
            const redirectId = escapeHtml($button.data('id'));
            const sourceUrl = escapeHtml($('#source_url').val() || 'redirect');
            const homeUrl = escapeHtml(window.location.origin);
            const pngUrl = escapeHtml(qrCodes.png_url);
            const svgUrl = qrCodes.svg_url ? escapeHtml(qrCodes.svg_url) : '';
            
            const qrHtml = `
                <div class="hoppr-qr-display">
                    <div class="hoppr-qr-preview">
                        <img src="${pngUrl}" 
                             alt="QR Code" 
                             width="200" height="200"
                             style="border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div class="hoppr-qr-info">
                        <p><strong>QR Code URL:</strong> ${homeUrl}/${sourceUrl}</p>
                        <p class="description">This QR code redirects to your destination URL when scanned.</p>
                    </div>
                    <div class="hoppr-qr-actions">
                        <a href="${pngUrl}" download class="button button-secondary">
                            <span class="dashicons dashicons-download"></span>
                            Download PNG
                        </a>
                        ${svgUrl ? `
                            <a href="${svgUrl}" download class="button button-secondary">
                                <span class="dashicons dashicons-download"></span>
                                Download SVG
                            </a>
                        ` : ''}
                        <button type="button" class="button button-secondary hoppr-regenerate-qr" data-id="${redirectId}">
                            <span class="dashicons dashicons-update"></span>
                            Regenerate
                        </button>
                    </div>
                </div>
            `;
            
            // Find the QR section and replace content
            const $qrSection = $button.closest('td').find('.hoppr-qr-display, .hoppr-qr-missing');
            if ($qrSection.length) {
                $qrSection.replaceWith(qrHtml);
            } else {
                $button.closest('td').html(qrHtml);
            }
        },

        // Utility Functions - XSS protected
        showNotice: function(message, type) {
            type = escapeHtml(type || 'info');
            message = escapeHtml(message);
            
            const $notice = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            $('.wrap h1').after($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        debounce: function(func, wait, immediate) {
            let timeout;
            return function() {
                const context = this;
                const args = arguments;
                const later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },

        initTooltips: function() {
            // Initialize WordPress-style tooltips
            $('.hoppr-tooltip').each(function() {
                $(this).attr('title', $(this).data('tooltip'));
            });
        },

        initDatePickers: function() {
            // Initialize date pickers if jQuery UI is available
            if ($.fn.datepicker) {
                $('.hoppr-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });
            }
        },

        initSortableTables: function() {
            // Make table headers sortable
            $('.hoppr-sortable-table th.sortable').on('click', function() {
                const $this = $(this);
                const column = $this.data('column');
                const currentOrder = $this.hasClass('asc') ? 'desc' : 'asc';
                
                // Update URL with sort parameters
                const url = new URL(window.location);
                url.searchParams.set('orderby', column);
                url.searchParams.set('order', currentOrder);
                window.location.href = url.toString();
            });
        },

        // Export functionality
        exportRedirects: function(e) {
            e.preventDefault();
            
            const format = $(this).data('format') || 'csv';
            const filters = $('.hoppr-filters form').serialize();
            
            window.location.href = `${hoppr_ajax.ajax_url}?action=hoppr_export&format=${format}&${filters}&nonce=${hoppr_ajax.nonce}`;
        },

        validateImportFile: function() {
            const file = this.files[0];
            const allowedTypes = ['text/csv', 'application/vnd.ms-excel'];
            
            if (file && !allowedTypes.includes(file.type)) {
                alert('Please select a valid CSV file.');
                $(this).val('');
                return false;
            }
            
            if (file && file.size > 5 * 1024 * 1024) { // 5MB limit
                alert('File size must be less than 5MB.');
                $(this).val('');
                return false;
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        HopprAdmin.init();
    });

    // Handle notice dismissals
    $(document).on('click', '.notice-dismiss', function() {
        $(this).closest('.notice').fadeOut(300, function() {
            $(this).remove();
        });
    });

    // Select all checkbox functionality
    $(document).on('change', '#cb-select-all-1, #cb-select-all-2', function() {
        const isChecked = $(this).prop('checked');
        $('input[name="redirect[]"]').prop('checked', isChecked);
    });

})(jQuery);