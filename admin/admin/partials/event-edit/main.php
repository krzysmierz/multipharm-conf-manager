<?php

/**
 * Main event edit template with tabs
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/admin/partials/event-edit
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$valid_tabs = array('basic', 'lineup', 'quizzes', 'files', 'qr-codes', 'preview');
$current_tab = (isset($_GET['tab']) && in_array($_GET['tab'], $valid_tabs)) ? $_GET['tab'] : 'basic';
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        Edytuj wydarzenie: <?php echo esc_html($event->get_title()); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=conference-manager-events'); ?>" class="page-title-action">
        ← Powrót do listy
    </a>
    
    <hr class="wp-header-end">

    <!-- Event Edit Tabs Navigation -->
    <div class="cm-event-tabs flex border-b border-gray-200 bg-white mt-6">
        <a href="#" data-tab="basic" class="flex items-center gap-2 py-4 px-6 text-gray-500 font-medium hover:text-gray-700 hover:border-gray-300 transition-colors duration-150 ease-in-out border-b-2 border-transparent <?php echo $current_tab === 'basic' ? 'border-blue-500 text-blue-600' : ''; ?>">
            <span class="dashicons dashicons-admin-settings text-base"></span>
            Podstawowe
        </a>

        <a href="#" data-tab="lineup" class="flex items-center gap-2 py-4 px-6 text-gray-500 font-medium hover:text-gray-700 hover:border-gray-300 transition-colors duration-150 ease-in-out border-b-2 border-transparent <?php echo $current_tab === 'lineup' ? 'border-blue-500 text-blue-600' : ''; ?>">
            <span class="dashicons dashicons-list-view text-base"></span>
            Harmonogram
        </a>

        <a href="#" data-tab="quizzes" class="flex items-center gap-2 py-4 px-6 text-gray-500 font-medium hover:text-gray-700 hover:border-gray-300 transition-colors duration-150 ease-in-out border-b-2 border-transparent <?php echo $current_tab === 'quizzes' ? 'border-blue-500 text-blue-600' : ''; ?>">
            <span class="dashicons dashicons-clipboard text-base"></span>
            Quizy
        </a>

        <a href="#" data-tab="files" class="flex items-center gap-2 py-4 px-6 text-gray-500 font-medium hover:text-gray-700 hover:border-gray-300 transition-colors duration-150 ease-in-out border-b-2 border-transparent <?php echo $current_tab === 'files' ? 'border-blue-500 text-blue-600' : ''; ?>">
            <span class="dashicons dashicons-media-document text-base"></span>
            Pliki
        </a>

        <a href="#" data-tab="qr-codes" class="flex items-center gap-2 py-4 px-6 text-gray-500 font-medium hover:text-gray-700 hover:border-gray-300 transition-colors duration-150 ease-in-out border-b-2 border-transparent <?php echo $current_tab === 'qr-codes' ? 'border-blue-500 text-blue-600' : ''; ?>">
            <span class="dashicons dashicons-smartphone text-base"></span>
            Kody QR
        </a>

        <a href="#" data-tab="preview" class="flex items-center gap-2 py-4 px-6 text-gray-500 font-medium hover:text-gray-700 hover:border-gray-300 transition-colors duration-150 ease-in-out border-b-2 border-transparent <?php echo $current_tab === 'preview' ? 'border-blue-500 text-blue-600' : ''; ?>">
            <span class="dashicons dashicons-visibility text-base"></span>
            Podgląd
        </a>
    </div>

    <!-- Tab Content -->
    <div class="tab-content cm-tab-content bg-white border border-gray-200 border-t-0 shadow-sm">
        <div id="basic" class="tab-pane <?php echo $current_tab === 'basic' ? 'active' : ''; ?>" <?php echo $current_tab === 'basic' ? 'style="display: block !important;"' : ''; ?>>
            <?php include 'basic-info.php'; ?>
        </div>
        
        <div id="lineup" class="tab-pane <?php echo $current_tab === 'lineup' ? 'active' : ''; ?>" <?php echo $current_tab === 'lineup' ? 'style="display: block !important;"' : ''; ?>>
            <?php include 'lineup-manager.php'; ?>
        </div>
        
        <div id="quizzes" class="tab-pane <?php echo $current_tab === 'quizzes' ? 'active' : ''; ?>" <?php echo $current_tab === 'quizzes' ? 'style="display: block !important;"' : ''; ?>>
            <?php include 'quiz-manager.php'; ?>
        </div>
        
        <div id="files" class="tab-pane <?php echo $current_tab === 'files' ? 'active' : ''; ?>" <?php echo $current_tab === 'files' ? 'style="display: block !important;"' : ''; ?>>
            <?php include 'files-manager.php'; ?>
        </div>
        
        <div id="qr-codes" class="tab-pane <?php echo $current_tab === 'qr-codes' ? 'active' : ''; ?>" <?php echo $current_tab === 'qr-codes' ? 'style="display: block !important;"' : ''; ?>>
            <?php include 'qr-codes.php'; ?>
        </div>
        
        <div id="preview" class="tab-pane <?php echo $current_tab === 'preview' ? 'active' : ''; ?>" <?php echo $current_tab === 'preview' ? 'style="display: block !important;"' : ''; ?>>
            <?php include 'preview.php'; ?>
        </div>
    </div>
</div>

<style>
.cm-tab-content,
.tab-content {
    display: block !important;
    visibility: visible !important;
}

.tab-pane {
    display: none;
    padding: 32px;
}

.tab-pane.active,
#basic.active,
#lineup.active,
#quizzes.active,
#files.active,
#qr-codes.active,
#preview.active {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    height: auto !important;
    overflow: visible !important;
}

/* Enhanced active state for tabs */
.cm-event-tabs a.cm-tab-active {
    border-bottom-color: #3b82f6 !important;
    color: #2563eb !important;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Disable the external tabs-navigation.js by removing its event handlers
    $(document).off('click', '.nav-tab');
    
    // Clean tab switching function
    function switchToTab(tabId) {
        // Remove active classes from all tab buttons
        $('.cm-event-tabs a').removeClass('cm-tab-active border-blue-500 text-blue-600')
                             .addClass('border-transparent text-gray-500');

        // Add active class to clicked tab button
        $('.cm-event-tabs a[data-tab="' + tabId + '"]')
            .removeClass('border-transparent text-gray-500')
            .addClass('cm-tab-active border-blue-500 text-blue-600');

        // Hide all tab content
        $('.tab-pane').removeClass('active').hide();

        // Show selected tab content
        $('#' + tabId).addClass('active').show();
    }
    
    // Add click event handlers to tabs
    $('.cm-event-tabs a').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var tabId = $(this).data('tab');
        if (tabId) {
            switchToTab(tabId);
            
            // Update URL parameter
            var url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url);
        }
    });
    
    // Ensure parent container is visible
    $('.tab-content, .cm-tab-content').show().css('display', 'block');
    
    // Initialize with current tab
    var currentTab = '<?php echo $current_tab; ?>';
    setTimeout(function() {
        switchToTab(currentTab);
    }, 50);
});
</script>