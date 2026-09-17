/**
 * Tabs Navigation JavaScript for Conference Manager
 */

(function($) {
    'use strict';

    var TabsNavigation = {
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.handleHashNavigation();
        },

        bindEvents: function() {
            $(document).on('click', '.nav-tab', this.switchTab);
            $(window).on('hashchange', this.handleHashChange);
        },

        initTabs: function() {
            $('.nav-tab-wrapper').each(function() {
                var $wrapper = $(this);
                var $tabs = $wrapper.find('.nav-tab');
                var $content = $wrapper.siblings('.tab-content');
                
                // Hide all tab content initially
                $content.find('.tab-pane').removeClass('active');
                
                // Show active tab or first tab
                var $activeTab = $tabs.filter('.nav-tab-active');
                if (!$activeTab.length) {
                    $activeTab = $tabs.first().addClass('nav-tab-active');
                }
                
                var activeTabId = $activeTab.data('tab');
                if (activeTabId) {
                    $('#' + activeTabId).addClass('active');
                }
            });
        },

        switchTab: function(e) {
            e.preventDefault();
            
            var $clickedTab = $(this);
            var tabId = $clickedTab.data('tab');
            var $wrapper = $clickedTab.closest('.nav-tab-wrapper');
            var $content = $wrapper.siblings('.tab-content');
            
            // Remove active class from all tabs
            $wrapper.find('.nav-tab').removeClass('nav-tab-active');
            
            // Add active class to clicked tab
            $clickedTab.addClass('nav-tab-active');
            
            // Hide all tab content
            $content.find('.tab-pane').removeClass('active');
            
            // Show selected tab content
            if (tabId) {
                $('#' + tabId).addClass('active');
                
                // Update URL hash if needed
                if ($clickedTab.data('hash')) {
                    window.location.hash = tabId;
                }
                
                // Trigger custom event
                $(document).trigger('cm:tab:switched', [tabId, $clickedTab]);
            }
        },

        handleHashNavigation: function() {
            var hash = window.location.hash.substring(1);
            if (hash) {
                var $tab = $('.nav-tab[data-tab="' + hash + '"]');
                if ($tab.length) {
                    $tab.click();
                }
            }
        },

        handleHashChange: function() {
            TabsNavigation.handleHashNavigation();
        },

        // Public methods
        switchToTab: function(tabId) {
            var $tab = $('.nav-tab[data-tab="' + tabId + '"]');
            if ($tab.length) {
                $tab.click();
                return true;
            }
            return false;
        },

        getCurrentTab: function($wrapper) {
            if (!$wrapper) {
                $wrapper = $('.nav-tab-wrapper').first();
            }
            return $wrapper.find('.nav-tab-active').data('tab');
        },

        isTabValid: function(tabId) {
            var $tabContent = $('#' + tabId);
            if (!$tabContent.length) {
                return true; // Assume valid if no content to validate
            }
            
            var isValid = true;
            
            // Check for required fields
            $tabContent.find('[required]').each(function() {
                var $field = $(this);
                if (!$field.val().trim()) {
                    isValid = false;
                    $field.addClass('error');
                } else {
                    $field.removeClass('error');
                }
            });
            
            // Check for custom validation
            $tabContent.find('[data-validate]').each(function() {
                var $field = $(this);
                var validationType = $field.data('validate');
                var value = $field.val().trim();
                
                switch (validationType) {
                    case 'email':
                        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (value && !emailRegex.test(value)) {
                            isValid = false;
                            $field.addClass('error');
                        } else {
                            $field.removeClass('error');
                        }
                        break;
                    case 'url':
                        try {
                            if (value) new URL(value);
                            $field.removeClass('error');
                        } catch {
                            isValid = false;
                            $field.addClass('error');
                        }
                        break;
                }
            });
            
            return isValid;
        },

        showTabError: function(tabId, message) {
            var $tab = $('.nav-tab[data-tab="' + tabId + '"]');
            var $tabContent = $('#' + tabId);
            
            // Add error indicator to tab
            $tab.addClass('tab-error');
            
            // Show error message in tab content
            $tabContent.find('.tab-error-message').remove();
            $tabContent.prepend('<div class="notice notice-error tab-error-message"><p>' + message + '</p></div>');
            
            // Switch to the error tab
            $tab.click();
        },

        clearTabError: function(tabId) {
            var $tab = $('.nav-tab[data-tab="' + tabId + '"]');
            var $tabContent = $('#' + tabId);
            
            $tab.removeClass('tab-error');
            $tabContent.find('.tab-error-message').remove();
        },

        validateAllTabs: function() {
            var errors = [];
            
            $('.nav-tab').each(function() {
                var tabId = $(this).data('tab');
                if (tabId && !TabsNavigation.isTabValid(tabId)) {
                    errors.push({
                        tabId: tabId,
                        tabName: $(this).text().trim()
                    });
                }
            });
            
            return errors;
        }
    };

    $(document).ready(function() {
        if ($('.nav-tab-wrapper').length) {
            TabsNavigation.init();
        }
    });

    // Form submission validation
    $(document).on('submit', 'form.validate-tabs', function(e) {
        var errors = TabsNavigation.validateAllTabs();
        
        if (errors.length > 0) {
            e.preventDefault();
            
            // Show first error tab
            TabsNavigation.showTabError(errors[0].tabId, 'Please correct the errors in this tab.');
            
            // Show summary of all errors
            var errorMessage = 'Please correct errors in the following tabs: ' + 
                              errors.map(function(error) { return error.tabName; }).join(', ');
            CMAdmin.showNotice('error', errorMessage);
        }
    });

    window.TabsNavigation = TabsNavigation;

})(jQuery);