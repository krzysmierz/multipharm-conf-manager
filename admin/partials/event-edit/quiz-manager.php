<?php

/**
 * Quiz manager template
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/admin/partials/event-edit
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$quizzes = CM_Quiz::get_by_event($event->get_id());
?>

<div class="space-y-8">
    <div class="flex items-center justify-between bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h2 class="text-2xl font-bold text-gray-900">Zarządzanie quizami</h2>
        <button type="button" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-sm" id="add-quiz">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Dodaj quiz
        </button>
    </div>
    
    <div class="space-y-6">
        <?php if (empty($quizzes)): ?>
            <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl p-12 text-center">
                <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Brak quizów</h3>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">Dodaj pierwszy quiz, aby umożliwić uczestnikom interakcję podczas wydarzenia.</p>
                <button type="button" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-sm" id="add-first-quiz">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Dodaj pierwszy quiz
                </button>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($quizzes as $quiz): ?>
                    <?php $quiz_obj = new CM_Quiz($quiz->id); ?>
                    <?php $questions = $quiz_obj->get_questions(); ?>
                    
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200 p-6 <?php echo $quiz->is_active ? 'ring-2 ring-green-500 bg-green-50' : ''; ?> relative" 
                         data-quiz-id="<?php echo $quiz->id; ?>">
                        
                        <div class="flex flex-col lg:flex-row lg:items-start gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900"><?php echo esc_html($quiz->title); ?></h3>
                                    <?php if ($quiz->is_active): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <span class="dashicons dashicons-yes-alt text-sm mr-1"></span>
                                            Aktywny
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <span class="dashicons dashicons-dismiss text-sm mr-1"></span>
                                            Nieaktywny
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($quiz->description): ?>
                                    <p class="text-gray-600 mb-3"><?php echo wp_kses_post($quiz->description); ?></p>
                                <?php endif; ?>
                                
                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                                    <span class="flex items-center">
                                        <span class="dashicons dashicons-clipboard text-base mr-1"></span>
                                        <?php echo count($questions); ?> pytań
                                    </span>
                                    
                                    <?php if ($quiz->start_time): ?>
                                        <span class="flex items-center">
                                            <span class="dashicons dashicons-clock text-base mr-1"></span>
                                            <?php echo date('d.m.Y H:i', strtotime($quiz->start_time)); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-3 ml-auto">
                                <!-- Status & Main Actions -->
                                <div class="flex gap-2 items-stretch">
                                    <!-- Status Indicator -->
                                    <?php if ($quiz->is_active): ?>
                                        <div class="flex flex-col items-center justify-center px-3 py-2 text-sm font-medium bg-green-100 text-green-800 rounded-lg border border-green-200 h-16 min-w-[80px]">
                                            <svg class="w-4 h-4 mb-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z"/>
                                            </svg>
                                            <span class="text-xs font-semibold">AKTYWNY</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Toggle Status Button -->
                                    <button type="button" class="flex flex-col items-center justify-center px-3 py-2 border rounded-lg text-sm font-medium transition-colors h-16 min-w-[80px] <?php echo $quiz->is_active ? 'border-orange-300 bg-orange-50 text-orange-700 hover:bg-orange-100' : 'border-green-300 bg-green-50 text-green-700 hover:bg-green-100'; ?> cm-toggle-quiz-status" 
                                            data-quiz-id="<?php echo $quiz->id; ?>"
                                            data-active="<?php echo $quiz->is_active ? '1' : '0'; ?>">
                                        <svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                        </svg>
                                        <span class="text-xs font-semibold"><?php echo $quiz->is_active ? 'Dezaktywuj' : 'Aktywuj'; ?></span>
                                    </button>
                                    
                                    <!-- Display Mode Button (only for active quizzes) -->
                                    <?php if ($quiz->is_active): ?>
                                        <button type="button" class="flex flex-col items-center justify-center px-3 py-2 border border-purple-300 bg-purple-50 text-purple-700 hover:bg-purple-100 rounded-lg text-sm font-medium transition-colors h-16 min-w-[80px] cm-manage-display-mode" 
                                                data-quiz-id="<?php echo $quiz->id; ?>">
                                            <svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                            <span class="text-xs font-semibold">Tryb</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Secondary Actions Dropdown -->
                                <div class="relative">
                                    <button type="button" class="flex items-center justify-center p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors dropdown-toggle" data-quiz-id="<?php echo $quiz->id; ?>">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                                        </svg>
                                    </button>
                                    <div id="quiz-dropdown-<?php echo $quiz->id; ?>" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                                        <div class="py-1">
                                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center cm-edit-quiz" data-quiz-id="<?php echo $quiz->id; ?>">
                                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                                Edytuj quiz
                                            </button>
                                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center cm-manage-questions" data-quiz-id="<?php echo $quiz->id; ?>">
                                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Zarządzaj pytaniami
                                            </button>
                                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center cm-view-results" data-quiz-id="<?php echo $quiz->id; ?>">
                                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                </svg>
                                                Zobacz wyniki
                                            </button>
                                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-orange-700 hover:bg-orange-50 flex items-center cm-reset-quiz" data-quiz-id="<?php echo $quiz->id; ?>">
                                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                                Resetuj quiz
                                            </button>
                                            <hr class="my-1">
                                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50 flex items-center cm-delete-quiz" data-quiz-id="<?php echo $quiz->id; ?>">
                                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Usuń quiz
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($questions)): ?>
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <h4 class="text-sm font-medium text-gray-700 mb-3">Podgląd pytań:</h4>
                                <div class="space-y-2">
                                    <?php foreach (array_slice($questions, 0, 3) as $question): ?>
                                        <div class="flex items-center gap-3 py-2 px-3 bg-gray-50 rounded-md border border-gray-200">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                                <?php 
                                                switch($question->question_type) {
                                                    case 'single': echo 'bg-blue-100 text-blue-800'; break;
                                                    case 'multiple': echo 'bg-yellow-100 text-yellow-800'; break;
                                                    case 'text': echo 'bg-purple-100 text-purple-800'; break;
                                                }
                                                ?>">
                                                <?php 
                                                switch($question->question_type) {
                                                    case 'single': echo 'Jednokrotny wybór'; break;
                                                    case 'multiple': echo 'Wielokrotny wybór'; break;
                                                    case 'text': echo 'Tekst'; break;
                                                }
                                                ?>
                                            </span>
                                            <span class="text-sm text-gray-700"><?php echo esc_html(wp_trim_words($question->question, 10)); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($questions) > 3): ?>
                                        <div class="text-sm text-gray-500 italic pl-3">
                                            ... i <?php echo count($questions) - 3; ?> więcej
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Add/Edit Quiz Modal -->
    <div id="quiz-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 id="quiz-modal-title" class="text-xl font-semibold text-gray-900">Dodaj quiz</h3>
                <button type="button" class="text-gray-400 hover:text-gray-600 transition-colors cm-modal-close">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="quiz-form" class="cm-ajax-form" data-action="cm_save_quiz">
                <input type="hidden" name="event_id" value="<?php echo esc_attr($event->get_id()); ?>">
                <input type="hidden" name="quiz_id" id="quiz_id" value="">
                
                <div class="p-6 space-y-6">
                    <div>
                        <label for="quiz_title" class="block text-sm font-medium text-gray-700 mb-2">
                            Tytuł quizu *
                        </label>
                        <input type="text" id="quiz_title" name="title" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                               required>
                    </div>
                    
                    <div>
                        <label for="quiz_description" class="block text-sm font-medium text-gray-700 mb-2">
                            Opis quizu
                        </label>
                        <textarea id="quiz_description" name="description" rows="4"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="quiz_start_time" class="block text-sm font-medium text-gray-700 mb-2">
                                Czas rozpoczęcia
                            </label>
                            <input type="datetime-local" id="quiz_start_time" name="start_time" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-sm text-gray-600 mt-1">Opcjonalnie - kiedy quiz ma być automatycznie dostępny</p>
                        </div>
                        
                        <div>
                            <label for="quiz_end_time" class="block text-sm font-medium text-gray-700 mb-2">
                                Czas zakończenia
                            </label>
                            <input type="datetime-local" id="quiz_end_time" name="end_time" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-sm text-gray-600 mt-1">Opcjonalnie - kiedy quiz ma być automatycznie zamknięty</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="quiz_is_active" name="is_active" value="1" 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="quiz_is_active" class="ml-3 text-sm font-medium text-gray-700">
                            Quiz jest aktywny i dostępny dla uczestników
                        </label>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 p-6 border-t border-gray-200 bg-gray-50">
                    <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors cm-modal-close">
                        Anuluj
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        Zapisz quiz
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Questions Manager Modal -->
    <div id="questions-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-3xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 id="questions-modal-title" class="text-xl font-semibold text-gray-900">Zarządzanie pytaniami</h3>
                <button type="button" class="text-gray-400 hover:text-gray-600 transition-colors cm-modal-close">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="p-6">
                <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
                    <div class="text-sm text-gray-600">
                        <svg class="w-5 h-5 inline-block mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Zarządzaj pytaniami dla tego quizu. Możesz dodawać, edytować i usuwać pytania.
                    </div>
                    <button type="button" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-sm" id="add-question">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Dodaj pytanie
                    </button>
                </div>
                
                <div id="questions-list" class="space-y-4 min-h-[200px]">
                    <!-- Questions will be loaded here via AJAX -->
                </div>
            </div>
            
            <div class="flex justify-end gap-3 p-6 border-t border-gray-200 bg-gray-50">
                <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors cm-modal-close">
                    Zamknij
                </button>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Question Modal -->
    <div id="question-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-3xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 id="question-modal-title" class="text-xl font-semibold text-gray-900">Dodaj pytanie</h3>
                <button type="button" class="text-gray-400 hover:text-gray-600 transition-colors cm-modal-close">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="question-form" class="cm-ajax-form" data-action="cm_save_quiz_question">
                <input type="hidden" name="quiz_id" id="question_quiz_id" value="">
                <input type="hidden" name="question_id" id="question_id" value="">
                
                <div class="p-6 space-y-6">
                    <div>
                        <label for="question_text" class="block text-sm font-medium text-gray-700 mb-2">
                            Pytanie *
                        </label>
                        <textarea id="question_text" name="question" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                  required placeholder="Wprowadź treść pytania..."></textarea>
                    </div>
                    
                    <div>
                        <label for="question_type" class="block text-sm font-medium text-gray-700 mb-2">
                            Typ pytania *
                        </label>
                        <select id="question_type" name="question_type" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                required>
                            <option value="">Wybierz typ pytania</option>
                            <option value="single">Jednokrotny wybór</option>
                            <option value="multiple">Wielokrotny wybór</option>
                            <option value="text">Odpowiedź tekstowa</option>
                        </select>
                    </div>
                    
                    <div id="answers-section" class="hidden">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <h4 class="text-lg font-semibold text-gray-900 mb-2">Odpowiedzi</h4>
                            <p class="text-sm text-blue-800">Zaznacz poprawne odpowiedzi. Dla pytań wielokrotnego wyboru możesz zaznaczyć więcej niż jedną odpowiedź.</p>
                        </div>
                        <div id="answers-list" class="space-y-3 mb-4">
                            <!-- Answers will be added here dynamically -->
                        </div>
                        <button type="button" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors shadow-sm" id="add-answer-btn">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Dodaj odpowiedź
                        </button>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 p-6 border-t border-gray-200 bg-gray-50">
                    <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors cm-modal-close">
                        Anuluj
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        Zapisz pytanie
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Quiz Results Modal -->
    <div id="quiz-results-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-3xl max-h-[90vh] overflow-y-auto">
            <!-- Compact header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-4 sm:px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2 sm:space-x-3">
                        <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 id="quiz-results-modal-title" class="text-lg sm:text-xl font-bold text-white">Wyniki Quizu</h3>
                            <p class="text-blue-100 text-xs sm:text-sm hidden sm:block">Analiza odpowiedzi uczestników</p>
                        </div>
                    </div>
                    <button type="button" class="text-white/80 hover:text-white hover:bg-white/20 p-1.5 sm:p-2 rounded-lg transition-colors cm-modal-close">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Scrollable content area -->
            <div class="overflow-y-auto" style="max-height: calc(98vh - 140px);">
                <div id="quiz-results-content" class="p-3 sm:p-6">
                    <div class="flex items-center justify-center py-12 sm:py-16">
                        <div class="text-center">
                            <div class="relative inline-flex items-center justify-center mb-4">
                                <div class="animate-spin rounded-full h-10 w-10 sm:h-12 sm:w-12 border-3 border-blue-200"></div>
                                <div class="animate-spin rounded-full h-10 w-10 sm:h-12 sm:w-12 border-t-3 border-blue-600 absolute inset-0"></div>
                            </div>
                            <p class="text-gray-600 text-base sm:text-lg font-medium">Ładowanie wyników...</p>
                            <p class="text-gray-400 text-sm mt-1">Analizowanie danych</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Compact footer -->
            <div class="border-t border-gray-100 bg-gray-50 px-3 sm:px-6 py-3 sm:py-4">
                <div class="flex flex-col-reverse sm:flex-row sm:justify-between sm:items-center gap-3">
                    <button type="button" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors text-sm" id="refresh-quiz-results">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Odśwież
                    </button>
                    <div class="flex gap-2 sm:gap-3">
                        <button type="button" class="flex-1 sm:flex-none inline-flex items-center justify-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors text-sm" id="export-quiz-results">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Eksport
                        </button>
                        <button type="button" class="flex-1 sm:flex-none px-4 py-2 text-gray-700 font-medium bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-sm cm-modal-close">
                            Zamknij
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Display Mode Management Modal -->
    <div id="display-mode-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-3xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 id="display-mode-modal-title" class="text-xl font-semibold text-gray-900">Zarządzanie trybem wyświetlania</h3>
                <button type="button" class="text-gray-400 hover:text-gray-600 transition-colors cm-modal-close">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="p-6 space-y-8">
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <svg class="w-6 h-6 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <h4 class="text-lg font-semibold text-gray-900">Aktualny tryb wyświetlania</h4>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-800 rounded-lg font-medium" id="current-mode-badge">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span id="current-mode-text">Sprawdzanie...</span>
                        </div>
                        <div class="text-gray-600 italic" id="current-mode-description">
                            Ładowanie informacji o trybie...
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="flex items-center mb-6">
                        <svg class="w-6 h-6 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                        </svg>
                        <h4 class="text-lg font-semibold text-gray-900">Zmień tryb wyświetlania</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="mode-option border-2 border-gray-200 rounded-xl p-6 hover:border-blue-500 hover:bg-blue-50 transition-all cursor-pointer" data-mode="qr">
                            <div class="flex flex-col items-center text-center">
                                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mb-4 text-white">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                    </svg>
                                </div>
                                <h5 class="text-lg font-semibold text-gray-900 mb-2">Kod QR</h5>
                                <p class="text-gray-600">Wyświetla kod QR umożliwiający uczestnikom dołączenie do quizu</p>
                            </div>
                        </div>
                        
                        <div class="mode-option border-2 border-gray-200 rounded-xl p-6 hover:border-green-500 hover:bg-green-50 transition-all cursor-pointer" data-mode="results">
                            <div class="flex flex-col items-center text-center">
                                <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-teal-600 rounded-full flex items-center justify-center mb-4 text-white">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <h5 class="text-lg font-semibold text-gray-900 mb-2">Wyniki na żywo</h5>
                                <p class="text-gray-600">Pokazuje wyniki quizu w czasie rzeczywistym</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-orange-50 border border-orange-200 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <svg class="w-6 h-6 text-orange-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h4 class="text-lg font-semibold text-gray-900">Automatyczne przełączanie</h4>
                    </div>
                    <div class="space-y-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" id="auto-switch-enabled" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <span class="ml-3 text-sm font-medium text-gray-700">Włącz automatyczne przełączanie na wyniki</span>
                        </label>
                        
                        <div id="auto-switch-delay-container" class="hidden bg-white border border-orange-300 rounded-lg p-4">
                            <label for="auto-switch-delay" class="block text-sm font-medium text-gray-700 mb-2">Opóźnienie (sekundy):</label>
                            <input type="number" id="auto-switch-delay" min="5" max="300" value="30" 
                                   class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-sm text-gray-600 mt-2">Po tylu sekundach od rozpoczęcia quizu automatycznie przełączy na tryb wyników</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <svg class="w-6 h-6 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <h4 class="text-lg font-semibold text-gray-900">Podgląd na żywo</h4>
                    </div>
                    <div class="space-y-3">
                        <button type="button" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors shadow-sm" id="open-live-preview">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            Otwórz podgląd w nowej karcie
                        </button>
                        <p class="text-sm text-gray-600">Podgląd pokazuje aktualny tryb wyświetlania tak, jak widzi go uczestnik</p>
                    </div>
                </div>
                
                <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <svg class="w-6 h-6 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <h4 class="text-lg font-semibold text-gray-900">Statystyki połączeń</h4>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white rounded-lg p-4 text-center shadow-sm">
                            <div class="text-2xl font-bold text-green-600 mb-1" id="active-connections">-</div>
                            <div class="text-sm text-gray-600">Aktywne połączenia</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 text-center shadow-sm">
                            <div class="text-2xl font-bold text-green-600 mb-1" id="total-participants">-</div>
                            <div class="text-sm text-gray-600">Uczestnicy w quizie</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-between items-center p-6 border-t border-gray-200 bg-gray-50">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-sm" id="toggle-quiz-mode">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                    </svg>
                    Przełącz tryb
                </button>
                <div class="flex gap-3">
                    <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors shadow-sm" id="refresh-mode-status">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Odśwież status
                    </button>
                    <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors cm-modal-close">
                        Zamknij
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Essential styles for legacy compatibility and dropdown functionality */
.hidden {
    display: none !important;
}

/* Answer item styles for question modal */
.answer-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 12px;
}

.answer-item input[type="text"] {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.answer-item input[type="radio"],
.answer-item input[type="checkbox"] {
    width: 16px;
    height: 16px;
}

.remove-answer-btn {
    background: #ef4444;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.remove-answer-btn:hover {
    background: #dc2626;
}

/* Questions list styles for questions modal */
.cm-questions-list .cm-question-item {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 16px;
    background: white;
    transition: all 0.2s;
}

.cm-questions-list .cm-question-item:hover {
    border-color: #3b82f6;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
}

.cm-questions-list .cm-question-item h4 {
    margin: 0 0 12px 0;
    color: #1f2937;
    font-size: 16px;
    font-weight: 600;
}

.cm-questions-list .question-type {
    display: inline-block;
    padding: 4px 10px;
    background: #3b82f6;
    color: white;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    margin-right: 10px;
    text-transform: uppercase;
}

.cm-questions-list .question-actions {
    margin-top: 12px;
    display: flex;
    gap: 8px;
}

.cm-empty-questions {
    text-align: center;
    color: #6b7280;
    font-style: italic;
    padding: 40px 20px;
    background: #f9fafb;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
}

/* Loading spinner */
.cm-loading-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    padding: 40px;
}

/* Mode option active state */
.mode-option.active {
    border-color: #10b981 !important;
    background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal functionality
    const quizModal = document.getElementById('quiz-modal');
    const questionsModal = document.getElementById('questions-modal');
    const questionModal = document.getElementById('question-modal');
    const resultsModal = document.getElementById('quiz-results-modal');
    const displayModeModal = document.getElementById('display-mode-modal');
    const addButtons = document.querySelectorAll('#add-quiz, #add-first-quiz');
    let resultsRefreshInterval;
    
    // Show modal function
    function showModal(modal) {
        modal.style.display = 'flex';
        modal.classList.remove('hidden');
    }
    
    // Hide modal function
    function hideModal(modal) {
        modal.style.display = 'none';
        modal.classList.add('hidden');
    }
    
    // Dropdown toggle function
    function toggleQuizDropdown(quizId) {
        const dropdown = document.getElementById('quiz-dropdown-' + quizId);
        if (!dropdown) {
            console.error('Dropdown not found for quiz ID:', quizId);
            return;
        }
        
        const allDropdowns = document.querySelectorAll('[id^="quiz-dropdown-"]');
        
        // Close all other dropdowns
        allDropdowns.forEach(dd => {
            if (dd !== dropdown) {
                dd.classList.add('hidden');
            }
        });
        
        // Toggle current dropdown
        dropdown.classList.toggle('hidden');
    }
    
    // Make function available globally for compatibility
    window.toggleQuizDropdown = toggleQuizDropdown;
    
    // Dropdown toggle using event delegation
    document.addEventListener('click', function(e) {
        if (e.target.matches('.dropdown-toggle') || e.target.closest('.dropdown-toggle')) {
            e.preventDefault();
            e.stopPropagation();
            const button = e.target.matches('.dropdown-toggle') ? e.target : e.target.closest('.dropdown-toggle');
            const quizId = button.dataset.quizId;
            if (quizId) {
                toggleQuizDropdown(quizId);
            }
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        // Don't close if clicking on dropdown toggle button
        if (e.target.matches('.dropdown-toggle') || e.target.closest('.dropdown-toggle')) {
            return;
        }
        
        // Close all dropdowns if clicking outside
        if (!e.target.closest('[id^="quiz-dropdown-"]') && !e.target.closest('.relative')) {
            document.querySelectorAll('[id^="quiz-dropdown-"]').forEach(dropdown => {
                dropdown.classList.add('hidden');
            });
        }
    });
    
    // Add quiz modal
    addButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('quiz-modal-title').textContent = 'Dodaj quiz';
            document.getElementById('quiz-form').reset();
            document.getElementById('quiz_id').value = '';
            showModal(quizModal);
        });
    });
    
    // Close modals
    document.querySelectorAll('.cm-modal-close').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.fixed.inset-0.z-50').forEach(modal => {
                hideModal(modal);
            });
            
            // Clear auto-refresh interval when closing results modal
            if (resultsRefreshInterval) {
                clearInterval(resultsRefreshInterval);
                resultsRefreshInterval = null;
            }
        });
    });
    
    // Close modal when clicking backdrop
    document.querySelectorAll('.fixed.inset-0.z-50').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                hideModal(modal);
            }
        });
    });
    
    // Toggle quiz status - using event delegation
    document.addEventListener('click', function(e) {
        if (e.target.matches('.cm-toggle-quiz-status') || e.target.closest('.cm-toggle-quiz-status')) {
            const button = e.target.matches('.cm-toggle-quiz-status') ? e.target : e.target.closest('.cm-toggle-quiz-status');
            const quizId = button.dataset.quizId;
            const isActive = button.dataset.active === '1';
            const newStatus = !isActive;
            
            jQuery.ajax({
                url: cm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cm_toggle_quiz_status',
                    quiz_id: quizId,
                    is_active: newStatus ? 1 : 0,
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
        }
    });
    
    // Manage questions - using event delegation
    document.addEventListener('click', function(e) {
        if (e.target.matches('.cm-manage-questions') || e.target.closest('.cm-manage-questions')) {
            const button = e.target.matches('.cm-manage-questions') ? e.target : e.target.closest('.cm-manage-questions');
            const quizId = button.dataset.quizId;
            currentQuizId = quizId; // Set the global currentQuizId
            document.getElementById('questions-modal-title').textContent = 'Zarządzanie pytaniami - Quiz #' + quizId;
            showModal(questionsModal);
            
            // Load questions via AJAX
            loadQuestions(quizId);
        }
    });

    // Edit quiz - using event delegation
    document.addEventListener('click', function(e) {
        if (e.target.matches('.cm-edit-quiz') || e.target.closest('.cm-edit-quiz')) {
            const button = e.target.matches('.cm-edit-quiz') ? e.target : e.target.closest('.cm-edit-quiz');
            const quizId = button.dataset.quizId;
            
            // Load quiz data and populate form
            loadQuizForEdit(quizId);
        }
    });

    // View results - using event delegation
    document.addEventListener('click', function(e) {
        if (e.target.matches('.cm-view-results') || e.target.closest('.cm-view-results')) {
            const button = e.target.matches('.cm-view-results') ? e.target : e.target.closest('.cm-view-results');
            const quizId = button.dataset.quizId;
            
            // Show results in modal
            showQuizResultsModal(quizId);
        }
    });

    // Reset quiz - using event delegation
    document.addEventListener('click', function(e) {
        if (e.target.matches('.cm-reset-quiz') || e.target.closest('.cm-reset-quiz')) {
            const button = e.target.matches('.cm-reset-quiz') ? e.target : e.target.closest('.cm-reset-quiz');
            const quizId = button.dataset.quizId;

            if (confirm('Czy na pewno chcesz zresetować ten quiz? Wszystkie odpowiedzi uczestników zostaną usunięte. Tej operacji nie można cofnąć.')) {
                jQuery.ajax({
                    url: cm_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'cm_reset_quiz',
                        quiz_id: quizId,
                        nonce: cm_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Quiz został pomyślnie zresetowany. Wszystkie odpowiedzi uczestników zostały usunięte.');
                            location.reload();
                        } else {
                            alert('Błąd podczas resetowania quizu: ' + (response.data || 'Nieznany błąd'));
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Błąd połączenia podczas resetowania quizu.');
                        console.error('Reset quiz error:', error);
                    }
                });
            }
        }
    });

    // Delete quiz - using event delegation
    document.addEventListener('click', function(e) {
        if (e.target.matches('.cm-delete-quiz') || e.target.closest('.cm-delete-quiz')) {
            const button = e.target.matches('.cm-delete-quiz') ? e.target : e.target.closest('.cm-delete-quiz');
            const quizId = button.dataset.quizId;

            if (confirm('Czy na pewno chcesz usunąć ten quiz? Tej operacji nie można cofnąć.')) {
                jQuery.ajax({
                    url: cm_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'cm_delete_quiz',
                        quiz_id: quizId,
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
            }
        }
    });
    
    function loadQuestions(quizId) {
        jQuery.ajax({
            url: cm_ajax.ajax_url,
            type: 'GET',
            data: {
                action: 'cm_get_quiz_questions',
                quiz_id: quizId,
                nonce: cm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    document.getElementById('questions-list').innerHTML = response.data;
                } else {
                    document.getElementById('questions-list').innerHTML = '<p>Błąd wczytywania pytań.</p>';
                }
            }
        });
    }

    function loadQuizForEdit(quizId) {
        jQuery.ajax({
            url: cm_ajax.ajax_url,
            type: 'GET',
            data: {
                action: 'cm_get_quiz_data',
                quiz_id: quizId,
                nonce: cm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const quiz = response.data;
                    // Populate form fields
                    document.getElementById('quiz_id').value = quiz.id;
                    document.getElementById('quiz_title').value = quiz.title;
                    document.getElementById('quiz_description').value = quiz.description || '';
                    document.getElementById('quiz_start_time').value = quiz.start_time || '';
                    document.getElementById('quiz_end_time').value = quiz.end_time || '';
                    document.getElementById('quiz_is_active').checked = quiz.is_active == 1;
                    
                    // Update modal title
                    document.getElementById('quiz-modal-title').textContent = 'Edytuj quiz';
                    
                    // Show modal
                    showModal(quizModal);
                } else {
                    alert('Błąd wczytywania danych quizu: ' + response.data);
                }
            }
        });
    }

    // Handle quiz form success
    jQuery('#quiz-form').on('cm:ajax:success', function(event, response) {
        if (response.data && response.data.reload) {
            // Close modal
            quizModal.style.display = 'none';
            // Reload page to show updated quiz list
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
    });

    // Question modal functionality
    let currentQuizId = null;

    // Add question button - using event delegation
    document.addEventListener('click', function(e) {
        if (e.target.matches('#add-question') || e.target.closest('#add-question')) {
            // Get the current quiz ID from the questions modal title or set it when opening questions modal
            const questionsModalTitle = document.getElementById('questions-modal-title').textContent;
            const quizIdMatch = questionsModalTitle.match(/Quiz #(\d+)/);
            if (quizIdMatch) {
                currentQuizId = quizIdMatch[1];
            }
            
            // Reset form
            document.getElementById('question-form').reset();
            document.getElementById('question_id').value = '';
            document.getElementById('question_quiz_id').value = currentQuizId;
            document.getElementById('question-modal-title').textContent = 'Dodaj pytanie';
            document.getElementById('answers-section').classList.add('hidden');
            document.getElementById('answers-list').innerHTML = '';
            
            // Show modal
            showModal(questionModal);
        }
    });

    // Edit question button - using event delegation
    document.addEventListener('click', function(e) {
        if (e.target.matches('.cm-edit-question') || e.target.closest('.cm-edit-question')) {
            const button = e.target.matches('.cm-edit-question') ? e.target : e.target.closest('.cm-edit-question');
            const questionId = button.dataset.questionId;
            
            // Load question data for editing
            loadQuestionForEdit(questionId);
        }
    });

    // Delete question button - using event delegation
    document.addEventListener('click', function(e) {
        if (e.target.matches('.cm-delete-question') || e.target.closest('.cm-delete-question')) {
            const button = e.target.matches('.cm-delete-question') ? e.target : e.target.closest('.cm-delete-question');
            const questionId = button.dataset.questionId;
            
            if (confirm('Czy na pewno chcesz usunąć to pytanie? Tej operacji nie można cofnąć.')) {
                jQuery.ajax({
                    url: cm_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'cm_delete_quiz_question',
                        question_id: questionId,
                        nonce: cm_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Reload questions list
                            loadQuestions(currentQuizId);
                        } else {
                            alert('Błąd: ' + response.data);
                        }
                    }
                });
            }
        }
    });

    // Question type change handler
    document.addEventListener('change', function(e) {
        if (e.target.id === 'question_type') {
            const questionType = e.target.value;
            const answersSection = document.getElementById('answers-section');
            
            if (questionType === 'single' || questionType === 'multiple') {
                answersSection.style.display = 'block';
                // Add default answers if none exist
                const answersList = document.getElementById('answers-list');
                if (answersList.children.length === 0) {
                    addAnswerField();
                    addAnswerField();
                }
            } else {
                answersSection.style.display = 'none';
                document.getElementById('answers-list').innerHTML = '';
            }
        }
    });

    // Add answer button
    document.addEventListener('click', function(e) {
        if (e.target.matches('#add-answer-btn') || e.target.closest('#add-answer-btn')) {
            addAnswerField();
        }
    });

    // Remove answer button - using event delegation
    document.addEventListener('click', function(e) {
        if (e.target.matches('.remove-answer-btn') || e.target.closest('.remove-answer-btn')) {
            const answerItem = e.target.closest('.answer-item');
            const answersList = document.getElementById('answers-list');
            
            if (answersList.children.length > 2) {
                answerItem.remove();
            } else {
                alert('Pytanie musi mieć przynajmniej 2 odpowiedzi.');
            }
        }
    });

    function addAnswerField() {
        const answersList = document.getElementById('answers-list');
        const answerIndex = answersList.children.length;
        const questionType = document.getElementById('question_type').value;
        const inputType = questionType === 'multiple' ? 'checkbox' : 'radio';
        const inputName = questionType === 'multiple' ? 'correct_answers[]' : 'correct_answer';
        
        const answerHtml = `
            <div class="answer-item" style="display: flex; align-items: center; margin-bottom: 10px;">
                <input type="${inputType}" name="${inputName}" value="${answerIndex}" style="margin-right: 10px;">
                <input type="text" name="answers[${answerIndex}][text]" placeholder="Tekst odpowiedzi" class="regular-text" required style="flex: 1; margin-right: 10px;">
                <button type="button" class="button remove-answer-btn">
                    <span class="dashicons dashicons-minus"></span>
                </button>
            </div>
        `;
        
        answersList.insertAdjacentHTML('beforeend', answerHtml);
    }

    function loadQuestionForEdit(questionId) {
        jQuery.ajax({
            url: cm_ajax.ajax_url,
            type: 'GET',
            data: {
                action: 'cm_get_question_data',
                question_id: questionId,
                nonce: cm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const question = response.data.question;
                    const answers = response.data.answers;
                    
                    // Populate form
                    document.getElementById('question_id').value = question.id;
                    document.getElementById('question_quiz_id').value = question.quiz_id;
                    document.getElementById('question_text').value = question.question;
                    document.getElementById('question_type').value = question.question_type;
                    
                    // Trigger question type change to show/hide answers
                    document.getElementById('question_type').dispatchEvent(new Event('change'));
                    
                    // Populate answers if applicable
                    if ((question.question_type === 'single' || question.question_type === 'multiple') && answers.length > 0) {
                        const answersList = document.getElementById('answers-list');
                        answersList.innerHTML = '';
                        
                        answers.forEach(function(answer, index) {
                            addAnswerField();
                            const lastAnswer = answersList.lastElementChild;
                            lastAnswer.querySelector('input[type="text"]').value = answer.answer_text;
                            if (answer.is_correct == 1) {
                                lastAnswer.querySelector('input[type="radio"], input[type="checkbox"]').checked = true;
                            }
                        });
                    }
                    
                    // Update modal title and show
                    document.getElementById('question-modal-title').textContent = 'Edytuj pytanie';
                    showModal(questionModal);
                } else {
                    alert('Błąd wczytywania danych pytania: ' + response.data);
                }
            }
        });
    }

    // Handle question form success
    jQuery('#question-form').on('cm:ajax:success', function(event, response) {
        console.log('Question form success:', response);
        if (response.success) {
            // Close modal
            questionModal.style.display = 'none';
            // Reload questions list
            if (currentQuizId) {
                loadQuestions(currentQuizId);
            }
        }
    });

    // Add debugging to form submission
    jQuery('#question-form').on('submit', function(e) {
        console.log('Question form submitted');
        const formData = new FormData(this);
        console.log('Form data:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
    });

    // Quiz results modal functionality
    function showQuizResultsModal(quizId) {
        // Set modal title
        document.getElementById('quiz-results-modal-title').textContent = `Wyniki Quizu #${quizId}`;
        
        // Show modal
        showModal(resultsModal);
        
        // Load quiz results
        loadQuizResults(quizId);
        
        // Set up auto-refresh every 30 seconds
        resultsRefreshInterval = setInterval(() => {
            if (resultsModal.style.display === 'flex') {
                loadQuizResults(quizId);
            }
        }, 30000);
    }

    function loadQuizResults(quizId) {
        const resultsContent = document.getElementById('quiz-results-content');
        
        // Show enhanced loading spinner
        resultsContent.innerHTML = `
            <div class="flex items-center justify-center py-20">
                <div class="text-center">
                    <div class="relative inline-flex items-center justify-center mb-6">
                        <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-200"></div>
                        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-blue-600 absolute inset-0"></div>
                    </div>
                    <p class="text-gray-600 text-lg font-medium">Ładowanie wyników...</p>
                    <p class="text-gray-400 text-sm mt-2">Analizowanie danych uczestników</p>
                </div>
            </div>
        `;
        
        jQuery.ajax({
            url: cm_ajax.ajax_url,
            type: 'GET',
            data: {
                action: 'cm_get_quiz_results_detailed',
                quiz_id: quizId,
                nonce: cm_ajax.nonce,
                _nocache: Date.now()
            },
            cache: false,
            success: function(response) {
                if (response.success) {
                    console.log('Received quiz results data:', response.data);
                    displayQuizResults(response.data);
                } else {
                    resultsContent.innerHTML = `
                        <div class="flex items-center justify-center py-12">
                            <div class="text-center max-w-sm px-4">
                                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"></path>
                                    </svg>
                                </div>
                                <h3 class="text-base font-semibold text-gray-900 mb-2">Błąd ładowania</h3>
                                <p class="text-red-600 text-sm mb-4">${response.data}</p>
                                <button onclick="loadQuizResults(${quizId})" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582"></path>
                                    </svg>
                                    Ponów próbę
                                </button>
                            </div>
                        </div>
                    `;
                }
            },
            error: function() {
                resultsContent.innerHTML = `
                    <div class="flex items-center justify-center py-12">
                        <div class="text-center max-w-sm px-4">
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536"></path>
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold text-gray-900 mb-2">Błąd połączenia</h3>
                            <p class="text-red-600 text-sm mb-4">Wystąpił błąd sieciowy podczas ładowania wyników.</p>
                            <button onclick="loadQuizResults(${quizId})" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582"></path>
                                </svg>
                                Ponów próbę
                            </button>
                        </div>
                    </div>
                `;
            }
        });
    }

    function displayQuizResults(data) {
        const resultsContent = document.getElementById('quiz-results-content');

        // Initialize global results data
        window.quizResultsData = data;
        window.currentPage = 1;
        window.itemsPerPage = 20;
        window.filteredParticipants = data.participants ? [...data.participants] : [];

        let html = `
            <!-- Enhanced Statistics Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-4 transition-all hover:shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-2xl font-bold text-blue-900">${data.stats.total_responses || 0}</div>
                            <div class="text-blue-600 text-sm font-medium">Odpowiedzi</div>
                        </div>
                        <div class="p-3 bg-blue-500 rounded-xl shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 border border-emerald-200 rounded-xl p-4 transition-all hover:shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-2xl font-bold text-emerald-900">${data.stats.unique_participants || 0}</div>
                            <div class="text-emerald-600 text-sm font-medium">Uczestnicy</div>
                        </div>
                        <div class="p-3 bg-emerald-500 rounded-xl shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm6 0v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-200 rounded-xl p-4 transition-all hover:shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-2xl font-bold text-amber-900">${data.stats.completion_rate || 0}%</div>
                            <div class="text-amber-600 text-sm font-medium">Ukończenie</div>
                        </div>
                        <div class="p-3 bg-amber-500 rounded-xl shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-xl p-4 transition-all hover:shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-2xl font-bold text-purple-900">${data.stats.average_score || 0}%</div>
                            <div class="text-purple-600 text-sm font-medium">Średnia</div>
                        </div>
                        <div class="p-3 bg-purple-500 rounded-xl shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-200 rounded-xl p-4 transition-all hover:shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-2xl font-bold text-indigo-900">${data.participants ? Math.max(...data.participants.map(p => p.score_percentage || 0)) : 0}%</div>
                            <div class="text-indigo-600 text-sm font-medium">Najlepszy</div>
                        </div>
                        <div class="p-3 bg-indigo-500 rounded-xl shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add enhanced winners podium if there are participants
        if (data.participants && data.participants.length > 0) {
            const sortedParticipants = data.participants.sort((a, b) => {
                // First sort by correct answers count (descending) - same as public interface
                if (b.correct_answers !== a.correct_answers) {
                    return b.correct_answers - a.correct_answers;
                }
                // Then by completion time (ascending - faster is better)
                return new Date(a.submission_time) - new Date(b.submission_time);
            });

            const topThree = sortedParticipants.slice(0, 3);
            const perfectScorers = sortedParticipants.filter(p => p.score_percentage === 100).length;
            const highScorers = sortedParticipants.filter(p => p.score_percentage >= 80).length;

            html += `
                <!-- Enhanced Winners Section -->
                <div class="mb-6 bg-gradient-to-br from-yellow-50 via-white to-yellow-50 border border-yellow-200 rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 text-yellow-500 mr-3" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            Podium Zwycięzców
                        </h3>
                        <div class="flex items-center space-x-4 text-sm">
                            <span class="flex items-center px-3 py-1 bg-green-100 text-green-800 rounded-full">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                ${perfectScorers} × 100%
                            </span>
                            <span class="flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full">
                                <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                                ${highScorers} × 80%+
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            `;

            topThree.forEach((participant, index) => {
                const badges = ['🥇', '🥈', '🥉'];
                const bgGradients = [
                    'bg-gradient-to-br from-yellow-100 to-yellow-200 border-yellow-300',
                    'bg-gradient-to-br from-gray-100 to-gray-200 border-gray-300',
                    'bg-gradient-to-br from-orange-100 to-orange-200 border-orange-300'
                ];
                const textColors = ['text-yellow-900', 'text-gray-900', 'text-orange-900'];
                const ribbonColors = ['bg-yellow-500', 'bg-gray-500', 'bg-orange-500'];
                const submissionTime = new Date(participant.submission_time);
                const timeStr = submissionTime.toLocaleString('pl-PL', {
                    day: '2-digit',
                    month: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });

                html += `
                    <div class="relative border-2 ${bgGradients[index]} rounded-xl p-5 text-center transform transition-all hover:scale-105 hover:shadow-lg">
                        <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 ${ribbonColors[index]} text-white px-4 py-1 rounded-full text-sm font-bold shadow-md">
                            ${index + 1}. miejsce
                        </div>
                        <div class="text-4xl mb-3 mt-2">${badges[index]}</div>
                        <div class="font-bold ${textColors[index]} text-lg mb-1 break-words">${participant.user_identifier}</div>
                        <div class="text-3xl font-extrabold ${textColors[index]} mb-2">${participant.score_percentage}%</div>
                        <div class="text-sm text-gray-700 mb-2">
                            <span class="font-semibold">${participant.correct_answers}</span>/<span class="font-semibold">${participant.total_possible_answers}</span> poprawnych
                        </div>
                        <div class="text-xs text-gray-600 bg-white bg-opacity-70 rounded-lg px-2 py-1">
                            ⏱️ ${timeStr}
                        </div>
                        ${participant.score_percentage === 100 ? '<div class="absolute -top-2 -right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full shadow-sm">💯 PERFECT!</div>' : ''}
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        }

        // Display participant results if available
        if (data.participants && data.participants.length > 0) {
            // Store sorted participants globally for filtering/pagination
            window.filteredParticipants = data.participants.sort((a, b) => {
                if (b.score_percentage !== a.score_percentage) {
                    return b.score_percentage - a.score_percentage;
                }
                return new Date(a.submission_time) - new Date(b.submission_time);
            });
            
            html += `
                <!-- Advanced Controls Section -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6 space-y-4 lg:space-y-0">
                        <h3 class="text-xl font-bold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Ranking Uczestników (${data.participants.length})
                        </h3>
                        <div class="flex items-center space-x-3">
                            <button onclick="exportResults('csv')" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm shadow-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Export CSV
                            </button>
                        </div>
                    </div>

                    <!-- Filters and Search -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">🔍 Wyszukaj uczestnika</label>
                            <input type="text" id="participant-search" placeholder="Wpisz nazwę uczestnika..."
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                   onkeyup="filterParticipants()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">🎯 Wynik</label>
                            <select id="score-filter" onchange="filterParticipants()"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <option value="all">Wszystkie wyniki</option>
                                <option value="perfect">100% (Perfect)</option>
                                <option value="excellent">90-99%</option>
                                <option value="good">80-89%</option>
                                <option value="average">60-79%</option>
                                <option value="poor">Poniżej 60%</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">⚡ Sortuj</label>
                            <select id="sort-filter" onchange="filterParticipants()"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <option value="score_desc">Najlepszy wynik</option>
                                <option value="score_asc">Najgorszy wynik</option>
                                <option value="time_asc">Najszybsi (czas)</option>
                                <option value="time_desc">Najwolniejsi (czas)</option>
                                <option value="name_asc">Nazwisko A-Z</option>
                                <option value="name_desc">Nazwisko Z-A</option>
                            </select>
                        </div>
                    </div>

                    <!-- Results Summary -->
                    <div id="results-summary" class="bg-gray-50 rounded-lg p-4 mb-4">
                        <div class="flex items-center justify-between text-sm text-gray-600">
                            <span>Wyświetlanie <span class="font-semibold" id="showing-count">0</span> z <span class="font-semibold" id="total-count">${data.participants.length}</span> uczestników</span>
                            <span>Elementy na stronę:
                                <select id="items-per-page" onchange="changeItemsPerPage()" class="ml-1 px-2 py-1 border border-gray-300 rounded text-sm">
                                    <option value="10">10</option>
                                    <option value="20" selected>20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </span>
                        </div>
                    </div>

                    <!-- Participants List -->
                    <div id="participants-list" class="space-y-3 min-h-[400px]">
                        <!-- Content will be populated by JavaScript -->
                    </div>

                    <!-- Pagination -->
                    <div id="pagination" class="flex items-center justify-center mt-6 space-x-2">
                        <!-- Pagination controls will be populated by JavaScript -->
                    </div>
                </div>
            `;

            // Initialize participants display immediately after setting up the HTML
            // This will be called after the HTML is inserted into the DOM
        }

        // Display question results with compact styling
        if (data.questions && data.questions.length > 0) {
            data.questions.forEach(function(question, index) {
                html += `
                    <div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm">
                        <div class="mb-3">
                            <div class="flex items-start justify-between space-x-3">
                                <div class="flex items-start space-x-2 flex-1 min-w-0">
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-100 text-blue-600 rounded-lg text-xs font-bold flex-shrink-0">
                                        ${index + 1}
                                    </span>
                                    <h4 class="text-sm font-semibold text-gray-900 leading-tight">
                                        ${question.question}
                                    </h4>
                                </div>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${question.type === 'text' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'} flex-shrink-0">
                                    ${question.type === 'text' ? 'Tekst' : 'MC'}
                                </span>
                            </div>
                        </div>
                `;
                
                if (question.type === 'text') {
                    html += `<div class="space-y-2">`;
                    if (question.responses && question.responses.length > 0) {
                        question.responses.slice(0, 3).forEach(function(response, idx) {
                            html += `
                                <div class="bg-purple-50 border border-purple-200 rounded-md p-2">
                                    <div class="flex items-start space-x-2 text-xs">
                                        <div class="w-4 h-4 bg-purple-500 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                            ${idx + 1}
                                        </div>
                                        <div class="text-purple-900 font-medium flex-1 leading-tight">${response}</div>
                                    </div>
                                </div>
                            `;
                        });
                        if (question.responses.length > 3) {
                            html += `
                                <div class="text-center text-gray-500 text-xs py-1">
                                    +${question.responses.length - 3} więcej odpowiedzi
                                </div>
                            `;
                        }
                    } else {
                        html += `
                            <div class="text-center py-4 text-gray-500">
                                <p class="text-xs">Brak odpowiedzi</p>
                            </div>
                        `;
                    }
                    html += '</div>';
                } else {
                    html += '<div class="space-y-1.5">';
                    if (question.answers && question.answers.length > 0) {
                        const maxCount = Math.max(...question.answers.map(a => a.response_count));
                        
                        question.answers.forEach(function(answer) {
                            const isCorrect = answer.is_correct;
                            const percentage = question.total_responses > 0 ? 
                                Math.round((answer.response_count / question.total_responses) * 100) : 0;
                            const barWidth = maxCount > 0 ? (answer.response_count / maxCount * 100) : 0;
                            
                            html += `
                                <div class="${isCorrect ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200'} border rounded-md p-2">
                                    <div class="flex items-center justify-between mb-1">
                                        <div class="flex items-center space-x-2 flex-1 min-w-0">
                                            ${isCorrect ? 
                                                '<div class="w-4 h-4 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0"><svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg></div>' : 
                                                '<div class="w-4 h-4 bg-gray-400 rounded-full flex-shrink-0"></div>'
                                            }
                                            <span class="text-xs font-medium ${isCorrect ? 'text-green-900' : 'text-gray-900'} truncate">${answer.answer_text}</span>
                                        </div>
                                        <div class="flex items-center space-x-1 ml-2 flex-shrink-0">
                                            <span class="text-xs font-bold ${isCorrect ? 'text-green-700' : 'text-gray-700'}">${answer.response_count}</span>
                                            <span class="text-xs text-gray-500">(${percentage}%)</span>
                                        </div>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-1">
                                        <div class="${isCorrect ? 'bg-green-500' : 'bg-blue-500'} h-1 rounded-full transition-all duration-300" style="width: ${barWidth}%"></div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    html += '</div>';
                }
                
                html += '</div>';
            });
        } else {
            if (!data.participants || data.participants.length === 0) {
                html += `
                    <div class="text-center py-12">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3"></path>
                        </svg>
                        <h3 class="text-base font-medium text-gray-900 mb-2">Brak odpowiedzi</h3>
                        <p class="text-gray-500 text-sm">Nie otrzymano jeszcze odpowiedzi na ten quiz.</p>
                    </div>
                `;
            }
        }

        if (data.participants && data.participants.length > 0) {
            html += `
                        </div>
                    </div>
                </div>
            `;
        }

        resultsContent.innerHTML = html;

        // Initialize the participants list if we have data
        if (data.participants && data.participants.length > 0) {
            setTimeout(() => {
                // Initialize participant display
                renderParticipantsList();
            }, 100);
        }

        // Add enhanced tab switching functionality
        const tabButtons = resultsContent.querySelectorAll('.tab-button');
        const tabContents = resultsContent.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');

                // Update button states with Tailwind classes
                tabButtons.forEach(btn => {
                    btn.classList.remove('bg-white', 'text-blue-600', 'shadow-sm');
                    btn.classList.add('text-gray-600', 'hover:text-gray-900');
                });
                this.classList.remove('text-gray-600', 'hover:text-gray-900');
                this.classList.add('bg-white', 'text-blue-600', 'shadow-sm');

                // Update content states
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                    if (content.id === targetTab + '-tab') {
                        content.classList.remove('hidden');
                    }
                });
            });
        });
    }

    // Function to render participants list with pagination
    function renderParticipantsList() {
        if (!window.quizResultsData || !window.filteredParticipants) {
            return;
        }

        const participants = window.filteredParticipants;
        const currentPage = window.currentPage || 1;
        const itemsPerPage = window.itemsPerPage || 20;

        // Calculate pagination
        const totalItems = participants.length;
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
        const pageParticipants = participants.slice(startIndex, endIndex);

        // Update summary
        document.getElementById('showing-count').textContent = `${startIndex + 1}-${endIndex}`;
        document.getElementById('total-count').textContent = totalItems;

        // Render participants list
        const participantsList = document.getElementById('participants-list');
        if (!participantsList) return;

        if (pageParticipants.length === 0) {
            participantsList.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <p>Brak uczestników do wyświetlenia</p>
                </div>
            `;
            return;
        }

        participantsList.innerHTML = pageParticipants.map((participant, index) => {
            const actualIndex = startIndex + index + 1;
            const submissionTime = new Date(participant.submission_time);
            const timeStr = submissionTime.toLocaleString('pl-PL', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });

            return `
                <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-8 h-8 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center font-semibold text-sm">
                                ${actualIndex}
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 text-lg">${participant.user_identifier}</div>
                                <div class="text-sm text-gray-500">ID uczestnika</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6 text-sm">
                            <div class="text-center">
                                <div class="font-semibold text-green-600 text-lg">${participant.correct_answers}/${participant.total_possible_answers}</div>
                                <div class="text-gray-500">Poprawne</div>
                            </div>
                            <div class="text-center">
                                <div class="font-semibold text-blue-600 text-lg">${participant.score_percentage}%</div>
                                <div class="text-gray-500">Wynik</div>
                            </div>
                            <div class="text-center">
                                <div class="text-gray-700">${timeStr}</div>
                                <div class="text-gray-500">Czas wysłania</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Render pagination
        renderPagination(currentPage, totalPages);
    }

    // Function to render pagination controls
    function renderPagination(currentPage, totalPages) {
        const pagination = document.getElementById('pagination');
        if (!pagination || totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }

        let paginationHTML = '';

        // Previous button
        if (currentPage > 1) {
            paginationHTML += `
                <button onclick="changePage(${currentPage - 1})"
                        class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50">
                    Poprzednia
                </button>
            `;
        }

        // Page numbers
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === currentPage;
            paginationHTML += `
                <button onclick="changePage(${i})"
                        class="px-3 py-2 text-sm font-medium ${isActive ? 'text-blue-600 bg-blue-50 border-blue-500' : 'text-gray-500 bg-white border-gray-300'} border hover:bg-gray-50">
                    ${i}
                </button>
            `;
        }

        // Next button
        if (currentPage < totalPages) {
            paginationHTML += `
                <button onclick="changePage(${currentPage + 1})"
                        class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50">
                    Następna
                </button>
            `;
        }

        pagination.innerHTML = paginationHTML;
    }

    // Pagination helper functions
    function changePage(page) {
        window.currentPage = page;
        renderParticipantsList();
    }

    function changeItemsPerPage() {
        const select = document.getElementById('items-per-page');
        window.itemsPerPage = parseInt(select.value);
        window.currentPage = 1; // Reset to first page
        renderParticipantsList();
    }

    // Export quiz results functionality
    document.addEventListener('click', function(e) {
        if (e.target.matches('#export-quiz-results')) {
            // Get current quiz ID from modal title
            const modalTitle = document.getElementById('quiz-results-modal-title').textContent;
            const quizIdMatch = modalTitle.match(/Quiz #(\d+)/);
            if (quizIdMatch) {
                const quizId = quizIdMatch[1];
                exportQuizResults(quizId);
            }
        }
    });

    // Refresh quiz results functionality
    document.addEventListener('click', function(e) {
        if (e.target.matches('#refresh-quiz-results') || e.target.closest('#refresh-quiz-results')) {
            // Get current quiz ID from modal title
            const modalTitle = document.getElementById('quiz-results-modal-title').textContent;
            const quizIdMatch = modalTitle.match(/Quiz #(\d+)/);
            if (quizIdMatch) {
                const quizId = quizIdMatch[1];
                
                // Show loading state
                const resultsContent = document.getElementById('quiz-results-content');
                resultsContent.innerHTML = `
                    <div class="cm-loading-spinner" style="text-align: center; padding: 40px;">
                        <span class="spinner is-active"></span>
                        <p>Odświeżanie wyników...</p>
                    </div>
                `;
                
                // Reload results
                loadQuizResults(quizId);
            }
        }
    });

    function exportQuizResults(quizId) {
        // Create download link
        const downloadUrl = `${cm_ajax.ajax_url}?action=cm_export_quiz_results&quiz_id=${quizId}&nonce=${cm_ajax.nonce}`;
        
        // Create temporary link and trigger download
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = `quiz_${quizId}_results.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Display Mode Management functionality
    let currentQuizIdForMode = null;
    let modeRefreshInterval = null;

    // Manage display mode button - using event delegation
    document.addEventListener('click', function(e) {
        if (e.target.matches('.cm-manage-display-mode') || e.target.closest('.cm-manage-display-mode')) {
            const button = e.target.matches('.cm-manage-display-mode') ? e.target : e.target.closest('.cm-manage-display-mode');
            const quizId = button.dataset.quizId;
            
            currentQuizIdForMode = quizId;
            document.getElementById('display-mode-modal-title').textContent = `Zarządzanie trybem wyświetlania - Quiz #${quizId}`;
            
            // Show modal and load current mode
            showModal(displayModeModal);
            loadCurrentDisplayMode(quizId);
            
            // Set up auto-refresh every 10 seconds
            modeRefreshInterval = setInterval(() => {
                if (displayModeModal.style.display === 'flex') {
                    loadCurrentDisplayMode(quizId, false); // Silent refresh
                }
            }, 10000);
        }
    });

    // Close display mode modal
    document.querySelectorAll('#display-mode-modal .cm-modal-close, #display-mode-modal .cm-modal-backdrop').forEach(button => {
        button.addEventListener('click', function() {
            displayModeModal.style.display = 'none';
            if (modeRefreshInterval) {
                clearInterval(modeRefreshInterval);
                modeRefreshInterval = null;
            }
        });
    });

    // Set mode on block click
    document.addEventListener('click', function(e) {
        const modeOption = e.target.closest('#display-mode-modal .mode-option');
        if (modeOption) {
            const mode = modeOption.dataset.mode;
            setQuizDisplayMode(currentQuizIdForMode, mode);
        }
    });

    // Toggle mode button
    document.addEventListener('click', function(e) {
        if (e.target.matches('#toggle-quiz-mode') || e.target.closest('#toggle-quiz-mode')) {
            toggleQuizMode(currentQuizIdForMode);
        }
    });

    // Refresh mode status button
    document.addEventListener('click', function(e) {
        if (e.target.matches('#refresh-mode-status') || e.target.closest('#refresh-mode-status')) {
            loadCurrentDisplayMode(currentQuizIdForMode);
        }
    });

    // Auto-switch checkbox
    document.addEventListener('change', function(e) {
        if (e.target.id === 'auto-switch-enabled') {
            const enabled = e.target.checked;
            const delayContainer = document.getElementById('auto-switch-delay-container');
            
            if (enabled) {
                delayContainer.style.display = 'block';
            } else {
                delayContainer.style.display = 'none';
            }
            
            // Save auto-switch setting
            const delay = document.getElementById('auto-switch-delay').value;
            configureAutoSwitch(currentQuizIdForMode, enabled, delay);
        }
    });

    // Auto-switch delay change
    document.addEventListener('change', function(e) {
        if (e.target.id === 'auto-switch-delay') {
            const enabled = document.getElementById('auto-switch-enabled').checked;
            const delay = parseInt(e.target.value);
            
            if (enabled) {
                configureAutoSwitch(currentQuizIdForMode, enabled, delay);
            }
        }
    });

    // Open live preview
    document.addEventListener('click', function(e) {
        if (e.target.matches('#open-live-preview') || e.target.closest('#open-live-preview')) {
            const quizUrl = `${window.location.origin}/quiz/?quiz_id=${currentQuizIdForMode}`;
            window.open(quizUrl, '_blank', 'width=800,height=600');
        }
    });

    function loadCurrentDisplayMode(quizId, showLoading = true) {
        if (showLoading) {
            document.getElementById('current-mode-text').textContent = 'Sprawdzanie...';
            document.getElementById('current-mode-description').textContent = 'Ładowanie informacji o trybie...';
        }

        jQuery.ajax({
            url: cm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cm_get_quiz_current_mode',
                quiz_id: quizId,
                nonce: cm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    updateModeDisplay(data);
                    loadConnectionStats(quizId);
                } else {
                    document.getElementById('current-mode-text').textContent = 'Błąd';
                    document.getElementById('current-mode-description').textContent = 'Nie udało się załadować informacji o trybie';
                }
            },
            error: function() {
                document.getElementById('current-mode-text').textContent = 'Błąd połączenia';
                document.getElementById('current-mode-description').textContent = 'Wystąpił błąd podczas łączenia z serwerem';
            }
        });
    }

    function updateModeDisplay(data) {
        const modeText = document.getElementById('current-mode-text');
        const modeDescription = document.getElementById('current-mode-description');
        const modeBadge = document.getElementById('current-mode-badge');
        
        // Update mode badge
        modeBadge.className = 'mode-badge';
        if (data.mode === 'qr') {
            modeBadge.classList.add('qr-mode');
            modeText.textContent = 'Kod QR';
            modeDescription.textContent = 'Uczestnicy mogą dołączyć do quizu skanując kod QR';
        } else {
            modeBadge.classList.add('results-mode');
            modeText.textContent = 'Wyniki na żywo';
            modeDescription.textContent = 'Wyświetlane są wyniki quizu w czasie rzeczywistym';
        }

        // Update mode options active state
        document.querySelectorAll('.mode-option').forEach(option => {
            option.classList.remove('active');
            if (option.dataset.mode === data.mode) {
                option.classList.add('active');
            }
        });

        // Update auto-switch controls
        const autoSwitchEnabled = document.getElementById('auto-switch-enabled');
        const autoSwitchDelay = document.getElementById('auto-switch-delay');
        const autoSwitchDelayContainer = document.getElementById('auto-switch-delay-container');

        autoSwitchEnabled.checked = data.auto_switch_enabled;
        autoSwitchDelay.value = data.auto_switch_delay || 30;
        
        if (data.auto_switch_enabled) {
            autoSwitchDelayContainer.style.display = 'block';
        } else {
            autoSwitchDelayContainer.style.display = 'none';
        }
    }

    function setQuizDisplayMode(quizId, mode) {
        jQuery.ajax({
            url: cm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cm_set_quiz_state_mode',
                quiz_id: quizId,
                mode: mode,
                nonce: cm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Reload mode display
                    loadCurrentDisplayMode(quizId);
                    
                    // Show success message
                    const modeText = mode === 'qr' ? 'QR' : 'wyników';
                    alert(`Pomyślnie przełączono na tryb ${modeText}`);
                } else {
                    alert('Błąd podczas zmiany trybu: ' + response.data);
                }
            },
            error: function() {
                alert('Wystąpił błąd podczas zmiany trybu');
            }
        });
    }

    function toggleQuizMode(quizId) {
        jQuery.ajax({
            url: cm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cm_toggle_quiz_mode',
                quiz_id: quizId,
                nonce: cm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Reload mode display
                    loadCurrentDisplayMode(quizId);
                    
                    // Show success message
                    const newMode = response.data.mode === 'qr' ? 'QR' : 'wyników';
                    alert(`Pomyślnie przełączono na tryb ${newMode}`);
                } else {
                    alert('Błąd podczas przełączania trybu: ' + response.data);
                }
            },
            error: function() {
                alert('Wystąpił błąd podczas przełączania trybu');
            }
        });
    }

    function configureAutoSwitch(quizId, enabled, delay) {
        jQuery.ajax({
            url: cm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cm_configure_auto_switch',
                quiz_id: quizId,
                enabled: enabled ? 1 : 0,
                delay: delay,
                nonce: cm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    console.log('Auto-switch configured successfully');
                } else {
                    console.error('Auto-switch configuration failed:', response.data);
                }
            },
            error: function() {
                console.error('Auto-switch configuration error');
            }
        });
    }

    function loadConnectionStats(quizId) {
        // For now, we'll show placeholder data
        // In a real implementation, you would fetch actual SSE connection stats
        document.getElementById('active-connections').textContent = '-';
        document.getElementById('total-participants').textContent = '-';
        
        // Optional: Load actual participant count
        jQuery.ajax({
            url: cm_ajax.ajax_url,
            type: 'GET',
            data: {
                action: 'cm_get_quiz_participant_count',
                quiz_id: quizId,
                nonce: cm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    document.getElementById('total-participants').textContent = response.data.count || '0';
                }
            }
        });
    }
    
    // Question type change handler
    document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'question_type') {
            const answersSection = document.getElementById('answers-section');
            const answersList = document.getElementById('answers-list');
            
            if (e.target.value === 'single' || e.target.value === 'multiple') {
                answersSection.classList.remove('hidden');
                
                // Clear existing answers and add initial ones if empty
                if (answersList.children.length === 0) {
                    addAnswerField();
                    addAnswerField();
                }
            } else {
                answersSection.classList.add('hidden');
            }
        }
    });
    
    // Add answer field function
    function addAnswerField() {
        const answersList = document.getElementById('answers-list');
        const questionType = document.getElementById('question_type').value;
        const inputType = questionType === 'multiple' ? 'checkbox' : 'radio';
        const answerIndex = answersList.children.length;
        
        const answerDiv = document.createElement('div');
        answerDiv.className = 'answer-item';
        answerDiv.innerHTML = `
            <input type="${inputType}" name="correct_answers${questionType === 'multiple' ? '[]' : ''}" value="${answerIndex}" />
            <input type="text" name="answers[]" placeholder="Wprowadź odpowiedź..." required />
            <button type="button" class="remove-answer-btn" onclick="removeAnswerField(this)">Usuń</button>
        `;
        
        answersList.appendChild(answerDiv);
    }
    
    // Remove answer field function
    window.removeAnswerField = function(button) {
        const answerItem = button.closest('.answer-item');
        if (answerItem) {
            answerItem.remove();
            
            // Update indices for remaining answers
            const answersList = document.getElementById('answers-list');
            const inputs = answersList.querySelectorAll('input[type="radio"], input[type="checkbox"]');
            inputs.forEach((input, index) => {
                input.value = index;
            });
        }
    };
    
    // Add answer button event listener
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'add-answer-btn') {
            addAnswerField();
        }
    });
});
</script>