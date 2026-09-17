<?php
/**
 * Lineup item template for rendering lineup items via AJAX
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// $item should be passed as an object with lineup data
if (!isset($item)) {
    return;
}
?>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200 p-6 <?php echo $item->is_active ? 'ring-2 ring-green-500 bg-green-50' : ''; ?> relative"
     data-lineup-id="<?php echo $item->id; ?>"
     data-quiz-id="<?php echo $item->quiz_id ?? ''; ?>"
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
            <?php if (!empty($item->presenter)): ?>
                <div class="flex items-center text-sm text-gray-600 mb-2">
                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <?php echo esc_html($item->presenter); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($item->description)): ?>
                <p class="text-sm text-gray-700 mb-2"><?php echo wp_kses_post($item->description); ?></p>
            <?php endif; ?>
            <?php if (!empty($item->presentation_file)): ?>
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