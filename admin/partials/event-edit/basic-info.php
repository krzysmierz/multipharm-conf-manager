<?php

/**
 * Basic event information edit template
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/admin/partials/event-edit
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="cm-basic-info-tab">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Podstawowe informacje wydarzenia</h2>
        <p class="text-gray-600">Wprowadź podstawowe dane dotyczące wydarzenia konferencyjnego.</p>
    </div>

    <form method="post" class="cm-event-basic-form">
        <?php wp_nonce_field('cm_event_form'); ?>
        <input type="hidden" name="action" value="cm_save_event">
        <input type="hidden" name="event_id" value="<?php echo esc_attr($event->get_id()); ?>">

        <div class="grid grid-cols-1 md:grid-cols-12 gap-x-6 gap-y-8">
            <div class="md:col-span-12">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    Tytuł wydarzenia *
                </label>
                <input type="text" id="title" name="title"
                       value="<?php echo esc_attr($event->get_title()); ?>"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>
                <p class="mt-1 text-sm text-gray-500">Wprowadź nazwę wydarzenia konferencyjnego.</p>
            </div>

            <div class="md:col-span-12">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Opis wydarzenia
                </label>
                <textarea id="description" name="description" rows="6"
                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"><?php echo esc_textarea($event->get_description()); ?></textarea>
                <p class="mt-1 text-sm text-gray-500">Szczegółowy opis wydarzenia, programu, prelegentów itp.</p>
            </div>

            <div class="md:col-span-4">
                <label for="event_date" class="block text-sm font-medium text-gray-700 mb-2">
                    Data wydarzenia *
                </label>
                <input type="date" id="event_date" name="event_date"
                       value="<?php echo esc_attr(date('Y-m-d', strtotime($event->get_event_date()))); ?>"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>
                <p class="mt-1 text-sm text-gray-500">Wybierz datę wydarzenia.</p>
            </div>

            <div class="md:col-span-4">
                <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">
                    Godzina rozpoczęcia
                </label>
                <input type="time" id="start_time" name="start_time"
                       value="<?php echo esc_attr(substr($event->get_start_time(), 0, 5)); ?>"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <p class="mt-1 text-sm text-gray-500">Planowana godzina rozpoczęcia wydarzenia.</p>
            </div>

            <div class="md:col-span-4">
                <label for="end_time" class="block text-sm font-medium text-gray-700 mb-2">
                    Godzina zakończenia
                </label>
                <input type="time" id="end_time" name="end_time"
                       value="<?php echo esc_attr(substr($event->get_end_time(), 0, 5)); ?>"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <p class="mt-1 text-sm text-gray-500">Planowana godzina zakończenia wydarzenia.</p>
            </div>

            <div class="md:col-span-6">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                    Status wydarzenia
                </label>
                <select id="status" name="status"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="draft" <?php selected($event->get_status(), 'draft'); ?>>
                        Szkic - wydarzenie w przygotowaniu
                    </option>
                    <option value="active" <?php selected($event->get_status(), 'active'); ?>>
                        Aktywny - wydarzenie trwa lub jest dostępne
                    </option>
                    <option value="paused" <?php selected($event->get_status(), 'paused'); ?>>
                        Wstrzymany - tymczasowo niedostępny
                    </option>
                    <option value="completed" <?php selected($event->get_status(), 'completed'); ?>>
                        Zakończony - wydarzenie się skończyło
                    </option>
                </select>
                <p class="mt-1 text-sm text-gray-500">Bieżący status wydarzenia wpływa na dostępność dla uczestników.</p>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-8 mt-8">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">
                        Pamiętaj o zapisaniu zmian przed przejściem do innej zakładki.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="<?php echo admin_url('admin.php?page=conference-manager-events'); ?>"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Anuluj
                    </a>
                    <input type="submit" name="submit" id="submit"
                           class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                           value="Zapisz zmiany">
                </div>
            </div>
        </div>
    </form>

    <!-- Event Quick Stats -->
    <div class="mt-12">
        <h3 class="text-lg font-medium text-gray-900 mb-6">Statystyki wydarzenia</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                            <span class="dashicons dashicons-list-view text-blue-600 text-base"></span>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Prezentacje w harmonogramie</dt>
                            <dd class="text-2xl font-bold text-gray-900">
                                <?php
                                $lineup_count = count(CM_Lineup::get_by_event($event->get_id()));
                                echo $lineup_count;
                                ?>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                            <span class="dashicons dashicons-clipboard text-green-600 text-base"></span>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Quizy</dt>
                            <dd class="text-2xl font-bold text-gray-900">
                                <?php
                                $quizzes_count = count(CM_Quiz::get_by_event($event->get_id()));
                                echo $quizzes_count;
                                ?>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                            <span class="dashicons dashicons-smartphone text-purple-600 text-base"></span>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Kody QR</dt>
                            <dd class="text-2xl font-bold text-gray-900">
                                <?php
                                $qr_codes_count = count(CM_QR_Generator::get_qr_codes_by_event($event->get_id()));
                                echo $qr_codes_count;
                                ?>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-gray-100 rounded-md flex items-center justify-center">
                            <span class="dashicons dashicons-calendar text-gray-600 text-base"></span>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Data utworzenia</dt>
                            <dd class="text-2xl font-bold text-gray-900">
                                <?php
                                $created_date = new DateTime($event->get_created_at());
                                echo $created_date->format('d.m.Y');
                                ?>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>