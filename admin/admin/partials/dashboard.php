<?php

/**
 * Dashboard template
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">KONF-Manager - Multipharm</h1>
    <div><span>v0.9.2</span>
		<div class="small">dev ops - Krzysztof Mierzejewski photograficznie.pl</div></div>
    <div class="cm-dashboard-container mt-6">
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- Total Events -->
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                            <span class="dashicons dashicons-calendar-alt text-blue-600"></span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Wszystkich wydarzeń</h3>
                        <p class="text-2xl font-semibold text-gray-900">
                            <?php echo array_sum($events_count); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Active Events -->
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                            <span class="dashicons dashicons-yes-alt text-green-600"></span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Aktywne wydarzenia</h3>
                        <p class="text-2xl font-semibold text-gray-900">
                            <?php echo isset($events_count['active']) ? $events_count['active'] : 0; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Draft Events -->
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 rounded-md flex items-center justify-center">
                            <span class="dashicons dashicons-edit text-yellow-600"></span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Szkice wydarzeń</h3>
                        <p class="text-2xl font-semibold text-gray-900">
                            <?php echo isset($events_count['draft']) ? $events_count['draft'] : 0; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Completed Events -->
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-gray-100 rounded-md flex items-center justify-center">
                            <span class="dashicons dashicons-archive text-gray-600"></span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Zakończone</h3>
                        <p class="text-2xl font-semibold text-gray-900">
                            <?php echo isset($events_count['completed']) ? $events_count['completed'] : 0; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Recent Events -->
            <div class="bg-white rounded-lg shadow-sm border">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Ostatnie wydarzenia</h2>
                </div>
                <div class="divide-y divide-gray-200">
                    <?php if (!empty($recent_events)): ?>
                        <?php foreach (array_slice($recent_events, 0, 5) as $event): ?>
                            <div class="px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-sm font-medium text-gray-900">
                                            <?php echo esc_html($event->title); ?>
                                        </h3>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <?php echo esc_html($event->event_date); ?>
                                        </p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php 
                                            switch($event->status) {
                                                case 'active':
                                                    echo 'bg-green-100 text-green-800';
                                                    break;
                                                case 'draft':
                                                    echo 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'paused':
                                                    echo 'bg-orange-100 text-orange-800';
                                                    break;
                                                case 'completed':
                                                    echo 'bg-gray-100 text-gray-800';
                                                    break;
                                                default:
                                                    echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                            <?php echo ucfirst($event->status); ?>
                                        </span>
                                        <a href="<?php echo admin_url('admin.php?page=conference-manager-events&action=edit&event_id=' . $event->id); ?>" 
                                           class="text-indigo-600 hover:text-indigo-900">
                                            <span class="dashicons dashicons-edit-large"></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="px-6 py-8 text-center">
                            <span class="dashicons dashicons-calendar-alt text-gray-400 text-4xl mb-4"></span>
                            <h3 class="text-sm font-medium text-gray-900 mb-2">Brak ewentów</h3>
                            <p class="text-sm text-gray-500 mb-4">Get started by creating your first event.</p>
                            <a href="<?php echo admin_url('admin.php?page=conference-manager-events'); ?>" 
                               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                Create Event
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm border">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Szybkie akcje</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <a href="<?php echo admin_url('admin.php?page=conference-manager-events'); ?>" 
                           class="flex items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <div class="flex-shrink-0">
                                <span class="dashicons dashicons-plus-alt2 text-indigo-600"></span>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-900">Utworz nowy event</h3>
                                <p class="text-sm text-gray-500">Konfiguracja i ustawienia nowego eventu lub konferencji</p>
                            </div>
                        </a>

                        <a href="<?php echo admin_url('admin.php?page=conference-manager-events'); ?>" 
                           class="flex items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <div class="flex-shrink-0">
                                <span class="dashicons dashicons-list-view text-indigo-600"></span>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-900">Zarządzaj eventami</h3>
                                <p class="text-sm text-gray-500">View and edit existing events</p>
                            </div>
                        </a>

                        <a href="<?php echo admin_url('admin.php?page=conference-manager-settings'); ?>" 
                           class="flex items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <div class="flex-shrink-0">
                                <span class="dashicons dashicons-admin-settings text-indigo-600"></span>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-900">Plugin Settings</h3>
                                <p class="text-sm text-gray-500">Configure global plugin options</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- QR Shortcodes Section -->
        <div class="mt-8">
            <div class="bg-white rounded-lg shadow-sm border">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Dostępne shortcody QR</h2>
                    <p class="text-sm text-gray-600 mt-1">Skopiuj i wklej te shortcody na swoich stronach</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <!-- Event Display Shortcode -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-900 mb-2">Wyświetl wydarzenie</h3>
                            <div class="bg-gray-50 p-3 rounded-md mb-3">
                                <code class="text-sm text-gray-800">[cm_event id="1"]</code>
                                <button onclick="copyToClipboard('[cm_event id=&quot;1&quot;]')" class="ml-2 text-xs text-indigo-600 hover:text-indigo-800">
                                    Kopiuj
                                </button>
                            </div>
                            <p class="text-xs text-gray-500">Wyświetla szczegóły wydarzenia o podanym ID</p>
                        </div>

                        <!-- Current Presentation Shortcode -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-900 mb-2">Aktualna prezentacja</h3>
                            <div class="bg-gray-50 p-3 rounded-md mb-3">
                                <code class="text-sm text-gray-800">[cm_current_presentation event_id="1"]</code>
                                <button onclick="copyToClipboard('[cm_current_presentation event_id=&quot;1&quot;]')" class="ml-2 text-xs text-indigo-600 hover:text-indigo-800">
                                    Kopiuj
                                </button>
                            </div>
                            <p class="text-xs text-gray-500">Pokazuje obecnie trwającą prezentację</p>
                        </div>

                        <!-- Quiz Display Shortcode -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-900 mb-2">Quiz</h3>
                            <div class="bg-gray-50 p-3 rounded-md mb-3">
                                <code class="text-sm text-gray-800">[cm_quiz id="1"]</code>
                                <button onclick="copyToClipboard('[cm_quiz id=&quot;1&quot;]')" class="ml-2 text-xs text-indigo-600 hover:text-indigo-800">
                                    Kopiuj
                                </button>
                            </div>
                            <p class="text-xs text-gray-500">Wyświetla quiz o podanym ID</p>
                        </div>

                        <!-- Event Lineup Shortcode -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-900 mb-2">Harmonogram wydarzenia</h3>
                            <div class="bg-gray-50 p-3 rounded-md mb-3">
                                <code class="text-sm text-gray-800">[cm_event_lineup event_id="1"]</code>
                                <button onclick="copyToClipboard('[cm_event_lineup event_id=&quot;1&quot;]')" class="ml-2 text-xs text-indigo-600 hover:text-indigo-800">
                                    Kopiuj
                                </button>
                            </div>
                            <p class="text-xs text-gray-500">Pokazuje pełny harmonogram wydarzenia</p>
                        </div>

                        <!-- Quiz Page with QR Shortcode -->
                        <div class="border border-gray-200 rounded-lg p-4 lg:col-span-2">
                            <h3 class="text-sm font-medium text-gray-900 mb-2">Strona z QR do quizu</h3>
                            <div class="bg-gray-50 p-3 rounded-md mb-3">
                                <code class="text-sm text-gray-800">[cm_quiz_display]</code>
                                <button onclick="copyToClipboard('[cm_quiz_display]')" class="ml-2 text-xs text-indigo-600 hover:text-indigo-800">
                                    Kopiuj
                                </button>
                            </div>
                            <p class="text-xs text-gray-500">
                                <strong>Tryb wyświetlania:</strong> Pokazuje kod QR do aktualnego quizu (dla ekranu projektora)<br>
                                <strong>Tryb uczestnika:</strong> Gdy ktoś zeskanuje QR lub doda parametr ?cm_quiz=ID, pokazuje formularz quizu
                            </p>
                        </div>
                    </div>

                    <!-- Usage Instructions -->
                    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h4 class="text-sm font-medium text-blue-900 mb-2">Jak używać:</h4>
                        <ul class="text-xs text-blue-800 space-y-1">
                            <li>• Skopiuj wybrany shortcode i wklej na swojej stronie lub w poście</li>
                            <li>• Zmień wartość ID na właściwe ID swojego wydarzenia/quizu</li>
                            <li>• Shortcode [cm_quiz_display] należy umieścić na dedykowanej stronie /quiz/</li>
                            <li>• Kody QR automatycznie przekierują na stronę z tym shortcodem</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom styles for dashboard */
.cm-dashboard-container .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}
</style>

<script>
function copyToClipboard(text) {
    // Create a temporary textarea element
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    
    // Select and copy the text
    textarea.select();
    document.execCommand('copy');
    
    // Remove the temporary element
    document.body.removeChild(textarea);
    
    // Show a brief feedback
    const button = event.target;
    const originalText = button.textContent;
    button.textContent = 'Skopiowano!';
    button.style.color = '#059669';
    
    setTimeout(() => {
        button.textContent = originalText;
        button.style.color = '';
    }, 2000);
}
</script>