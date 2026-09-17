<?php

/**
 * Lineup manager template
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/admin/partials/event-edit
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$lineup_items = CM_Lineup::get_by_event_chronological($event->get_id());
?>

<div class="space-y-8">
    <!-- Multi-day tabs -->
    <div class="cm-days-tabs mb-6">
        <div class="flex gap-2 border-b border-gray-200 items-center">
            <!-- Always show at least day 1 tab -->
            <?php for ($day = 1; $day <= $event->get_total_days(); $day++): ?>
                <div class="flex items-center gap-1 group">
                    <button class="day-tab px-4 py-2 border-b-2 transition-colors <?php echo $day === 1 ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>"
                            data-day="<?php echo $day; ?>">
                        Dzień <?php echo $day; ?>
                    </button>
                    <?php if ($event->get_total_days() > 1): ?>
                        <button class="delete-day-btn opacity-0 group-hover:opacity-100 transition-opacity p-1 text-red-500 hover:text-red-700"
                                data-day="<?php echo $day; ?>"
                                title="Usuń dzień <?php echo $day; ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>

            <?php if ($event->get_total_days() < 7): ?>
                <button id="add-day-btn" class="px-4 py-2 text-green-600 hover:text-green-700 border-b-2 border-transparent transition-colors">
                    + Dodaj kolejny dzień
                </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="flex items-center justify-between bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h2 class="text-2xl font-bold text-gray-900">Harmonogram prezentacji</h2>
        <div class="flex gap-3">
            <button type="button" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors shadow-sm" id="add-quick-event">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Szybkie wydarzenie
            </button>
            <button type="button" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-sm" id="add-lineup-item">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Dodaj prezentację
            </button>
            <button type="button" class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-medium rounded-lg transition-colors shadow-sm" id="clear-time-cache" title="Wyczyść cache godzin rozpoczęcia prezentacji">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Wyczyść cache godzin
            </button>
        </div>
    </div>
    
    <!-- Day content wrapper -->
    <div class="day-content space-y-6" data-day="1">
        <?php if (empty($lineup_items)): ?>
            <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl p-12 text-center">
                <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Brak prezentacji w harmonogramie</h3>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">Dodaj pierwszą prezentację, aby rozpocząć tworzenie harmonogramu wydarzenia.</p>
                <button type="button" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-sm" id="add-first-lineup-item">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Dodaj pierwszą prezentację
                </button>
            </div>
        <?php else: ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-blue-800">
                    <svg class="w-5 h-5 inline-block mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Przeciągnij prezentacje, aby zmienić ich kolejność. Kliknij <strong>Start</strong>, aby aktywować prezentację na żywo.
                </p>
            </div>
            
            <div class="space-y-4" id="lineup-sortable">
                <?php foreach ($lineup_items as $item): ?>
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200 p-6 <?php echo $item->is_active ? 'ring-2 ring-green-500 bg-green-50' : ''; ?> relative"
                         data-lineup-id="<?php echo $item->id; ?>"
                         data-quiz-id="<?php echo $item->quiz_id; ?>"
                         data-sort-order="<?php echo $item->sort_order ?? 0; ?>">
                         
                         
                        <div class="flex items-start gap-6">
                            <!-- Drag Handle -->
                            <div class="flex-shrink-0 cursor-move text-gray-400 hover:text-gray-600 transition-colors mt-1">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
                                </svg>
                            </div>
                            
                            <!-- Time & Duration -->
                            <div class="flex-shrink-0 text-center">
                                <div class="bg-gray-100 rounded-lg px-4 py-3 min-w-[120px]">
                                    <div class="text-lg font-bold text-gray-900"><?php echo esc_html($item->start_time); ?></div>
                                    <div class="text-sm text-gray-600">(<?php echo esc_html($item->duration_minutes); ?> min)</div>
                                </div>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <h4 class="text-lg font-semibold text-gray-900 mb-2">
                                <?php echo esc_html($item->title); ?>
                                <?php if (isset($item->event_type) && $item->event_type === 'quick'): ?>
                                    <span class="ml-2 inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Szybkie
                                    </span>
                                <?php endif; ?>
                            </h4>
                                <?php if ($item->presenter): ?>
                                    <div class="flex items-center text-sm text-gray-600 mb-2">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        <?php echo esc_html($item->presenter); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($item->description): ?>
                                    <p class="text-sm text-gray-700 mb-2"><?php echo wp_kses_post($item->description); ?></p>
                                <?php endif; ?>
                                <?php if ($item->presentation_file): ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <?php echo esc_html($item->presentation_file); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex items-center gap-4 ml-auto">
                                <!-- Controls Row: NA ŻYWO + Quiz Mode + Start -->
                                <div class="flex gap-3 items-stretch">
                                    <!-- NA ŻYWO Status -->
                                    <?php if ($item->is_active): ?>
                                        <div class="flex flex-col items-center justify-center px-3 py-2 text-sm font-medium bg-green-100 text-green-800 rounded-lg border border-green-200 animate-pulse h-20 min-w-[90px]">
                                            <svg class="w-5 h-5 mb-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z"/>
                                            </svg>
                                            <span class="text-xs font-semibold">NA ŻYWO</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Quiz Mode Controls -->
                                    <?php if ($item->is_active && !empty($item->quiz_id)): ?>
                                        <?php
                                        $quiz_state = class_exists('CM_Quiz_State') ? CM_Quiz_State::get_state($item->quiz_id) : null;
                                        $current_mode = $quiz_state ? $quiz_state->get_mode() : 'qr';
                                        ?>
                                        <button class="mode-option flex flex-col items-center justify-center px-3 py-2 border rounded-lg text-sm font-medium transition-colors h-20 min-w-[90px] <?php echo $current_mode === 'qr' ? 'border-green-500 bg-green-50 text-green-700' : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400 hover:bg-gray-50'; ?>" data-mode="qr">
                                            <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                            </svg>
                                            <span class="text-xs font-semibold">QR Code</span>
                                        </button>

                                        <button class="mode-option flex flex-col items-center justify-center px-3 py-2 border rounded-lg text-sm font-medium transition-colors h-20 min-w-[90px] <?php echo $current_mode === 'results' ? 'border-green-500 bg-green-50 text-green-700' : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400 hover:bg-gray-50'; ?>" data-mode="results">
                                            <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                            <span class="text-xs font-semibold">Wyniki</span>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <!-- Start Button -->
                                    <?php if (!$item->is_active): ?>
                                        <button type="button" class="flex flex-col items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm h-20 min-w-[90px] cm-start-presentation" 
                                                data-lineup-id="<?php echo $item->id; ?>">
                                            <svg class="w-5 h-5 mb-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z"/>
                                            </svg>
                                            <span class="text-xs font-semibold">Uruchom</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Action Buttons Column -->
                                <div class="flex flex-col gap-2">
                                    <button type="button" class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors cm-edit-lineup-item" 
                                            data-lineup-id="<?php echo $item->id; ?>">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Edytuj
                                    </button>
                                    
                                    <button type="button" class="inline-flex items-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors cm-delete-lineup-item" 
                                            data-lineup-id="<?php echo $item->id; ?>">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Usuń
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Add/Edit Lineup Item Modal -->
    <div id="lineup-item-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 id="lineup-modal-title" class="text-xl font-semibold text-gray-900">Dodaj prezentację</h3>
                <button type="button" class="text-gray-400 hover:text-gray-600 transition-colors cm-modal-close">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="lineup-item-form" class="cm-ajax-form" data-action="cm_save_lineup_item">
                <input type="hidden" name="event_id" value="<?php echo esc_attr($event->get_id()); ?>">
                <input type="hidden" name="lineup_id" id="lineup_id" value="">
                <input type="hidden" name="day_number" id="lineup_day_number" value="1">
                
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label for="lineup_title" class="block text-sm font-medium text-gray-700 mb-2">
                                Tytuł prezentacji *
                            </label>
                            <input type="text" id="lineup_title" name="title"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   required>
                        </div>

                        <div>
                            <label for="lineup_day_select" class="block text-sm font-medium text-gray-700 mb-2">
                                Dzień wydarzenia *
                            </label>
                            <select id="lineup_day_select" name="day_select"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    required>
                                <?php for ($day = 1; $day <= $event->get_total_days(); $day++): ?>
                                    <option value="<?php echo $day; ?>">Dzień <?php echo $day; ?></option>
                                <?php endfor; ?>
                            </select>
                            <p class="text-sm text-gray-600 mt-1">
                                Wybierz dzień, do którego ma być przypisana prezentacja
                            </p>
                        </div>
                        
                        <div>
                            <label for="lineup_presenter" class="block text-sm font-medium text-gray-700 mb-2">
                                Prelegent
                            </label>
                            <input type="text" id="lineup_presenter" name="presenter" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="lineup_start_time" class="block text-sm font-medium text-gray-700 mb-2">
                                Godzina rozpoczęcia *
                            </label>
                            <input type="time" id="lineup_start_time" name="start_time"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   required>
                            <div id="time-conflict-warning" class="hidden mt-2 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.318 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    <span class="text-red-800 text-sm font-medium">Prezentacja o tej godzinie już istnieje!</span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label for="lineup_duration" class="block text-sm font-medium text-gray-700 mb-2">
                                Czas trwania (minuty)
                            </label>
                            <input type="number" id="lineup_duration" name="duration_minutes" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                   value="30" min="5" max="300">
                        </div>
                        
                        <div>
                            <label for="lineup_quiz_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Quiz dla uczestników
                            </label>
                            <select id="lineup_quiz_id" name="quiz_id" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">-- Brak quizu --</option>
                                <?php 
                                $available_quizzes = CM_Database::get_results('quizzes', array(
                                    'event_id' => $event->get_id(),
                                    'is_active' => 1
                                ), 'title ASC');
                                
                                foreach ($available_quizzes as $quiz) {
                                    echo '<option value="' . esc_attr($quiz->id) . '">' . esc_html($quiz->title) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="text-sm text-gray-600 mt-2">
                                Wybierz quiz, który będzie wyświetlany podczas tej prezentacji. 
                                <br><strong>Uwaga:</strong> Aby quiz był dostępny, musi być aktywny.
                            </p>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="lineup_description" class="block text-sm font-medium text-gray-700 mb-2">
                                Opis prezentacji
                            </label>
                            <textarea id="lineup_description" name="description" rows="4" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors cm-modal-close">
                        Anuluj
                    </button>
                    <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        Zapisz prezentację
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Event Modal -->
    <div id="quick-event-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center p-4"
         role="dialog"
         aria-modal="true"
         aria-labelledby="quick-modal-title">

        <div class="absolute inset-0 bg-black bg-opacity-50 transition-opacity"
             data-close-modal></div>

        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md"
             role="document">

            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 id="quick-modal-title" class="text-lg font-semibold text-gray-900">
                        <?php _e('Dodaj szybkie wydarzenie', 'conference-manager'); ?>
                    </h3>
                    <button type="button"
                            class="text-gray-400 hover:text-gray-600 transition-colors"
                            data-close-modal
                            aria-label="<?php esc_attr_e('Zamknij modal', 'conference-manager'); ?>">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Templates -->
                <div class="grid grid-cols-2 gap-3 mb-4" role="group" aria-label="<?php esc_attr_e('Szablony wydarzeń', 'conference-manager'); ?>">
                    <?php foreach (CM_Lineup::get_quick_event_templates() as $key => $template): ?>
                    <button type="button"
                            class="quick-event-template p-3 border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 focus:border-green-500 focus:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50"
                            data-title="<?php echo esc_attr($template['title']); ?>"
                            data-duration="<?php echo esc_attr($template['duration']); ?>"
                            aria-label="<?php echo esc_attr(sprintf(__('Szablon: %s, %d minut', 'conference-manager'), $template['title'], $template['duration'])); ?>">
                        <div class="text-center">
                            <div class="w-6 h-6 mx-auto mb-1 text-gray-600 text-xl">
                                <?php
                                $icons = array(
                                    'coffee' => '☕',
                                    'utensils' => '🍽️',
                                    'users' => '👥',
                                    'clipboard' => '📋'
                                );
                                echo $icons[$template['icon']] ?? '⏰';
                                ?>
                            </div>
                            <div class="text-sm font-medium"><?php echo esc_html($template['title']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo esc_html($template['duration']); ?> <?php _e('min', 'conference-manager'); ?></div>
                        </div>
                    </button>
                    <?php endforeach; ?>
                </div>

                <form id="quick-event-form" novalidate>
                    <div class="space-y-4">
                        <div>
                            <label for="quick_title" class="block text-sm font-medium text-gray-700 mb-2">
                                <?php _e('Nazwa wydarzenia', 'conference-manager'); ?> *
                            </label>
                            <input type="text"
                                   id="quick_title"
                                   name="title"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   required
                                   aria-describedby="quick_title_error">
                            <div id="quick_title_error" class="hidden mt-1 text-sm text-red-600" role="alert"></div>
                        </div>

                        <div>
                            <label for="quick_day_select" class="block text-sm font-medium text-gray-700 mb-2">
                                <?php _e('Dzień wydarzenia', 'conference-manager'); ?> *
                            </label>
                            <select id="quick_day_select" name="day_number"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    required>
                                <?php for ($day = 1; $day <= $event->get_total_days(); $day++): ?>
                                    <option value="<?php echo $day; ?>">Dzień <?php echo $day; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div>
                            <label for="quick_start_time" class="block text-sm font-medium text-gray-700 mb-2">
                                <?php _e('Godzina rozpoczęcia', 'conference-manager'); ?> *
                            </label>
                            <input type="time"
                                   id="quick_start_time"
                                   name="start_time"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   required
                                   aria-describedby="quick_time_error time-conflict-warning">
                            <div id="quick_time_error" class="hidden mt-1 text-sm text-red-600" role="alert"></div>
                            <div id="time-conflict-warning" class="hidden mt-2 p-3 bg-red-50 border border-red-200 rounded-lg" role="alert">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.318 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    <span class="text-red-800 text-sm font-medium" id="conflict-message"></span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <?php _e('Czas trwania', 'conference-manager'); ?>
                            </label>
                            <div class="flex items-center gap-3">
                                <div class="flex gap-2" role="radiogroup" aria-label="<?php esc_attr_e('Wybierz czas trwania wydarzenia', 'conference-manager'); ?>">
                                    <?php
                                    $durations = array(5, 10, 15, 30, 60);
                                    foreach ($durations as $i => $duration):
                                        $label = $duration < 60 ? $duration . 'min' : ($duration / 60) . 'h';
                                        $checked = $duration === 15 ? 'true' : 'false';
                                    ?>
                                    <button type="button"
                                            class="duration-btn px-3 py-1 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo $duration === 15 ? 'bg-green-500 text-white' : 'border-gray-300 hover:border-gray-400'; ?>"
                                            data-duration="<?php echo $duration; ?>"
                                            role="radio"
                                            aria-checked="<?php echo $checked; ?>"
                                            aria-label="<?php echo esc_attr(sprintf(__('%s minut', 'conference-manager'), $duration)); ?>">
                                        <?php echo esc_html($label); ?>
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button"
                                        id="add-five-minutes"
                                        class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        title="Dodaj 5 minut">
                                    +5min
                                </button>
                            </div>
                            <div class="mt-2 text-sm text-gray-600">
                                Aktualny czas: <span id="current-duration-display" class="font-medium text-gray-900">15 min</span>
                            </div>
                            <input type="hidden" id="quick_duration" name="duration_minutes" value="15">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                data-close-modal>
                            <?php _e('Anuluj', 'conference-manager'); ?>
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors"
                                id="submit-quick-event">
                            <?php _e('Dodaj wydarzenie', 'conference-manager'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Minimal styling for drag handle cursor since Tailwind doesn't have cursor-move -->
<style>
.cursor-move { cursor: move; }
#lineup-sortable .ui-sortable-placeholder {
    visibility: visible !important;
    height: 80px !important;
    background: #f3f4f6;
    border: 2px dashed #d1d5db;
    border-radius: 12px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize sortable lineup
    if (typeof jQuery !== 'undefined' && jQuery.fn.sortable) {
        jQuery('#lineup-sortable').sortable({
            handle: '.cursor-move',
            placeholder: 'ui-sortable-placeholder',
            update: function(event, ui) {
                var lineupOrder = jQuery(this).sortable('toArray', {attribute: 'data-lineup-id'});
                updateLineupOrder(lineupOrder);
            }
        });
    }
    
    // Modal functionality
    const modal = document.getElementById('lineup-item-modal');
    const addButtons = document.querySelectorAll('#add-lineup-item, #add-first-lineup-item');
    const closeButtons = document.querySelectorAll('.cm-modal-close');
    const backdrop = modal.querySelector('.absolute.inset-0');
    
    addButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('lineup-modal-title').textContent = 'Dodaj prezentację';
            document.getElementById('lineup-item-form').reset();
            document.getElementById('lineup_id').value = '';
            // Set current day number
            const currentDay = parseInt(document.querySelector('.day-content')?.getAttribute('data-day')) || 1;
            document.getElementById('lineup_day_number').value = currentDay;
            document.getElementById('lineup_day_select').value = currentDay;
            modal.style.display = 'flex';
            modal.classList.remove('hidden');
        });
    });
    
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            modal.style.display = 'none';
            modal.classList.add('hidden');
        });
    });
    
    if (backdrop) {
        backdrop.addEventListener('click', function() {
            modal.style.display = 'none';
            modal.classList.add('hidden');
        });
    }

    // Handle day selection dropdown change
    const daySelectDropdown = document.getElementById('lineup_day_select');
    if (daySelectDropdown) {
        daySelectDropdown.addEventListener('change', function() {
            const selectedDay = parseInt(this.value);
            document.getElementById('lineup_day_number').value = selectedDay;

            // Clear time conflict warning when changing days
            const warningDiv = document.getElementById('time-conflict-warning');
            if (warningDiv) {
                warningDiv.classList.add('hidden');
            }

            // Re-check time conflicts if start time is already set
            const startTimeInput = document.getElementById('lineup_start_time');
            if (startTimeInput && startTimeInput.value) {
                startTimeInput.dispatchEvent(new Event('change'));
            }
        });
    }

    // Edit lineup item
    document.querySelectorAll('.cm-edit-lineup-item').forEach(button => {
        button.addEventListener('click', function() {
            const lineupId = this.dataset.lineupId;
            
            // Load lineup data
            jQuery.ajax({
                url: cm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cm_get_lineup_item',
                    lineup_id: lineupId,
                    nonce: cm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        // Populate form fields
                        document.getElementById('lineup-modal-title').textContent = 'Edytuj prezentację';
                        document.getElementById('lineup_id').value = data.id;
                        document.getElementById('lineup_title').value = data.title || '';
                        document.getElementById('lineup_presenter').value = data.presenter || '';
                        document.getElementById('lineup_start_time').value = data.start_time || '';
                        document.getElementById('lineup_duration').value = data.duration_minutes || '';
                        document.getElementById('lineup_description').value = data.description || '';
                        document.getElementById('lineup_quiz_id').value = data.quiz_id || '';
                        document.getElementById('lineup_day_select').value = data.day_number || 1;
                        document.getElementById('lineup_day_number').value = data.day_number || 1;

                        modal.style.display = 'flex';
                        modal.classList.remove('hidden');
                    } else {
                        alert('Błąd podczas ładowania danych: ' + response.data);
                    }
                }
            });
        });
    });
    
    // Check for time conflicts when start time changes
    document.getElementById('lineup_start_time').addEventListener('change', function() {
        const startTime = this.value;
        const lineupId = document.getElementById('lineup_id').value;
        const eventId = <?php echo $event->get_id(); ?>;
        const warningDiv = document.getElementById('time-conflict-warning');

        if (!startTime) {
            warningDiv.classList.add('hidden');
            return;
        }

        // Get duration for conflict check
        const durationMinutes = document.getElementById('lineup_duration').value || 30;
        const dayNumber = document.getElementById('lineup_day_number').value || 1;

        // Check for conflicts via AJAX
        jQuery.ajax({
            url: cm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cm_check_time_conflict',
                event_id: eventId,
                start_time: startTime,
                duration_minutes: durationMinutes,
                day_number: dayNumber,
                lineup_id: lineupId || '',
                nonce: cm_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.has_conflict) {
                    warningDiv.classList.remove('hidden');
                } else {
                    warningDiv.classList.add('hidden');
                }
            }
        });
    });

    // Handle successful form submission
    jQuery(document).on('cm:ajax:success', '#lineup-item-form', function(e, response) {
        // Close modal
        modal.style.display = 'none';
        modal.classList.add('hidden');

        // Reload page to show changes
        window.location.reload();
    });

    // Alternative detection for successful submission via SSE
    let lastLineupCount = document.querySelectorAll('[data-lineup-id]').length;

    // Listen for SSE lineup changes
    if (typeof window.eventLiveUpdates !== 'undefined') {
        window.eventLiveUpdates.addEventListener('lineup-change', function(event) {
            const newCount = Object.keys(event.detail || {}).length;

            // If lineup count increased and modal is open, it means presentation was added
            if (newCount > lastLineupCount && modal.style.display === 'flex') {
                // Close modal after successful addition
                modal.style.display = 'none';
                modal.classList.add('hidden');

                // Show success message
                if (window.CMAdmin) {
                    window.CMAdmin.showNotice('success', 'Prezentacja została dodana pomyślnie!');
                }

                // Reload page to show changes
                setTimeout(() => window.location.reload(), 1000);
            }

            lastLineupCount = newCount;
        });
    }
    
    // Start presentation
    document.querySelectorAll('.cm-start-presentation').forEach(button => {
        button.addEventListener('click', function() {
            const lineupId = this.dataset.lineupId;
            const eventId = <?php echo $event->get_id(); ?>;
            
            if (!confirm('Czy na pewno chcesz uruchomić tę prezentację? Inne prezentacje zostaną zatrzymane.')) {
                return;
            }
            
            jQuery.ajax({
                url: cm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cm_start_presentation',
                    event_id: eventId,
                    lineup_id: lineupId,
                    nonce: cm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Błąd: ' + response.data);
                    }
                }
            });
        });
    });
    
    // Delete lineup item
    document.querySelectorAll('.cm-delete-lineup-item').forEach(button => {
        button.addEventListener('click', function() {
            const lineupId = this.dataset.lineupId;

            if (!confirm('Czy na pewno chcesz usunąć tę prezentację?')) {
                return;
            }

            jQuery.ajax({
                url: cm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cm_delete_lineup_item',
                    lineup_id: lineupId,
                    nonce: cm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Błąd: ' + response.data);
                    }
                }
            });
        });
    });

    // Handle quiz mode switching in lineup
    document.addEventListener('click', function(e) {
        const modeOption = e.target.closest('.mode-option');
        if (!modeOption) return;

        e.preventDefault();
        e.stopPropagation();

        const lineupItem = e.target.closest('[data-quiz-id]');
        if (!lineupItem) return;

        const quizId = lineupItem.dataset.quizId;
        const mode = modeOption.dataset.mode;

        if (!quizId || quizId === '0') {
            alert('Błąd po stronie klienta: Brak ID quizu dla tego elementu harmonogramu.');
            return;
        }

        // Do nothing if clicking the active mode
        if (modeOption.classList.contains('border-green-500')) {
            return;
        }

        // AJAX call to set the new mode
        jQuery.ajax({
            url: cm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cm_set_quiz_state_mode',
                quiz_id: quizId,
                mode: mode,
                nonce: cm_ajax.nonce
            },
            beforeSend: function() {
                const parent = modeOption.parentElement;
                const allOptions = parent.querySelectorAll('.mode-option');
                allOptions.forEach(opt => opt.style.opacity = '0.5');
            },
            success: function(response) {
                if (response.success) {
                    // Update UI for this specific lineup item
                    const parent = modeOption.parentElement;
                    const allOptions = parent.querySelectorAll('.mode-option');
                    allOptions.forEach(opt => {
                        opt.classList.remove('border-green-500', 'bg-green-50', 'text-green-800');
                        opt.classList.add('border-gray-200', 'text-gray-600');
                        opt.style.opacity = '1';
                    });
                    modeOption.classList.remove('border-gray-200', 'text-gray-600');
                    modeOption.classList.add('border-green-500', 'bg-green-50', 'text-green-800');
                } else {
                    alert('BŁĄD (lineup-manager.php): ' + (response.data || 'Nieznany błąd'));
                    const parent = modeOption.parentElement;
                    const allOptions = parent.querySelectorAll('.mode-option');
                    allOptions.forEach(opt => opt.style.opacity = '1');
                }
            },
            error: function() {
                alert('Wystąpił błąd sieciowy podczas zmiany trybu.');
                const parent = modeOption.parentElement;
                const allOptions = parent.querySelectorAll('.mode-option');
                allOptions.forEach(opt => opt.style.opacity = '1');
            }
        });
    });
    
    // Function to send messages to quiz display window
    function sendMessageToQuizDisplay(action, quizId) {
        try {
            // Try to communicate with existing quiz display window
            const quizWindow = window.open('', 'quiz-display');
            if (quizWindow && !quizWindow.closed) {
                quizWindow.postMessage({
                    action: action,
                    quiz_id: quizId
                }, '*');
            }
        } catch (e) {
            // Ignore errors if window doesn't exist
        }
    }

    // QUICK EVENTS FUNCTIONALITY
    const quickModal = document.getElementById('quick-event-modal');
    const quickForm = document.getElementById('quick-event-form');
    const quickTitleInput = document.getElementById('quick_title');

    if (!quickModal || !quickForm || !quickTitleInput) return;

    let previousActiveElement = null;

    function openQuickModal() {
        previousActiveElement = document.activeElement;
        quickModal.style.display = 'flex';
        quickModal.classList.remove('hidden');
        quickTitleInput.focus();
        document.body.style.overflow = 'hidden';

        // Set current day in quick modal
        const currentDay = parseInt(document.querySelector('.day-content')?.getAttribute('data-day')) || 1;
        const daySelect = document.getElementById('quick_day_select');
        if (daySelect) {
            daySelect.value = currentDay;
        }
    }

    function closeQuickModal() {
        quickModal.style.display = 'none';
        quickModal.classList.add('hidden');
        quickForm.reset();
        clearQuickErrors();
        document.body.style.overflow = '';
        if (previousActiveElement) {
            previousActiveElement.focus();
        }
    }

    document.getElementById('add-quick-event')?.addEventListener('click', openQuickModal);

    quickModal.querySelectorAll('[data-close-modal]').forEach(el => {
        el.addEventListener('click', closeQuickModal);
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !quickModal.classList.contains('hidden')) {
            closeQuickModal();
        }
    });

    quickModal.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            const focusableElements = quickModal.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            if (e.shiftKey && document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            } else if (!e.shiftKey && document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    });

    quickModal.querySelectorAll('.quick-event-template').forEach(btn => {
        btn.addEventListener('click', function() {
            const title = this.dataset.title;
            const duration = parseInt(this.dataset.duration);

            quickTitleInput.value = title;
            selectQuickDuration(duration);

            quickModal.querySelectorAll('.quick-event-template').forEach(b =>
                b.classList.remove('border-green-500', 'bg-green-50'));
            this.classList.add('border-green-500', 'bg-green-50');
        });
    });

    function selectQuickDuration(duration) {
        quickModal.querySelectorAll('.duration-btn').forEach(btn => {
            const isSelected = parseInt(btn.dataset.duration) === duration;
            btn.classList.toggle('bg-green-500', isSelected);
            btn.classList.toggle('text-white', isSelected);
            btn.setAttribute('aria-checked', isSelected ? 'true' : 'false');
        });
        document.getElementById('quick_duration').value = duration;
        updateDurationDisplay(duration);
    }

    function updateDurationDisplay(duration) {
        const displayElement = document.getElementById('current-duration-display');
        if (displayElement) {
            const label = duration < 60 ? duration + ' min' : (duration / 60) + ' h';
            displayElement.textContent = label;
        }
    }

    quickModal.querySelectorAll('.duration-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            selectQuickDuration(parseInt(this.dataset.duration));
        });
    });

    // Add 5 minutes button functionality
    const addFiveBtn = document.getElementById('add-five-minutes');
    if (addFiveBtn) {
        addFiveBtn.addEventListener('click', function() {
            const currentDuration = parseInt(document.getElementById('quick_duration').value) || 15;
            const newDuration = currentDuration + 5;

            // Clear existing button selections
            quickModal.querySelectorAll('.duration-btn').forEach(btn => {
                btn.classList.remove('bg-green-500', 'text-white');
                btn.classList.add('border-gray-300');
                btn.setAttribute('aria-checked', 'false');
            });

            document.getElementById('quick_duration').value = newDuration;
            updateDurationDisplay(newDuration);
        });
    }

    const quickTimeInput = document.getElementById('quick_start_time');
    const quickDaySelect = document.getElementById('quick_day_select');
    const conflictWarningDiv = document.getElementById('time-conflict-warning');
    let quickConflictCheckTimeout;

    quickTimeInput.addEventListener('input', function() {
        clearTimeout(quickConflictCheckTimeout);
        quickConflictCheckTimeout = setTimeout(() => checkQuickTimeConflict(), 500);
    });

    // Handle day selection change
    if (quickDaySelect) {
        quickDaySelect.addEventListener('change', function() {
            // Clear time conflict warning when changing days
            hideQuickConflictWarning();

            // Re-check time conflicts if start time is already set
            if (quickTimeInput.value) {
                clearTimeout(quickConflictCheckTimeout);
                quickConflictCheckTimeout = setTimeout(() => checkQuickTimeConflict(), 500);
            }
        });
    }

    function checkQuickTimeConflict() {
        const startTime = quickTimeInput.value;
        const duration = parseInt(document.getElementById('quick_duration').value);
        const dayNumber = parseInt(quickDaySelect?.value) || 1;

        if (!startTime || !duration) return;

        fetch(cm_ajax.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'cm_check_time_conflict_extended',
                event_id: <?php echo $event->get_id(); ?>,
                start_time: startTime,
                duration_minutes: duration,
                day_number: dayNumber,
                nonce: cm_ajax.ajax_nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.has_conflict) {
                showQuickConflictWarning(data.data.message);
            } else {
                hideQuickConflictWarning();
            }
        })
        .catch(err => console.warn('Quick conflict check failed:', err));
    }

    function showQuickConflictWarning(message) {
        conflictWarningDiv.classList.remove('hidden');
        document.getElementById('conflict-message').textContent = message;
    }

    function hideQuickConflictWarning() {
        conflictWarningDiv.classList.add('hidden');
    }

    quickForm.addEventListener('submit', function(e) {
        e.preventDefault();

        clearQuickErrors();

        const title = quickTitleInput.value.trim();
        const startTime = quickTimeInput.value;
        const duration = document.getElementById('quick_duration').value;
        const dayNumber = parseInt(quickDaySelect?.value) || 1;

        let hasErrors = false;

        if (!title) {
            showQuickFieldError('quick_title', 'Nazwa wydarzenia jest wymagana');
            hasErrors = true;
        }

        if (!startTime) {
            showQuickFieldError('quick_start_time', 'Godzina rozpoczęcia jest wymagana');
            hasErrors = true;
        }

        if (hasErrors) return;

        const submitBtn = document.getElementById('submit-quick-event');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Dodawanie...';

        const formData = new FormData();
        formData.append('action', 'cm_save_quick_event');
        formData.append('event_id', <?php echo $event->get_id(); ?>);
        formData.append('title', title);
        formData.append('start_time', startTime);
        formData.append('duration_minutes', duration);
        formData.append('day_number', dayNumber);
        formData.append('nonce', cm_ajax.ajax_nonce);

        fetch(cm_ajax.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                insertNewLineupItem(data.data.html, data.data.lineup);
                closeQuickModal();
                showQuickNotice('success', data.data.message);

                // Update timeline if provided (for subsequent events time updates)
                if (data.data.timeline) {
                    updateTimelineDisplay(data.data.timeline);
                }

                reinitializeSortable();
            } else {
                showQuickNotice('error', data.data || 'Nieznany błąd');
            }
        })
        .catch(err => {
            showQuickNotice('error', 'Błąd sieciowy podczas dodawania wydarzenia');
            console.error('Quick submit error:', err);
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });

    function showQuickFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorDiv = document.getElementById(fieldId + '_error');

        field.classList.add('border-red-500');
        field.setAttribute('aria-invalid', 'true');

        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.classList.remove('hidden');
        }
    }

    function clearQuickErrors() {
        quickForm.querySelectorAll('.border-red-500').forEach(el => {
            el.classList.remove('border-red-500');
            el.setAttribute('aria-invalid', 'false');
        });
        quickForm.querySelectorAll('[id$="_error"]').forEach(el => {
            el.classList.add('hidden');
            el.textContent = '';
        });
    }

    function insertNewLineupItem(html, lineupData) {
        const container = document.getElementById('lineup-sortable');
        if (!container) return;

        const existingItems = Array.from(container.children);
        let insertBefore = null;

        for (let item of existingItems) {
            const itemOrder = parseInt(item.dataset.sortOrder || '999');
            if (itemOrder > lineupData.sort_order) {
                insertBefore = item;
                break;
            }
        }

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        const newElement = tempDiv.firstElementChild;

        if (insertBefore) {
            container.insertBefore(newElement, insertBefore);
        } else {
            container.appendChild(newElement);
        }

        newElement.classList.add('ring-2', 'ring-green-500');
        setTimeout(() => {
            newElement.classList.remove('ring-2', 'ring-green-500');
        }, 2000);
    }

    function reinitializeSortable() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.sortable) {
            jQuery('#lineup-sortable').sortable('destroy');
            jQuery('#lineup-sortable').sortable({
                handle: '.cursor-move',
                placeholder: 'ui-sortable-placeholder',
                update: function(event, ui) {
                    var lineupOrder = jQuery(this).sortable('toArray', {attribute: 'data-lineup-id'});
                    updateLineupOrder(lineupOrder);
                }
            });
        }
    }

    function updateLineupOrder(lineupOrder) {
        // Also update the old jQuery version to ensure compatibility
        jQuery.ajax({
            url: cm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cm_update_lineup_order',
                lineup_items: lineupOrder,
                nonce: cm_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data && response.data.timeline) {
                    updateTimelineDisplay(response.data.timeline);
                    console.log('Timeline updated after DnD sort');
                }
            },
            error: function() {
                console.warn('DnD order update failed');
            }
        });
    }

    function updateTimelineDisplay(timeline) {
        timeline.forEach(item => {
            const element = document.querySelector(`[data-lineup-id="${item.id}"] .text-lg.font-bold`);
            if (element) {
                element.textContent = item.start_time;
            }
        });
    }

    function showQuickNotice(type, message) {
        if (window.CMAdmin && window.CMAdmin.showNotice) {
            window.CMAdmin.showNotice(type, message);
        } else {
            const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            console.log(`${type}: ${message}`);
        }
    }

    // MULTI-DAY FUNCTIONALITY
    initDayTabs();

    function initDayTabs() {
        // Initialize day tab switching
        document.querySelectorAll('.day-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const dayNumber = parseInt(this.dataset.day);
                switchDay(dayNumber);
            });
        });

        // Add day button
        const addDayBtn = document.getElementById('add-day-btn');
        if (addDayBtn) {
            addDayBtn.addEventListener('click', addNewDay);
        }

        // Delete day buttons
        document.querySelectorAll('.delete-day-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const dayNumber = parseInt(this.dataset.day);
                deleteDay(dayNumber);
            });
        });
    }

    function switchDay(dayNumber) {
        // Update active tab
        document.querySelectorAll('.day-tab').forEach(tab => {
            const isActive = parseInt(tab.dataset.day) === dayNumber;
            tab.classList.toggle('border-blue-500', isActive);
            tab.classList.toggle('text-blue-600', isActive);
            tab.classList.toggle('border-transparent', !isActive);
            tab.classList.toggle('text-gray-500', !isActive);
        });

        // Check if we need to load content via AJAX
        const contentDiv = document.querySelector('.day-content');
        const currentDay = parseInt(contentDiv?.getAttribute('data-day')) || 1;

        // Only load via AJAX if switching to a different day than currently displayed
        if (dayNumber !== currentDay) {
            loadDayContent(dayNumber);
        }
    }

    function loadDayContent(dayNumber) {
        const contentDiv = document.querySelector('.day-content');
        if (!contentDiv) return;

        // Show loading state
        contentDiv.innerHTML = '<div class="text-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div><p class="mt-2 text-gray-600">Ładowanie...</p></div>';

        // Load day content via AJAX
        fetch(cm_ajax.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'cm_load_day_content',
                event_id: <?php echo $event->get_id(); ?>,
                day_number: dayNumber,
                nonce: cm_ajax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                contentDiv.innerHTML = data.data.html;
                contentDiv.setAttribute('data-day', dayNumber);
                // Re-initialize functionality for the new content
                reinitializeEventHandlers();
                reinitializeSortable();
            } else {
                contentDiv.innerHTML = '<div class="text-center py-8 text-red-600">Błąd: ' + (data.data || 'Nie udało się załadować zawartości dnia') + '</div>';
            }
        })
        .catch(err => {
            contentDiv.innerHTML = '<div class="text-center py-8 text-red-600">Błąd połączenia z serwerem</div>';
            console.error('Day content load error:', err);
        });
    }

    function addNewDay() {
        const eventId = <?php echo $event->get_id(); ?>;
        const addBtn = document.getElementById('add-day-btn');

        if (addBtn.disabled) return;
        addBtn.disabled = true;
        addBtn.textContent = 'Dodawanie...';

        fetch(cm_ajax.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'cm_add_event_day',
                event_id: eventId,
                nonce: cm_ajax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tabsContainer = document.querySelector('.cm-days-tabs .flex');
                const tabsHtml = data.data.tabs_html;

                // If this is the second day, we need to insert all tabs (day 1 + day 2)
                if (data.data.is_second_day) {
                    // Insert all tabs before the add button
                    addBtn.insertAdjacentHTML('beforebegin', tabsHtml);
                } else {
                    // Just add the new day tab
                    addBtn.insertAdjacentHTML('beforebegin', tabsHtml);
                }

                // Re-initialize tab event listeners
                initDayTabs();

                // Switch to the new day
                switchDay(data.data.day_number);

                // Hide add button if we reached max days (7)
                if (data.data.day_number >= 7) {
                    addBtn.style.display = 'none';
                }

                showQuickNotice('success', data.data.message || 'Dodano nowy dzień');
            } else {
                showQuickNotice('error', data.data || 'Nie udało się dodać dnia');
            }
        })
        .catch(err => {
            showQuickNotice('error', 'Błąd podczas dodawania dnia');
            console.error('Add day error:', err);
        })
        .finally(() => {
            addBtn.disabled = false;
            addBtn.textContent = '+ Dodaj kolejny dzień';
        });
    }

    function deleteDay(dayNumber) {
        const eventId = <?php echo $event->get_id(); ?>;

        if (!confirm(`Czy na pewno chcesz usunąć Dzień ${dayNumber}? To działanie jest nieodwracalne.`)) {
            return;
        }

        fetch(cm_ajax.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'cm_delete_event_day',
                event_id: eventId,
                day_number: dayNumber,
                nonce: cm_ajax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showQuickNotice('success', data.data.message || 'Dzień został usunięty');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showQuickNotice('error', data.data || 'Nie udało się usunąć dnia');
            }
        })
        .catch(err => {
            showQuickNotice('error', 'Błąd podczas usuwania dnia');
            console.error('Delete day error:', err);
        });
    }

    function reinitializeEventHandlers() {
        // Re-attach event handlers for dynamically loaded content
        document.querySelectorAll('.cm-edit-lineup-item').forEach(button => {
            button.removeEventListener('click', handleEditLineupItem);
            button.addEventListener('click', handleEditLineupItem);
        });

        document.querySelectorAll('.cm-delete-lineup-item').forEach(button => {
            button.removeEventListener('click', handleDeleteLineupItem);
            button.addEventListener('click', handleDeleteLineupItem);
        });

        document.querySelectorAll('.cm-start-presentation').forEach(button => {
            button.removeEventListener('click', handleStartPresentation);
            button.addEventListener('click', handleStartPresentation);
        });
    }

    function handleEditLineupItem(event) {
        const lineupId = this.dataset.lineupId;
        // Copy the existing edit lineup logic
        jQuery.ajax({
            url: cm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cm_get_lineup_item',
                lineup_id: lineupId,
                nonce: cm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    document.getElementById('lineup-modal-title').textContent = 'Edytuj prezentację';
                    document.getElementById('lineup_id').value = data.id;
                    document.getElementById('lineup_title').value = data.title || '';
                    document.getElementById('lineup_presenter').value = data.presenter || '';
                    document.getElementById('lineup_start_time').value = data.start_time || '';
                    document.getElementById('lineup_duration').value = data.duration_minutes || '';
                    document.getElementById('lineup_description').value = data.description || '';
                    document.getElementById('lineup_quiz_id').value = data.quiz_id || '';
                    document.getElementById('lineup_day_select').value = data.day_number || 1;
                    document.getElementById('lineup_day_number').value = data.day_number || 1;

                    const modal = document.getElementById('lineup-item-modal');
                    modal.style.display = 'flex';
                    modal.classList.remove('hidden');
                } else {
                    alert('Błąd podczas ładowania danych: ' + response.data);
                }
            }
        });
    }

    function handleDeleteLineupItem(event) {
        const lineupId = this.dataset.lineupId;
        if (!confirm('Czy na pewno chcesz usunąć tę prezentację?')) {
            return;
        }

        jQuery.ajax({
            url: cm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cm_delete_lineup_item',
                lineup_id: lineupId,
                nonce: cm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Reload current day content instead of full page
                    const currentDay = parseInt(document.querySelector('.day-content').getAttribute('data-day')) || 1;
                    loadDayContent(currentDay);
                } else {
                    alert('Błąd: ' + response.data);
                }
            }
        });
    }

    function handleStartPresentation(event) {
        const lineupId = this.dataset.lineupId;
        const eventId = <?php echo $event->get_id(); ?>;

        if (!confirm('Czy na pewno chcesz uruchomić tę prezentację? Inne prezentacje zostaną zatrzymane.')) {
            return;
        }

        jQuery.ajax({
            url: cm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cm_start_presentation',
                event_id: eventId,
                lineup_id: lineupId,
                nonce: cm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Reload current day content to show active state
                    const currentDay = parseInt(document.querySelector('.day-content').getAttribute('data-day')) || 1;
                    loadDayContent(currentDay);
                } else {
                    alert('Błąd: ' + response.data);
                }
            }
        });
    }

    // Handle clear time cache button
    const clearCacheBtn = document.getElementById('clear-time-cache');
    if (clearCacheBtn) {
        clearCacheBtn.addEventListener('click', function() {
            if (!confirm('Czy na pewno chcesz wyczyścić cache godzin rozpoczęcia prezentacji? To może rozwiązać problemy z walidacją godzin.')) {
                return;
            }

            const originalText = this.textContent;
            this.disabled = true;
            this.innerHTML = '<svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Czyszczenie...';

            fetch(cm_ajax.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cm_clear_time_cache',
                    event_id: <?php echo $event->get_id(); ?>,
                    nonce: cm_ajax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showQuickNotice('success', 'Cache godzin został wyczyszczony pomyślnie');

                    // Clear local storage as well
                    if (typeof localStorage !== 'undefined') {
                        localStorage.removeItem('cm_lineup_times_<?php echo $event->get_id(); ?>');
                        localStorage.removeItem('cm_used_times_<?php echo $event->get_id(); ?>');
                    }

                    // Trigger page refresh after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showQuickNotice('error', data.data || 'Nie udało się wyczyścić cache');
                }
            })
            .catch(err => {
                console.error('Cache clear error:', err);
                showQuickNotice('error', 'Wystąpił błąd podczas czyszczenia cache');
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Wyczyść cache godzin';
            });
        });
    }
});
</script>