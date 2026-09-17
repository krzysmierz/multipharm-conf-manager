<?php
/**
 * Empty day state template
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/admin/partials/event-edit
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl p-12 text-center">
    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-6">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
    </div>
    <h3 class="text-xl font-semibold text-gray-900 mb-2">Brak prezentacji na dzień <?php echo isset($day_number) ? $day_number : '1'; ?></h3>
    <p class="text-gray-600 mb-8 max-w-md mx-auto">Dodaj pierwszą prezentację do tego dnia, aby rozpocząć tworzenie harmonogramu.</p>
    <button type="button" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-sm" id="add-first-lineup-item">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Dodaj pierwszą prezentację
    </button>
</div>