/**
 * Admin JavaScript for Conference Manager
 */

(function($) {
    'use strict';

    // Admin main functionality
    var CMAdmin = {
        init: function() {
            this.bindEvents();
            this.initComponents();
        },

        bindEvents: function() {
            // Global admin events
            $(document).on('click', '.cm-delete-item', this.handleDelete);
            $(document).on('click', '.cm-toggle-active', this.handleToggleActive);
            
            // Form validation
            $(document).on('submit', '.cm-form', this.validateForm);
        },

        initComponents: function() {
            // Initialize tooltips if available
            if (typeof $.fn.tooltip === 'function') {
                $('[data-toggle="tooltip"]').tooltip();
            }

            // Initialize modals
            this.initModals();
            
            // Initialize AJAX forms
            this.initAjaxForms();
        },

        initModals: function() {
            // Simple modal functionality
            $(document).on('click', '[data-modal]', function(e) {
                e.preventDefault();
                var modalId = $(this).data('modal');
                $('#' + modalId).show().addClass('cm-modal-active');
            });

            $(document).on('click', '.cm-modal-close, .cm-modal-backdrop', function() {
                $('.cm-modal').hide().removeClass('cm-modal-active');
            });
        },

        initAjaxForms: function() {
            $(document).on('submit', '.cm-ajax-form', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $submitBtn = $form.find('[type="submit"]');
                var originalText = $submitBtn.val() || $submitBtn.text();
                
                // Show loading state
                $submitBtn.prop('disabled', true);
                $submitBtn.val('Saving...');
                $form.addClass('cm-loading');
                
                // Prepare form data
                var formData = new FormData(this);
                formData.append('action', $form.data('action') || 'cm_admin_action');
                formData.append('nonce', cm_ajax.nonce);
                
                // Make AJAX request
                $.ajax({
                    url: cm_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            CMAdmin.showNotice('success', response.data.message || 'Operation completed successfully.');

                            // Trigger custom event
                            $form.trigger('cm:ajax:success', [response]);
                        } else {
                            CMAdmin.showNotice('error', response.data || 'An error occurred.');
                        }
                    },
                    error: function(xhr) {
                        // Check if we got a JSON response even with error status
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                CMAdmin.showNotice('success', response.data.message || 'Operation completed successfully.');
                                $form.trigger('cm:ajax:success', [response]);
                                return;
                            }
                        } catch (e) {
                            // Not JSON or parsing failed, continue with error handling
                        }

                        // Show appropriate error message
                        if (xhr.status === 500) {
                            CMAdmin.showNotice('error', 'Server error occurred. Please try again or contact administrator.');
                        } else {
                            CMAdmin.showNotice('error', 'Network error occurred. Please try again.');
                        }
                    },
                    complete: function() {
                        // Reset loading state
                        $submitBtn.prop('disabled', false);
                        $submitBtn.val(originalText);
                        $form.removeClass('cm-loading');
                    }
                });
            });
        },

        handleDelete: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                return;
            }
            
            var $button = $(this);
            var itemId = $button.data('id');
            var itemType = $button.data('type');
            
            // Make AJAX delete request
            $.ajax({
                url: cm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cm_delete_' + itemType,
                    id: itemId,
                    nonce: cm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $button.closest('tr, .cm-item').fadeOut(300, function() {
                            $(this).remove();
                        });
                        CMAdmin.showNotice('success', 'Item deleted successfully.');
                    } else {
                        CMAdmin.showNotice('error', response.data || 'Failed to delete item.');
                    }
                },
                error: function() {
                    CMAdmin.showNotice('error', 'Network error occurred.');
                }
            });
        },

        handleToggleActive: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var itemId = $button.data('id');
            var itemType = $button.data('type');
            var isActive = $button.hasClass('active');
            
            $.ajax({
                url: cm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cm_toggle_' + itemType,
                    id: itemId,
                    active: !isActive,
                    nonce: cm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $button.toggleClass('active');
                        $button.text(isActive ? 'Activate' : 'Deactivate');
                        CMAdmin.showNotice('success', 'Status updated successfully.');
                    } else {
                        CMAdmin.showNotice('error', response.data || 'Failed to update status.');
                    }
                },
                error: function() {
                    CMAdmin.showNotice('error', 'Network error occurred.');
                }
            });
        },

        validateForm: function(e) {
            var $form = $(this);
            var isValid = true;
            
            // Clear previous errors
            $form.find('.error').removeClass('error');
            $form.find('.error-message').remove();
            
            // Validate required fields
            $form.find('[required]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (!value) {
                    isValid = false;
                    $field.addClass('error');
                    $field.after('<span class="error-message">This field is required.</span>');
                }
            });
            
            // Validate email fields
            $form.find('input[type="email"]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (value && !emailRegex.test(value)) {
                    isValid = false;
                    $field.addClass('error');
                    $field.after('<span class="error-message">Please enter a valid email address.</span>');
                }
            });
            
            // Validate time fields
            $form.find('input[type="time"]').each(function() {
                var $field = $(this);
                var value = $field.val();
                
                if (value) {
                    var startTime = $form.find('input[name="start_time"]').val();
                    var endTime = $form.find('input[name="end_time"]').val();
                    
                    if (startTime && endTime && startTime >= endTime) {
                        isValid = false;
                        $field.addClass('error');
                        $field.after('<span class="error-message">End time must be after start time.</span>');
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                CMAdmin.showNotice('error', 'Please correct the errors in the form.');
            }
        },

        showNotice: function(type, message) {
            // Remove existing notices
            $('.cm-notice').remove();
            
            var noticeClass = 'notice notice-' + (type === 'success' ? 'success' : 'error');
            var notice = $('<div class="' + noticeClass + ' cm-notice is-dismissible"><p>' + message + '</p></div>');
            
            // Add to the top of the page
            $('.wrap').prepend(notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Scroll to top to show notice
            $('html, body').animate({
                scrollTop: 0
            }, 300);
        },

        // Utility functions
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        formatDate: function(date) {
            var options = { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            return new Date(date).toLocaleDateString('en-US', options);
        },

        debounce: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var later = function() {
                    clearTimeout(timeout);
                    func.apply(this, arguments);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        CMAdmin.init();
    });

    // Expose CMAdmin globally
    window.CMAdmin = CMAdmin;

})(jQuery);